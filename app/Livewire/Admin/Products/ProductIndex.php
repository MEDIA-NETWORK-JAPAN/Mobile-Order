<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ProductIndex extends Component
{
    use Toast, WithPagination;

    public $search = '';

    public $selectedStore = '';

    public $selectedCategory = '';

    public $availabilityFilter = '';

    public $sortField = 'name';

    public $sortDirection = 'asc';

    // 権限制御
    public $canEdit = false;

    public function mount()
    {
        $user = auth()->user();

        // SuperAdminのみ編集可能（POS中心設計）
        $this->canEdit = $user->isSuperAdmin();

        if (! $user->isSuperAdmin() && $user->store_id) {
            $this->selectedStore = $user->store_id;
        }

        // URLパラメータからカテゴリIDを取得
        if (request()->has('category')) {
            $this->selectedCategory = request()->get('category');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedStore()
    {
        $this->selectedCategory = '';
        $this->resetPage();
    }

    public function updatedSelectedCategory()
    {
        $this->resetPage();
    }

    public function updatedAvailabilityFilter()
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

    public function deleteProduct($productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            $this->error('商品が見つかりません。');

            return;
        }

        // 権限チェック
        $user = auth()->user();
        if (! $user->hasStoreAccess($product->store_id)) {
            $this->error('この商品を削除する権限がありません。');

            return;
        }

        $product->delete();
        $this->success('商品を削除しました。');
    }

    public function render()
    {
        $user = auth()->user();

        // 商品クエリ
        $productsQuery = Product::with(['store', 'categories'])
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            )
            ->when($this->selectedStore, fn ($query) => $query->where('store_id', $this->selectedStore)
            )
            ->when($this->selectedCategory, fn ($query) => $query->whereHas('categories', fn ($q) => $q->where('categories.id', $this->selectedCategory))
            )
            ->when($this->availabilityFilter, fn ($query) => $query->where('availability_status', $this->availabilityFilter)
            )
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('store_id', $user->store_id)
            )
            ->orderBy($this->sortField, $this->sortDirection);

        $products = $productsQuery->paginate(10);

        // フィルター用データ
        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect();
        $categories = Category::when($this->selectedStore, fn ($query) => $query->where('store_id', $this->selectedStore)
        )->active()->get();

        return view('livewire.admin.products.product-index', [
            'products' => $products,
            'stores' => $stores,
            'categories' => $categories,
            'canEdit' => $user->isSuperAdmin(), // 編集権限フラグ
        ])->layout('components.layouts.admin', ['title' => '商品管理 - 管理画面']);
    }
}
