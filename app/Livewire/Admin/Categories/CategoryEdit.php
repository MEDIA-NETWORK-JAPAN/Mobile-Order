<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class CategoryEdit extends Component
{
    use Toast, WithPagination;

    public Category $category;

    // Form fields
    public $store_id = '';
    public $name = '';
    public $description = '';
    public $sort_order = 0;
    public $is_active = true;

    // 商品選択機能
    public $productSearch = '';
    public $productStatusFilter = '';
    public $selectedUnassignedProducts = [];
    public $selectedAssignedProducts = [];

    protected $rules = [
        'store_id' => 'required|exists:stores,id',
        'name' => 'required|max:255',
        'description' => 'nullable|max:1000',
        'sort_order' => 'required|integer|min:0',
        'is_active' => 'required|boolean',
    ];

    public function mount(Category $category)
    {
        $user = auth()->user();

        // 権限チェック
        if (!$user->hasStoreAccess($category->store_id)) {
            return $this->redirectRoute('admin.categories.index')
                ->with('error', 'このカテゴリを編集する権限がありません。');
        }

        $this->category = $category;

        // フィールドに既存データをセット
        $this->store_id = $category->store_id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;

        \Log::info('CategoryEdit mount() called', [
            'category_id' => $category->id,
            'user_role' => $user->role
        ]);
    }

    public function update()
    {
        \Log::info('=== CategoryEdit update() method START ===');

        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (!$user->isSuperAdmin()) {
            \Log::warning('CategoryEdit: Non-SuperAdmin attempted to update category', [
                'user_id' => $user->id,
                'category_id' => $this->category->id
            ]);
            $this->addError('permission', 'カテゴリの編集権限がありません。カテゴリマスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->category->store_id)) {
            $this->addError('permission', 'このカテゴリを編集する権限がありません。');
            return;
        }

        // バリデーション
        try {
            $this->validate();
            \Log::info('CategoryEdit: Validation passed');
        } catch (\Exception $e) {
            \Log::error('CategoryEdit: Validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        // カテゴリ更新
        $this->category->update([
            'store_id' => $this->store_id,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => (int) $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        \Log::info('CategoryEdit: Category updated successfully', [
            'category_id' => $this->category->id
        ]);

        session()->flash('success', 'カテゴリを更新しました。');

        return $this->redirectRoute('admin.categories.index');
    }

    public function delete()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (!$user->isSuperAdmin()) {
            session()->flash('error', 'カテゴリの削除権限がありません。カテゴリマスターデータはPOS側で管理されています。緊急削除にはSuperAdmin権限が必要です。');
            return $this->redirectRoute('admin.categories.index');
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->category->store_id)) {
            session()->flash('error', 'このカテゴリを削除する権限がありません。');
            return $this->redirectRoute('admin.categories.index');
        }

        // 関連商品があるかチェック
        if ($this->category->products()->count() > 0) {
            session()->flash('error', '商品が関連付けられているカテゴリは削除できません。');
            return $this->redirectRoute('admin.categories.index');
        }

        $this->category->delete();

        session()->flash('error', 'カテゴリを削除しました。');

        return $this->redirectRoute('admin.categories.index');
    }

    public function backToIndex()
    {
        return $this->redirectRoute('admin.categories.index');
    }

    // 商品選択機能メソッド
    public function assignProducts()
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin()) {
            $this->error('商品の関連付け権限がありません。');
            return;
        }

        if (empty($this->selectedUnassignedProducts)) {
            $this->warning('商品を選択してください。');
            return;
        }

        // 選択された商品をカテゴリに関連付け（sort_orderを設定）
        $maxSortOrder = $this->category->products()->max('category_product.sort_order') ?? 0;
        
        $attachData = [];
        foreach ($this->selectedUnassignedProducts as $index => $productId) {
            $attachData[$productId] = ['sort_order' => $maxSortOrder + $index + 1];
        }
        
        $this->category->products()->attach($attachData);
        
        // 選択状態をクリア（リダイレクトせずに同じページに留まる）
        $this->selectedUnassignedProducts = [];
        $this->selectedAssignedProducts = [];
        
        $this->success('商品をカテゴリに追加しました。');
    }

    public function unassignProducts()
    {
        $user = auth()->user();
        
        if (!$user->isSuperAdmin()) {
            $this->error('商品の関連付け解除権限がありません。');
            return;
        }

        if (empty($this->selectedAssignedProducts)) {
            $this->warning('商品を選択してください。');
            return;
        }

        // 選択された商品をカテゴリから解除
        $this->category->products()->detach($this->selectedAssignedProducts);
        
        // 選択状態をクリア（リダイレクトせずに同じページに留まる）
        $this->selectedUnassignedProducts = [];
        $this->selectedAssignedProducts = [];
        
        $this->success('商品をカテゴリから削除しました。');
    }

    public function clearProductSearch()
    {
        // ページリダイレクトでフィルタクリア（カテゴリ一覧と同じ設計）
        return redirect()->route('admin.categories.edit', $this->category->id);
    }

    // フィルタ更新メソッド（商品一覧と同じ設計）
    public function updatedProductSearch()
    {
        $this->resetPage();
    }

    public function updatedProductStatusFilter()
    {
        $this->resetPage();
    }

    // 選択状態の変更を検知するメソッド
    public function updatedSelectedUnassignedProducts()
    {
        // 選択状態の変更時に自動的に呼ばれる
    }

    public function updatedSelectedAssignedProducts()
    {
        // 選択状態の変更時に自動的に呼ばれる
    }

    // 商品並び替えメソッド（カテゴリ一覧と同じ設計）
    public function moveProductUp($productId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('並び替え権限がありません。商品マスターデータはPOS側で管理されています。');
            return;
        }

        // 現在の商品を取得（category_productピボットテーブルから）
        $currentPivot = \DB::table('category_product')
            ->where('category_id', $this->category->id)
            ->where('product_id', $productId)
            ->first();
        
        if (!$currentPivot) {
            $this->error('商品が見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->category->store_id)) {
            $this->error('この商品を操作する権限がありません。');
            return;
        }

        // 一つ上の商品を取得
        $upperPivot = \DB::table('category_product')
            ->where('category_id', $this->category->id)
            ->where('sort_order', '<', $currentPivot->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($upperPivot) {
            // DB トランザクションで順序を入れ替え
            \DB::transaction(function () use ($currentPivot, $upperPivot) {
                $tempOrder = $currentPivot->sort_order;
                \DB::table('category_product')
                    ->where('id', $currentPivot->id)
                    ->update(['sort_order' => $upperPivot->sort_order]);
                \DB::table('category_product')
                    ->where('id', $upperPivot->id)
                    ->update(['sort_order' => $tempOrder]);
            });

            // 並び替え後はページ全体をリロードして確実に状態をリセット
            return redirect()->route('admin.categories.edit', $this->category->id)
                ->with('success', '商品の並び順を更新しました。');
        } else {
            $this->error('これ以上上に移動できません。');
        }
    }

    public function moveProductDown($productId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('並び替え権限がありません。商品マスターデータはPOS側で管理されています。');
            return;
        }

        // 現在の商品を取得（category_productピボットテーブルから）
        $currentPivot = \DB::table('category_product')
            ->where('category_id', $this->category->id)
            ->where('product_id', $productId)
            ->first();
        
        if (!$currentPivot) {
            $this->error('商品が見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->category->store_id)) {
            $this->error('この商品を操作する権限がありません。');
            return;
        }

        // 一つ下の商品を取得
        $lowerPivot = \DB::table('category_product')
            ->where('category_id', $this->category->id)
            ->where('sort_order', '>', $currentPivot->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($lowerPivot) {
            // DB トランザクションで順序を入れ替え
            \DB::transaction(function () use ($currentPivot, $lowerPivot) {
                $tempOrder = $currentPivot->sort_order;
                \DB::table('category_product')
                    ->where('id', $currentPivot->id)
                    ->update(['sort_order' => $lowerPivot->sort_order]);
                \DB::table('category_product')
                    ->where('id', $lowerPivot->id)
                    ->update(['sort_order' => $tempOrder]);
            });

            // 並び替え後はページ全体をリロードして確実に状態をリセット
            return redirect()->route('admin.categories.edit', $this->category->id)
                ->with('success', '商品の並び順を更新しました。');
        } else {
            $this->error('これ以上下に移動できません。');
        }
    }

    public function render()
    {
        $user = auth()->user();

        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);

        // カテゴリに所属していない商品を取得
        $unassignedProductsQuery = Product::where('store_id', $this->category->store_id)
            ->whereDoesntHave('categories', function ($query) {
                $query->where('categories.id', $this->category->id);
            })
            ->when($this->productSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->productSearch}%")
                      ->orWhere('code', 'like', "%{$this->productSearch}%");
                });
            })
            ->when($this->productStatusFilter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->productStatusFilter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy('code');

        // カテゴリに所属している商品を取得（sort_order順）- フィルタなし
        $assignedProductsQuery = $this->category->products()
            ->where('store_id', $this->category->store_id)
            ->orderByPivot('sort_order', 'asc');

        return view('livewire.admin.categories.category-edit', [
            'stores' => $stores,
            'canEdit' => $user->isSuperAdmin(),
            'unassignedProducts' => $unassignedProductsQuery->get(),
            'assignedProducts' => $assignedProductsQuery->get(),
        ])->layout('components.layouts.admin', ['title' => 'カテゴリ編集 - 管理画面']);
    }
}