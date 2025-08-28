<?php

namespace App\Livewire\Admin\Products;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\TaxRate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class ProductCreate extends Component
{
    use Toast, WithFileUploads;

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

    // File upload (temporarily disabled)
    // public $photo;

    protected $rules = [
        'store_id' => 'required|exists:stores,id',
        'code' => 'required|max:45|unique:products,code',
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
        'is_active' => 'boolean',
        'selectedCategories' => 'array',
        'selectedCategories.*' => 'exists:categories,id',
        // 'photo' => 'nullable|image|max:2048',
    ];

    public function mount()
    {
        $user = auth()->user();


        // 必須フィールドの初期値設定
        $this->tax_type = 'standard';
        $this->availability_status = 'available';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->selectedCategories = [];
        $this->expected_available_time = '';

        if (! $user->isSuperAdmin()) {
            $this->store_id = $user->store_id;
        } else {
            // SuperAdminの場合は最初の店舗をデフォルトに設定
            $firstStore = \App\Models\Store::active()->first();
            if ($firstStore) {
                $this->store_id = $firstStore->id;
            }
        }
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

    public function save()
    {
        $user = auth()->user();

        // SuperAdmin権限チェック（緊急編集機能）
        if (! $user->isSuperAdmin()) {
            $this->addError('permission', '商品の作成権限がありません。商品マスターデータはPOS側で管理されています。緊急作成にはSuperAdmin権限が必要です。');
            return;
        }

        $this->validate();

        // 税込価格計算
        $taxInPrice = $this->calculateTaxInPrice();

        // ファイルアップロード処理（一時的に無効化）
        /*
        if ($this->photo) {
            $filename = $this->photo->store('products', 'public');
            $this->image_url = asset('storage/' . $filename);
        }
        */

        $product = Product::create([
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

        // カテゴリ関連付け
        if ($this->selectedCategories) {
            $product->categories()->sync($this->selectedCategories);
        }


        // フォームをリセット
        $this->reset([
            'code', 'name', 'description', 'price', 'cost',
            'availability_message', 'expected_available_time', 'image_url', 'selectedCategories'
        ]);

        // 初期値を再設定
        $this->tax_type = 'standard';
        $this->availability_status = 'available';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->selectedCategories = [];
        $this->expected_available_time = '';

        session()->flash('success', '商品を作成しました。');
        
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

        return view('livewire.admin.products.product-create', [
            'stores' => $stores,
            'categories' => $categories,
            'canEdit' => $user->isSuperAdmin(),
            'taxInPrice' => $taxInPrice,
        ])->layout('components.layouts.admin', ['title' => '商品作成 - 管理画面']);
    }
}
