<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\TaxRate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class ProductEdit extends Component
{
    use Toast, WithFileUploads;

    public Product $product;

    // Form fields
    public $store_id = '';
    public $code = '';
    public $name = '';
    public $description = '';
    public $price = '';
    public $cost = '';
    public $tax_type = 'standard';
    public $availability_status = 'available';
    public $availability_message = '';
    public $expected_available_time = '';
    public $image_url = '';
    public $sort_order = 0;
    public $is_active = true;

    // Categories
    public $selectedCategories = [];

    protected $rules = [
        'store_id' => 'required|exists:stores,id',
        'code' => 'required|max:45',
        'name' => 'required|max:255',
        'description' => 'nullable|max:1000',
        'price' => 'required|integer|min:-999999|max:999999',
        'cost' => 'nullable|integer|min:0',
        'tax_type' => 'required|in:standard,reduced,exempt,non_taxable',
        'availability_status' => 'required|in:available,sold_out,not_arrived,preparing',
        'availability_message' => 'nullable|max:255',
        'expected_available_time' => 'nullable',
        'image_url' => 'nullable|max:500|url',
        'sort_order' => 'required|integer|min:0',
        'is_active' => 'required|boolean',
        'selectedCategories' => 'array',
        'selectedCategories.*' => 'exists:categories,id',
    ];

    public function mount(Product $product)
    {
        $user = auth()->user();

        // 権限チェック
        if (!$user->hasStoreAccess($product->store_id)) {
            return $this->redirectRoute('admin.products.index')
                ->with('error', 'この商品を編集する権限がありません。');
        }

        $this->product = $product;

        // フィールドに既存データをセット
        $this->store_id = $product->store_id;
        $this->code = $product->code;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->cost = $product->cost;
        $this->tax_type = $product->tax_type;
        $this->availability_status = $product->availability_status;
        $this->availability_message = $product->availability_message;
        $this->expected_available_time = $product->expected_available_time ?
            (is_string($product->expected_available_time) ?
                $product->expected_available_time :
                $product->expected_available_time->format('H:i')) : '';
        $this->image_url = $product->image_url;
        $this->sort_order = $product->sort_order;
        $this->is_active = $product->is_active;

        // カテゴリ関連付けをセット
        $this->selectedCategories = $product->categories->pluck('id')->toArray();

        \Log::info('ProductEdit mount() called', [
            'product_id' => $product->id,
            'user_role' => $user->role
        ]);
    }

    public function updatedCode()
    {
        // リアルタイムでコード重複チェック（編集時は自分を除外）
        $rules = $this->rules;
        $rules['code'] = 'required|max:45|unique:products,code,' . $this->product->id;
        $this->validateOnly('code', $rules);
    }

    public function updatedPrice()
    {
        if ($this->price) {
            $this->calculateTaxInPrice();
        }
    }

    public function updatedTaxType()
    {
        if ($this->price) {
            $this->calculateTaxInPrice();
        }
    }

    private function calculateTaxInPrice()
    {
        if ($this->price && $this->tax_type) {
            $taxRate = TaxRate::getRate($this->tax_type);
            return (int) ($this->price * (1 + $taxRate / 100));
        }

        return 0;
    }

    public function update()
    {
        \Log::info('=== ProductEdit update() method START ===');

        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (!$user->isSuperAdmin()) {
            \Log::warning('ProductEdit: Non-SuperAdmin attempted to update product', [
                'user_id' => $user->id,
                'product_id' => $this->product->id
            ]);
            $this->addError('permission', '商品の編集権限がありません。商品マスターデータはPOS側で管理されています。緊急編集にはSuperAdmin権限が必要です。');
            return;
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->product->store_id)) {
            $this->addError('permission', 'この商品を編集する権限がありません。');
            return;
        }

        // バリデーション（コード重複チェック含む）
        $rules = $this->rules;
        $rules['code'] = 'required|max:45|unique:products,code,' . $this->product->id;

        try {
            $this->validate($rules);
            \Log::info('ProductEdit: Validation passed');
        } catch (\Exception $e) {
            \Log::error('ProductEdit: Validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        // 税込価格計算
        $taxInPrice = $this->calculateTaxInPrice();

        // 商品更新
        $this->product->update([
            'store_id' => $this->store_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (int) $this->price,
            'tax_in_price' => $taxInPrice,
            'cost' => $this->cost ? (int) $this->cost : null,
            'tax_type' => $this->tax_type,
            'availability_status' => $this->availability_status,
            'availability_message' => $this->availability_message,
            'expected_available_time' => $this->expected_available_time ?: null,
            'image_url' => $this->image_url,
            'sort_order' => (int) $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        // カテゴリ関連付け更新
        if ($this->selectedCategories) {
            $this->product->categories()->sync($this->selectedCategories);
        } else {
            $this->product->categories()->detach();
        }

        \Log::info('ProductEdit: Product updated successfully', [
            'product_id' => $this->product->id
        ]);

        session()->flash('success', '商品を更新しました。');

        return $this->redirectRoute('admin.products.index');
    }

    public function delete()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (!$user->isSuperAdmin()) {
            session()->flash('error', '商品の削除権限がありません。商品マスターデータはPOS側で管理されています。緊急削除にはSuperAdmin権限が必要です。');
            return $this->redirectRoute('admin.products.index');
        }

        // 権限チェック
        if (!$user->hasStoreAccess($this->product->store_id)) {
            session()->flash('error', 'この商品を削除する権限がありません。');
            return $this->redirectRoute('admin.products.index');
        }

        $this->product->delete();

        session()->flash('error', '商品を削除しました。');

        return $this->redirectRoute('admin.products.index');
    }

    public function backToIndex()
    {
        return $this->redirectRoute('admin.products.index');
    }

    public function render()
    {
        $user = auth()->user();

        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);
        $categories = Category::when($this->store_id, fn ($query) => $query->where('store_id', $this->store_id)
        )->active()->get();

        // 税込価格を計算
        $taxInPrice = $this->calculateTaxInPrice();

        return view('livewire.admin.products.product-edit', [
            'stores' => $stores,
            'categories' => $categories,
            'canEdit' => $user->isSuperAdmin(),
            'taxInPrice' => $taxInPrice,
        ])->layout('components.layouts.admin', ['title' => '商品編集 - 管理画面']);
    }
}
