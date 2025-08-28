<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Models\Store;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class CategoryIndex extends Component
{
    use Toast, WithPagination;

    public $search = '';

    public $selectedStore = '';

    public $statusFilter = '';

    public $sortField = 'sort_order';

    public $sortDirection = 'asc';

    // 権限制御
    public $canEdit = false;


    public function mount()
    {
        $user = auth()->user();

        // SuperAdminのみ編集可能（POS中心設計）
        $this->canEdit = $user->isSuperAdmin();

        // URLパラメータまたはユーザーの店舗IDから店舗フィルタを設定
        if (request()->has('store')) {
            $this->selectedStore = request()->get('store');
        } elseif (! $user->isSuperAdmin() && $user->store_id) {
            $this->selectedStore = $user->store_id;
        }

        // セッションフラッシュメッセージをToast形式で表示
        if (session('success')) {
            $this->success(session('success'));
        }

        if (session('error')) {
            $this->error(session('error'));
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedStore()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        // 権限に応じて保持すべき値を記憶
        $user = auth()->user();
        
        // クエリパラメータを構築
        $params = [];
        if (!$user->isSuperAdmin() && $user->store_id) {
            $params['store'] = $user->store_id;
        }
        
        // ページリダイレクトでフィルタクリア
        return redirect()->route('admin.categories.index', $params);
    }

    public function editCategory($categoryId)
    {
        return redirect()->route('admin.categories.edit', $categoryId);
    }

    public function moveCategoryUp($categoryId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('並び替え権限がありません。カテゴリマスターデータはPOS側で管理されています。');
            return;
        }

        $category = Category::find($categoryId);
        if (!$category) {
            $this->error('カテゴリが見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($category->store_id)) {
            $this->error('このカテゴリを操作する権限がありません。');
            return;
        }

        // 一つ上のカテゴリを取得
        $upperCategory = Category::where('store_id', $category->store_id)
            ->where('sort_order', '<', $category->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($upperCategory) {
            // DB トランザクションで順序を入れ替え
            \DB::transaction(function () use ($category, $upperCategory) {
                $tempOrder = $category->sort_order;
                $category->update(['sort_order' => $upperCategory->sort_order]);
                $upperCategory->update(['sort_order' => $tempOrder]);
            });

            // 並び替え後はページ全体をリロードして確実に状態をリセット
            return redirect()->route('admin.categories.index')
                ->with('success', '並び順を更新しました。');
        } else {
            $this->error('これ以上上に移動できません。');
        }
    }

    public function moveCategoryDown($categoryId)
    {
        $user = auth()->user();

        // SuperAdmin権限チェック
        if (!$user->isSuperAdmin()) {
            $this->error('並び替え権限がありません。カテゴリマスターデータはPOS側で管理されています。');
            return;
        }

        $category = Category::find($categoryId);
        if (!$category) {
            $this->error('カテゴリが見つかりません。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($category->store_id)) {
            $this->error('このカテゴリを操作する権限がありません。');
            return;
        }

        // 一つ下のカテゴリを取得
        $lowerCategory = Category::where('store_id', $category->store_id)
            ->where('sort_order', '>', $category->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($lowerCategory) {
            // DB トランザクションで順序を入れ替え
            \DB::transaction(function () use ($category, $lowerCategory) {
                $tempOrder = $category->sort_order;
                $category->update(['sort_order' => $lowerCategory->sort_order]);
                $lowerCategory->update(['sort_order' => $tempOrder]);
            });

            // 並び替え後はページ全体をリロードして確実に状態をリセット
            return redirect()->route('admin.categories.index')
                ->with('success', '並び順を更新しました。');
        } else {
            $this->error('これ以上下に移動できません。');
        }
    }


    public function toggleActive($categoryId)
    {
        $category = Category::find($categoryId);

        if (! $category) {
            $this->error('カテゴリが見つかりません。');

            return;
        }

        // 権限チェック
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (! $user->isSuperAdmin()) {
            $this->error('カテゴリの編集権限がありません。カテゴリマスターデータはPOS側で管理されています。');

            return;
        }

        if (! $user->hasStoreAccess($category->store_id)) {
            $this->error('このカテゴリを変更する権限がありません。');

            return;
        }

        $category->update(['is_active' => ! $category->is_active]);

        $status = $category->is_active ? '有効' : '無効';
        $this->success("カテゴリを{$status}にしました。");
    }

    public function deleteCategory($categoryId)
    {
        $category = Category::find($categoryId);

        if (! $category) {
            $this->error('カテゴリが見つかりません。');

            return;
        }

        // 権限チェック
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (! $user->isSuperAdmin()) {
            $this->error('カテゴリの削除権限がありません。カテゴリマスターデータはPOS側で管理されています。');

            return;
        }

        if (! $user->hasStoreAccess($category->store_id)) {
            $this->error('このカテゴリを削除する権限がありません。');

            return;
        }

        // 関連商品があるかチェック
        if ($category->products()->count() > 0) {
            $this->error('商品が関連付けられているカテゴリは削除できません。');

            return;
        }

        $category->delete();
        $this->success('カテゴリを削除しました。');
    }

    public function render()
    {
        $user = auth()->user();

        // カテゴリクエリ
        $categoriesQuery = Category::with(['store'])
            ->withCount('products')
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%")
            )
            ->when($this->selectedStore, fn ($query) => $query->where('store_id', $this->selectedStore)
            )
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('store_id', $user->store_id)
            )
            ->orderBy($this->sortField, $this->sortDirection);

        $categories = $categoriesQuery->paginate(10);

        // フィルター用データ
        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect();

        return view('livewire.admin.categories.category-index', [
            'categories' => $categories,
            'stores' => $stores,
            'canEdit' => $user->isSuperAdmin(), // 編集権限フラグ
        ])->layout('components.layouts.admin', ['title' => 'カテゴリ管理 - 管理画面']);
    }
}
