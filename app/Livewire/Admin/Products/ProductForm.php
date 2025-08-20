<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\Category;
use App\Models\Store;
use App\Models\TaxRate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class ProductForm extends Component
{
    use WithFileUploads, Toast;

    public Product $product;
    public $isEditing = false;

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
    
    // File upload
    public $photo;

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
        'expected_available_time' => 'nullable|date_format:H:i',
        'image_url' => 'nullable|max:500|url',
        'sort_order' => 'required|integer|min:0',
        'is_active' => 'required|boolean',
        'selectedCategories' => 'array',
        'selectedCategories.*' => 'exists:categories,id',
        'photo' => 'nullable|image|max:2048',
    ];

    public function mount($productId = null)
    {
        $user = auth()->user();
        
        if ($productId) {
            $this->product = Product::with('categories')->findOrFail($productId);
            $this->isEditing = true;
            
            // 権限チェック
            if (!$user->hasStoreAccess($this->product->store_id)) {
                abort(403, 'この商品にアクセスする権限がありません。');
            }
            
            $this->fill([
                'store_id' => $this->product->store_id,
                'code' => $this->product->code,
                'name' => $this->product->name,
                'description' => $this->product->description,
                'price' => $this->product->price,
                'cost' => $this->product->cost,
                'tax_type' => $this->product->tax_type,
                'availability_status' => $this->product->availability_status,
                'availability_message' => $this->product->availability_message,
                'expected_available_time' => $this->product->expected_available_time?->format('H:i'),
                'image_url' => $this->product->image_url,
                'sort_order' => $this->product->sort_order,
                'is_active' => $this->product->is_active,
                'selectedCategories' => $this->product->categories->pluck('id')->toArray(),
            ]);
        } else {
            $this->product = new Product();
            if (!$user->isSuperAdmin()) {
                $this->store_id = $user->store_id;
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
            $this->product->tax_in_price = (int)($this->price * (1 + $taxRate / 100));
        }
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        
        // 新規作成時の権限チェック
        if (!$this->isEditing && !$user->hasStoreAccess($this->store_id)) {
            $this->error('この店舗に商品を追加する権限がありません。');
            return;
        }

        // 税込価格計算
        $this->calculateTaxInPrice();

        // ファイルアップロード処理
        if ($this->photo) {
            $filename = $this->photo->store('products', 'public');
            $this->image_url = asset('storage/' . $filename);
        }

        $data = [
            'store_id' => $this->store_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (int)$this->price,
            'tax_in_price' => $this->product->tax_in_price,
            'cost' => $this->cost ? (int)$this->cost : null,
            'tax_type' => $this->tax_type,
            'availability_status' => $this->availability_status,
            'availability_message' => $this->availability_message,
            'expected_available_time' => $this->expected_available_time ?: null,
            'image_url' => $this->image_url,
            'sort_order' => (int)$this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing) {
            $this->product->update($data);
        } else {
            $this->product = Product::create($data);
        }

        // カテゴリ関連付け
        if ($this->selectedCategories) {
            $this->product->categories()->sync($this->selectedCategories);
        } else {
            $this->product->categories()->detach();
        }

        $message = $this->isEditing ? '商品を更新しました。' : '商品を作成しました。';
        $this->success($message);

        return redirect()->route('admin.products.index');
    }

    public function render()
    {
        $user = auth()->user();
        
        $stores = $user->isSuperAdmin() ? Store::active()->get() : collect([$user->store]);
        $categories = Category::when($this->store_id, fn($query) => 
            $query->where('store_id', $this->store_id)
        )->active()->get();

        return view('livewire.admin.products.product-form', [
            'stores' => $stores,
            'categories' => $categories,
        ]);
    }
}