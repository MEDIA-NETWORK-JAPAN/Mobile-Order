# Livewire 管理画面コンポーネント ベストプラクティス

## 📚 目次

- [1. Livewire 管理画面設計原則](#1-livewire-管理画面設計原則)
- [2. コンポーネント構造設計](#2-コンポーネント構造設計)
- [3. 権限管理パターン](#3-権限管理パターン)
- [4. フォーム処理ベストプラクティス](#4-フォーム処理ベストプラクティス)
- [5. データテーブル実装パターン](#5-データテーブル実装パターン)
- [6. ファイルアップロード実装](#6-ファイルアップロード実装)
- [7. パフォーマンス最適化](#7-パフォーマンス最適化)
- [8. エラーハンドリングパターン](#8-エラーハンドリングパターン)
- [9. テスト戦略](#9-テスト戦略)

---

## 1. Livewire 管理画面設計原則

### 1.1 単一責任の原則

#### ✅ 良い例: 機能ごとに分離
```php
// 商品一覧専用
class ProductIndex extends Component

// 商品フォーム専用  
class ProductForm extends Component

// カテゴリ管理専用
class CategoryIndex extends Component
```

#### ❌ 悪い例: 複数機能の混在
```php
// 避けるべき: 一つのコンポーネントで複数機能
class ProductManager extends Component
{
    public function index() { /* 一覧 */ }
    public function create() { /* 作成 */ }
    public function edit() { /* 編集 */ }
    // 巨大なコンポーネントになりがち
}
```

### 1.2 状態管理の原則

#### プロパティの適切な初期化
```php
class ProductIndex extends Component
{
    // 検索・フィルター状態
    public $search = '';
    public $selectedStore = '';
    public $selectedCategory = '';
    
    // ソート状態
    public $sortField = 'name';
    public $sortDirection = 'asc';
    
    public function mount()
    {
        $user = auth()->user();
        // 権限に基づく初期値設定
        if (!$user->isSuperAdmin() && $user->store_id) {
            $this->selectedStore = $user->store_id;
        }
    }
}
```

### 1.3 リアクティビティの活用

#### 自動更新とページネーション制御
```php
public function updatedSearch()
{
    $this->resetPage(); // ページを1に戻す
}

public function updatedSelectedStore()
{
    $this->selectedCategory = ''; // 依存関係をクリア
    $this->resetPage();
}
```

---

## 2. コンポーネント構造設計

### 2.1 ディレクトリ構造

```
app/Livewire/Admin/
├── Products/
│   ├── ProductIndex.php      # 商品一覧
│   ├── ProductForm.php       # 商品作成・編集
│   └── ProductBulkActions.php # 一括操作
├── Categories/
│   ├── CategoryIndex.php     # カテゴリ一覧
│   └── CategoryCreate.php    # カテゴリ作成
├── Orders/
│   └── OrderIndex.php        # 注文管理
└── Dashboard/
    └── StatsWidget.php       # 統計ウィジェット
```

### 2.2 基底クラスの活用

#### 管理画面共通機能
```php
abstract class AdminComponent extends Component
{
    use Toast; // Mary UI Toast機能
    
    protected function getCurrentUser()
    {
        return auth()->user();
    }
    
    protected function hasStoreAccess($storeId)
    {
        return $this->getCurrentUser()->hasStoreAccess($storeId);
    }
    
    protected function checkPermission($storeId, $errorMessage = 'アクセス権限がありません。')
    {
        if (!$this->hasStoreAccess($storeId)) {
            $this->error($errorMessage);
            return false;
        }
        return true;
    }
}
```

#### 具体的なコンポーネントでの継承
```php
class ProductIndex extends AdminComponent
{
    public function deleteProduct($productId)
    {
        $product = Product::find($productId);
        
        if (!$this->checkPermission($product->store_id, 'この商品を削除する権限がありません。')) {
            return;
        }
        
        $product->delete();
        $this->success('商品を削除しました。');
    }
}
```

---

## 3. 権限管理パターン

### 3.1 データスコープ制限

#### ✅ 推奨パターン: クエリレベル制限
```php
public function render()
{
    $user = $this->getCurrentUser();
    
    $productsQuery = Product::with(['store', 'categories'])
        // Super Admin以外は店舗制限
        ->when(!$user->isSuperAdmin(), fn($query) => 
            $query->where('store_id', $user->store_id)
        )
        ->when($this->search, fn($query) => 
            $query->where('name', 'like', "%{$this->search}%")
        );
        
    return view('livewire.admin.products.product-index', [
        'products' => $productsQuery->paginate(10)
    ]);
}
```

### 3.2 操作権限チェック

#### 一貫した権限チェックパターン
```php
public function deleteProduct($productId)
{
    $product = Product::find($productId);
    
    if (!$product) {
        $this->error('商品が見つかりません。');
        return;
    }
    
    if (!$this->checkPermission($product->store_id)) {
        return; // エラーメッセージは基底クラスで表示
    }
    
    $product->delete();
    $this->success('商品を削除しました。');
}
```

### 3.3 フィルター選択肢の制限

#### 権限に応じた選択肢提供
```php
public function render()
{
    $user = $this->getCurrentUser();
    
    // Super Admin: 全店舗、その他: 空collection
    $stores = $user->isSuperAdmin() ? Store::active()->get() : collect();
    
    // カテゴリは選択店舗に応じて制限
    $categories = Category::when($this->selectedStore, fn($query) => 
        $query->where('store_id', $this->selectedStore)
    )->active()->get();
    
    return view('livewire.admin.products.product-index', [
        'stores' => $stores,
        'categories' => $categories,
    ]);
}
```

---

## 4. フォーム処理ベストプラクティス

### 4.1 動的バリデーション

#### リアルタイム計算とバリデーション
```php
class ProductForm extends Component
{
    public $price = '';
    public $tax_type = 'standard';
    
    protected $rules = [
        'price' => 'required|integer|min:-999999|max:999999',
        'tax_type' => 'required|in:standard,reduced,exempt,non_taxable',
    ];
    
    public function updatedPrice()
    {
        $this->validateOnly('price'); // 該当フィールドのみバリデーション
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
}
```

### 4.2 フォーム状態管理

#### 編集・新規作成の統一フォーム
```php
class ProductForm extends Component
{
    public Product $product;
    public $isEditing = false;
    
    // フォームフィールド
    public $store_id = '';
    public $name = '';
    public $selectedCategories = [];
    
    public function mount($productId = null)
    {
        if ($productId) {
            $this->product = Product::with('categories')->findOrFail($productId);
            $this->isEditing = true;
            
            // 権限チェック
            if (!$this->hasStoreAccess($this->product->store_id)) {
                abort(403, 'この商品にアクセスする権限がありません。');
            }
            
            // フォームデータ設定
            $this->fill([
                'store_id' => $this->product->store_id,
                'name' => $this->product->name,
                'selectedCategories' => $this->product->categories->pluck('id')->toArray(),
            ]);
        } else {
            $this->product = new Product();
            $user = auth()->user();
            if (!$user->isSuperAdmin()) {
                $this->store_id = $user->store_id;
            }
        }
    }
    
    public function save()
    {
        $this->validate();
        
        // 権限チェック
        if (!$this->isEditing && !$this->hasStoreAccess($this->store_id)) {
            $this->error('この店舗に商品を追加する権限がありません。');
            return;
        }
        
        // 保存処理
        $data = [
            'store_id' => $this->store_id,
            'name' => $this->name,
            // ... 他のフィールド
        ];
        
        if ($this->isEditing) {
            $this->product->update($data);
        } else {
            $this->product = Product::create($data);
        }
        
        // 関連データ同期
        $this->product->categories()->sync($this->selectedCategories);
        
        $message = $this->isEditing ? '商品を更新しました。' : '商品を作成しました。';
        $this->success($message);
        
        return redirect()->route('admin.products.index');
    }
}
```

---

## 5. データテーブル実装パターン

### 5.1 効率的なクエリ構築

#### Eager Loading の活用
```php
public function render()
{
    $categoriesQuery = Category::with(['store'])
        ->withCount('products')  // 関連商品数
        ->when($this->search, fn($query) => 
            $query->where('name', 'like', "%{$this->search}%")
        )
        ->orderBy($this->sortField, $this->sortDirection);
    
    return view('livewire.admin.categories.category-index', [
        'categories' => $categoriesQuery->paginate(10)
    ]);
}
```

### 5.2 ソート機能実装

#### 双方向ソート
```php
public $sortField = 'name';
public $sortDirection = 'asc';

public function sortBy($field)
{
    if ($this->sortField === $field) {
        // 同じフィールドの場合は方向反転
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        // 異なるフィールドの場合はasc固定
        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }
}
```

### 5.3 インライン編集実装

#### 状態管理パターン
```php
class CategoryIndex extends Component
{
    // インライン編集状態
    public $editingCategory = null;
    public $editingName = '';
    public $editingSortOrder = '';
    
    public function editCategory($categoryId)
    {
        $category = Category::find($categoryId);
        
        if (!$this->checkPermission($category->store_id)) {
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
        
        Category::find($this->editingCategory)->update([
            'name' => $this->editingName,
            'sort_order' => (int)$this->editingSortOrder,
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
}
```

---

## 6. ファイルアップロード実装

### 6.1 ファイルアップロードのベストプラクティス

#### セキュアな画像アップロード
```php
class ProductForm extends Component
{
    use WithFileUploads;
    
    public $photo;
    
    protected $rules = [
        'photo' => 'nullable|image|max:2048|mimes:jpeg,png,gif', // 2MB制限
    ];
    
    public function save()
    {
        $this->validate();
        
        // ファイルアップロード処理
        if ($this->photo) {
            // バリデーション済みファイルの処理
            $filename = $this->photo->store('products', 'public');
            $this->image_url = asset('storage/' . $filename);
            
            // 古い画像の削除（編集時）
            if ($this->isEditing && $this->product->image_url) {
                $oldPath = str_replace(asset('storage/'), '', $this->product->image_url);
                Storage::disk('public')->delete($oldPath);
            }
        }
        
        // ... 保存処理
    }
}
```

### 6.2 プレビュー機能

#### リアルタイム画像プレビュー
```php
// Livewire コンポーネント内
public function updatedPhoto()
{
    $this->validateOnly('photo');
}

// ビュー内での表示
@if ($photo)
    <img src="{{ $photo->temporaryUrl() }}" alt="プレビュー" class="w-32 h-32 object-cover">
@elseif ($image_url)
    <img src="{{ $image_url }}" alt="現在の画像" class="w-32 h-32 object-cover">
@endif
```

---

## 7. パフォーマンス最適化

### 7.1 クエリ最適化

#### N+1問題の回避
```php
// ❌ 悪い例: N+1問題
$products = Product::paginate(10);
foreach ($products as $product) {
    echo $product->store->name; // 各レコードでクエリ実行
}

// ✅ 良い例: Eager Loading
$products = Product::with('store', 'categories')->paginate(10);
```

### 7.2 計算結果のキャッシュ

#### withCount の活用
```php
$categoriesQuery = Category::with(['store'])
    ->withCount('products')  // 関連商品数をクエリで計算
    ->orderBy($this->sortField, $this->sortDirection);
```

### 7.3 ページネーション最適化

#### 適切なページサイズ
```php
// 管理画面では10-20件が適切
public function render()
{
    return view('livewire.admin.products.product-index', [
        'products' => $this->getProductsQuery()->paginate(10)
    ]);
}
```

---

## 8. エラーハンドリングパターン

### 8.1 一貫したエラー表示

#### Mary UI Toast の活用
```php
use Mary\Traits\Toast;

class ProductIndex extends Component
{
    use Toast;
    
    public function deleteProduct($productId)
    {
        $product = Product::find($productId);
        
        if (!$product) {
            $this->error('商品が見つかりません。');
            return;
        }
        
        try {
            $product->delete();
            $this->success('商品を削除しました。');
        } catch (\Exception $e) {
            $this->error('削除に失敗しました。');
            \Log::error('Product deletion failed', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### 8.2 バリデーションエラー処理

#### 段階的バリデーション
```php
public function save()
{
    try {
        $this->validate();
    } catch (ValidationException $e) {
        $this->error('入力内容に問題があります。');
        throw $e; // Livewire が自動でフィールドエラー表示
    }
    
    // 業務ロジックバリデーション
    if (!$this->hasStoreAccess($this->store_id)) {
        $this->error('この店舗への操作権限がありません。');
        return;
    }
    
    // 保存処理
}
```

---

## 9. テスト戦略

### 9.1 Livewire コンポーネントテスト

#### 基本的なテストパターン
```php
use Livewire\Livewire;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_view_products_list()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create();
        $products = Product::factory()->count(3)->create(['store_id' => $store->id]);
        
        Livewire::actingAs($user)
            ->test(ProductIndex::class)
            ->assertSee($products->first()->name)
            ->assertViewHas('products');
    }
    
    public function test_can_search_products()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'テストハンバーガー']);
        
        Livewire::actingAs($user)
            ->test(ProductIndex::class)
            ->set('search', 'ハンバーガー')
            ->assertSee('テストハンバーガー');
    }
    
    public function test_can_delete_product()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        
        Livewire::actingAs($user)
            ->test(ProductIndex::class)
            ->call('deleteProduct', $product->id)
            ->assertEmitted('toast'); // Toast表示確認
            
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
```

### 9.2 権限テスト

#### 権限制御のテスト
```php
public function test_staff_cannot_access_other_store_products()
{
    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();
    
    $staff = User::factory()->create([
        'role' => 'staff',
        'store_id' => $store1->id
    ]);
    
    $product = Product::factory()->create(['store_id' => $store2->id]);
    
    Livewire::actingAs($staff)
        ->test(ProductIndex::class)
        ->assertDontSee($product->name);
}
```

---

## 📝 まとめ

### 実装時の重要ポイント

1. **単一責任**: 一つのコンポーネントは一つの機能に集中
2. **権限制御**: 全ての操作で適切な権限チェック
3. **パフォーマンス**: Eager Loading と適切なクエリ構築
4. **ユーザビリティ**: リアルタイム更新と直感的操作
5. **保守性**: 一貫したパターンとコード構造

### Livewire の利点

- **開発効率**: SPAライクなUXを少ないコードで実現
- **保守性**: コンポーネント単位での機能分割
- **セキュリティ**: Laravel標準のセキュリティ機能を継承

この実装パターンにより、スケーラブルで保守しやすい管理画面システムを構築できます。