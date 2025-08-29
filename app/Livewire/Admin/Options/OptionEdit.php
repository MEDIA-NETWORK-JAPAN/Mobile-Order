<?php

namespace App\Livewire\Admin\Options;

use App\Models\Option;
use App\Models\OptionDetail;
use App\Models\Product;
use App\Models\Store;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class OptionEdit extends Component
{
    use Toast, WithPagination;

    public Option $option;

    // Form fields
    public $store_id = '';
    public $title = '';
    public $required = false;
    public $selection_type = 'single';

    // 商品選択機能（カテゴリ管理と同様）
    public $productSearch = '';
    public $productStatusFilter = '';
    public $selectedUnassignedProducts = [];
    public $selectedAssignedProducts = [];
    
    // 関連商品管理用
    public $relatedProductSearch = '';
    public $relatedProductStatusFilter = '';
    public $selectedUnrelatedProducts = [];
    public $selectedRelatedProducts = [];

    protected $rules = [
        'store_id' => 'required|exists:stores,id',
        'title' => 'required|max:45',
        'required' => 'required|boolean',
        'selection_type' => 'required|in:single,multiple',
    ];

    public function mount(Option $option)
    {
        $user = auth()->user();

        // 権限チェック
        if (!$user->hasStoreAccess($option->store_id)) {
            return $this->redirectRoute('admin.options.index')
                ->with('error', 'このオプションを編集する権限がありません。');
        }

        $this->option = $option;

        // フォーム初期化
        $this->store_id = $option->store_id;
        $this->title = $option->title;
        $this->required = $option->required;
        $this->selection_type = $option->selection_type;

        // セッションフラッシュメッセージをToast形式で表示
        if (session('success')) {
            $this->success(session('success'));
        }

        if (session('error')) {
            $this->error(session('error'));
        }
    }

    public function updatedProductSearch()
    {
        $this->resetPage();
    }

    public function updatedProductStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedRelatedProductSearch()
    {
        $this->resetPage();
    }

    public function updatedRelatedProductStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        // ページリダイレクトでフィルタクリア（カテゴリ編集と同じ設計）
        return redirect()->route('admin.options.edit', $this->option->id);
    }

    public function clearUnassignedSelection()
    {
        $this->selectedUnassignedProducts = [];
    }

    public function clearAssignedSelection()
    {
        $this->selectedAssignedProducts = [];
    }

    public function assignProducts()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('選択肢の追加権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        if (empty($this->selectedUnassignedProducts)) {
            $this->warning('商品を選択してください。');
            return;
        }

        // 最大sort_orderを取得
        $maxSortOrder = OptionDetail::where('option_id', $this->option->id)->max('sort_order') ?? 0;

        foreach ($this->selectedUnassignedProducts as $index => $productId) {
            OptionDetail::create([
                'option_id' => $this->option->id,
                'product_id' => $productId,
                'sort_order' => $maxSortOrder + $index + 1,
                'default_selected' => false,
            ]);
        }

        // 選択状態をクリア（リダイレクトせずに同じページに留まる）
        $this->selectedUnassignedProducts = [];
        $this->selectedAssignedProducts = [];
        
        $this->success('商品をオプションに追加しました。');
    }

    public function unassignProducts()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('選択肢の削除権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        if (empty($this->selectedAssignedProducts)) {
            $this->warning('商品を選択してください。');
            return;
        }

        foreach ($this->selectedAssignedProducts as $detailId) {
            $detail = OptionDetail::find($detailId);
            if ($detail) {
                $detail->delete();
            }
        }

        // 選択状態をクリア（リダイレクトせずに同じページに留まる）
        $this->selectedUnassignedProducts = [];
        $this->selectedAssignedProducts = [];
        
        $this->success('商品をオプションから削除しました。');
    }

    public function toggleDefault($detailId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('デフォルト設定の変更権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        $detail = OptionDetail::find($detailId);
        if (!$detail) {
            $this->error('選択肢が見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->option->store_id)) {
            $this->error('この選択肢を操作する権限がありません。');
            return;
        }

        // 単一選択の場合、他のデフォルトを解除
        if ($this->option->selection_type === 'single' && !$detail->default_selected) {
            OptionDetail::where('option_id', $this->option->id)
                ->update(['default_selected' => false]);
        }

        // トグル処理
        $detail->update(['default_selected' => !$detail->default_selected]);

        $message = $detail->default_selected ? 'デフォルトに設定しました。' : 'デフォルトを解除しました。';
        $this->success($message);
    }

    public function clearRelatedFilters()
    {
        // ページリダイレクトでフィルタクリア
        return redirect()->route('admin.options.edit', $this->option->id);
    }

    public function linkProducts()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('商品との関連付け権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        if (empty($this->selectedUnrelatedProducts)) {
            $this->warning('商品を選択してください。');
            return;
        }

        foreach ($this->selectedUnrelatedProducts as $productId) {
            // 既に関連付けされているかチェック
            if (!$this->option->products()->where('products.id', $productId)->exists()) {
                // 関連付け（最大sort_orderを取得）
                $maxSortOrder = $this->option->products()->max('product_to_options.sort_order') ?? 0;
                $this->option->products()->attach($productId, ['sort_order' => $maxSortOrder + 1]);
            }
        }

        // 選択状態をクリア
        $this->selectedUnrelatedProducts = [];
        $this->selectedRelatedProducts = [];
        
        $this->success('商品をオプションと関連付けしました。');
    }

    public function unlinkProducts()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('商品との関連付け解除権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        if (empty($this->selectedRelatedProducts)) {
            $this->warning('商品を選択してください。');
            return;
        }

        foreach ($this->selectedRelatedProducts as $productId) {
            $this->option->products()->detach($productId);
        }

        // 選択状態をクリア
        $this->selectedUnrelatedProducts = [];
        $this->selectedRelatedProducts = [];
        
        $this->success('商品とオプションの関連付けを解除しました。');
    }

    public function linkProduct($productId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('商品との関連付け権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            $this->error('商品が見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($product->store_id)) {
            $this->error('この商品を操作する権限がありません。');
            return;
        }

        // 既に関連付けされているかチェック
        if ($this->option->products()->where('products.id', $productId)->exists()) {
            $this->warning('この商品は既に関連付けされています。');
            return;
        }

        // 関連付け（最大sort_orderを取得）
        $maxSortOrder = $this->option->products()->max('product_to_options.sort_order') ?? 0;
        $this->option->products()->attach($productId, ['sort_order' => $maxSortOrder + 1]);

        $this->success('商品をオプションと関連付けしました。');
    }

    public function unlinkProduct($productId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('商品との関連付け解除権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            $this->error('商品が見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($product->store_id)) {
            $this->error('この商品を操作する権限がありません。');
            return;
        }

        // 関連付け解除
        $this->option->products()->detach($productId);

        $this->success('商品とオプションの関連付けを解除しました。');
    }

    public function moveProductUp($detailId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('並び替え権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        $detail = OptionDetail::find($detailId);
        if (!$detail) {
            $this->error('選択肢が見つかりません。');
            return;
        }

        // 一つ上の選択肢を取得
        $upperDetail = OptionDetail::where('option_id', $this->option->id)
            ->where('sort_order', '<', $detail->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($upperDetail) {
            \DB::transaction(function () use ($detail, $upperDetail) {
                $tempOrder = $detail->sort_order;
                $detail->update(['sort_order' => $upperDetail->sort_order]);
                $upperDetail->update(['sort_order' => $tempOrder]);
            });

            return redirect()->route('admin.options.edit', $this->option)
                ->with('success', '並び順を更新しました。');
        } else {
            $this->error('これ以上上に移動できません。');
        }
    }

    public function moveProductDown($detailId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('並び替え権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        $detail = OptionDetail::find($detailId);
        if (!$detail) {
            $this->error('選択肢が見つかりません。');
            return;
        }

        // 一つ下の選択肢を取得
        $lowerDetail = OptionDetail::where('option_id', $this->option->id)
            ->where('sort_order', '>', $detail->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($lowerDetail) {
            \DB::transaction(function () use ($detail, $lowerDetail) {
                $tempOrder = $detail->sort_order;
                $detail->update(['sort_order' => $lowerDetail->sort_order]);
                $lowerDetail->update(['sort_order' => $tempOrder]);
            });

            return redirect()->route('admin.options.edit', $this->option)
                ->with('success', '並び順を更新しました。');
        } else {
            $this->error('これ以上下に移動できません。');
        }
    }

    public function updateOption()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('オプションの更新権限がありません。オプションマスターデータはPOS側で管理されています。');
            return;
        }

        $this->validate();

        $this->option->update([
            'title' => $this->title,
            'required' => $this->required,
            'selection_type' => $this->selection_type,
        ]);

        session()->flash('success', 'オプション情報を更新しました。');

        return $this->redirectRoute('admin.options.index');
    }

    public function backToIndex()
    {
        return $this->redirectRoute('admin.options.index');
    }

    public function delete()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (!$user->isSuperAdmin()) {
            session()->flash('error', 'オプションの削除権限がありません。オプションマスターデータはPOS側で管理されています。');
            return $this->redirectRoute('admin.options.index');
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->option->store_id)) {
            session()->flash('error', 'このオプションを削除する権限がありません。');
            return $this->redirectRoute('admin.options.index');
        }

        // 関連商品があるかチェック
        if ($this->option->products()->count() > 0) {
            session()->flash('error', '商品が関連付けられているオプションは削除できません。');
            return $this->redirectRoute('admin.options.index');
        }

        $this->option->delete();

        session()->flash('success', 'オプションを削除しました。');

        return $this->redirectRoute('admin.options.index');
    }

    public function render()
    {
        $user = auth()->user();

        // 現在このオプションに割り当てられている商品IDを取得
        $assignedProductIds = OptionDetail::where('option_id', $this->option->id)
            ->pluck('product_id')
            ->toArray();

        // 未所属商品（このオプションに未割り当ての商品）
        $unassignedProductsQuery = Product::where('store_id', $this->option->store_id)
            ->whereNotIn('id', $assignedProductIds)
            ->when($this->productSearch, fn ($query) => 
                $query->where('name', 'like', "%{$this->productSearch}%")
                      ->orWhere('code', 'like', "%{$this->productSearch}%")
            )
            ->when($this->productStatusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->productStatusFilter === 'inactive', fn ($query) => $query->where('is_active', false));

        // 所属商品（このオプションに割り当て済みの商品）
        $assignedProductsQuery = OptionDetail::where('option_id', $this->option->id)
            ->with('product')
            ->orderBy('sort_order');

        $unassignedProducts = $unassignedProductsQuery->get();
        $assignedProducts = $assignedProductsQuery->get();

        // 関連商品取得（このオプションを使用している商品）
        $relatedProducts = Product::whereHas('options', function ($query) {
                $query->where('options.id', $this->option->id);
            })
            ->with(['categories', 'store'])
            ->get();

        // フィルター用データ
        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect();
        
        // 未関連商品（このオプションを使用していない商品）
        $unrelatedProductsQuery = Product::where('store_id', $this->option->store_id)
            ->whereDoesntHave('options', function ($query) {
                $query->where('options.id', $this->option->id);
            })
            ->when($this->relatedProductSearch, fn ($query) => 
                $query->where('name', 'like', "%{$this->relatedProductSearch}%")
                      ->orWhere('code', 'like', "%{$this->relatedProductSearch}%")
            )
            ->when($this->relatedProductStatusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->relatedProductStatusFilter === 'inactive', fn ($query) => $query->where('is_active', false));

        $unrelatedProducts = $unrelatedProductsQuery->get();

        return view('livewire.admin.options.option-edit', [
            'unassignedProducts' => $unassignedProducts,
            'assignedProducts' => $assignedProducts,
            'relatedProducts' => $relatedProducts,
            'unrelatedProducts' => $unrelatedProducts,
            'stores' => $stores,
            'canEdit' => $user->isSuperAdmin(),
        ])->layout('components.layouts.admin', ['title' => "オプション編集: {$this->option->title} - 管理画面"]);
    }
}