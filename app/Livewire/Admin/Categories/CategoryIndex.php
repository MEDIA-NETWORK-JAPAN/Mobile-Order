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

    public $sortField = 'sort_order';

    public $sortDirection = 'asc';

    // 権限制御
    public $canEdit = false;

    // Inline editing
    public $editingCategory = null;

    public $editingName = '';

    public $editingSortOrder = '';

    public function mount()
    {
        $user = auth()->user();

        // SuperAdminのみ編集可能（POS中心設計）
        $this->canEdit = $user->isSuperAdmin();

        if (! $user->isSuperAdmin() && $user->store_id) {
            $this->selectedStore = $user->store_id;
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

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function editCategory($categoryId)
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
            $this->error('このカテゴリを編集する権限がありません。');

            return;
        }

        $this->editingCategory = $categoryId;
        $this->editingName = $category->name;
        $this->editingSortOrder = $category->sort_order;
    }

    public function updateCategory()
    {
        $this->validate([
            'editingName' => 'required|max:255',
            'editingSortOrder' => 'required|integer|min:0',
        ]);

        $category = Category::find($this->editingCategory);

        if (! $category) {
            $this->error('カテゴリが見つかりません。');

            return;
        }

        $category->update([
            'name' => $this->editingName,
            'sort_order' => (int) $this->editingSortOrder,
        ]);

        $this->success('カテゴリを更新しました。');
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->editingCategory = null;
        $this->editingName = '';
        $this->editingSortOrder = '';
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
