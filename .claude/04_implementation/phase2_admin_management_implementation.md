# Phase 2: 管理機能実装ドキュメント

## 📚 目次

- [1. 実装概要](#1-実装概要)
- [2. Eloquentモデル設計](#2-eloquentモデル設計)
- [3. Livewire管理画面コンポーネント](#3-livewire管理画面コンポーネント)
- [4. 認証・権限管理システム](#4-認証権限管理システム)
- [5. ルーティング設計](#5-ルーティング設計)
- [6. 特徴的な機能実装](#6-特徴的な機能実装)
- [7. セキュリティ考慮事項](#7-セキュリティ考慮事項)
- [8. 今後の拡張予定](#8-今後の拡張予定)

---

## 1. 実装概要

### 1.1 Phase 2の目的
Phase 1で構築したデータベース基盤の上に、管理者向けの操作インターフェースを実装。店舗オーナーやスタッフが商品・カテゴリの管理を行える管理画面を提供する。

### 1.2 技術スタック
- **フレームワーク**: Laravel 10 + Livewire 3
- **UI コンポーネント**: Mary UI (TailwindCSS ベース)
- **認証**: Laravel Sanctum + 独自権限管理
- **データベース**: MySQL (Docker環境)

### 1.3 実装範囲
```
Phase 2-1: 管理機能基盤
├── Eloquentモデル (8モデル)
├── Livewire管理画面コンポーネント (4コンポーネント)
├── 管理画面コントローラー (DashboardController)
├── 認証・権限管理システム
└── ルーティング設定
```

---

## 2. Eloquentモデル設計

### 2.1 Store モデル (店舗管理)

#### 主要機能
- **QRコード運用モード設定**: 固定モード/都度発行モード
- **営業時間管理**: JSON形式で柔軟な時間設定
- **多店舗対応**: 各店舗独立した設定管理

```php
// 重要なリレーション
public function users()     // 所属スタッフ
public function products()  // 店舗商品
public function sessions()  // 席セッション
public function orders()    // 店舗注文

// 重要なスコープ
public function scopeActive($query)        // 有効店舗のみ
public function scopeByCode($query, $code) // 店舗コード検索
```

#### 設計ポイント
- `business_hours` と `settings` はJSON型で柔軟性を確保
- `qr_mode` は ENUM('fixed', 'temporary') で運用方式を管理
- ソフトデリート対応で履歴保持

### 2.2 Product モデル (商品マスター)

#### 統一商品マスター設計
```php
// 価格管理 (マイナス価格対応)
'price' => 'integer',          // 基本価格（値引き商品対応）
'tax_in_price' => 'integer',   // 税込価格（自動計算）
'cost' => 'integer',           // 原価

// 提供状態管理
'availability_status' => ['available', 'sold_out', 'not_arrived', 'preparing']
```

#### 多言語対応
```php
// JSON形式で多言語翻訳を格納
public function getTranslatedNameAttribute($language = 'ja')
{
    if ($this->translations && isset($this->translations[$language]['name'])) {
        return $this->translations[$language]['name'];
    }
    return $this->name;
}
```

#### リレーション設計
- **多対多関係**: `categories()`, `options()`
- **一対多関係**: `images()`, `orderItems()`, `cartLogs()`
- **pivot テーブル活用**: `category_product`, `product_to_options`

### 2.3 Category モデル (カテゴリ管理)

#### シンプル階層構造
```php
// 基本設計
protected $fillable = [
    'store_id', 'name', 'translations', 
    'sort_order', 'is_active'
];

// 多言語対応
public function getTranslatedNameAttribute($language = 'ja')
```

#### 商品との関連
```php
public function products()
{
    return $this->belongsToMany(Product::class, 'category_product')
                ->withPivot('sort_order')
                ->orderBy('pivot_sort_order');
}
```

### 2.4 User モデル拡張 (権限管理強化)

#### 役割ベース権限管理
```php
// 権限レベル
public function isSuperAdmin()  // 全店舗アクセス可能
public function isAdmin()       // 店舗管理者
public function isStaff()       // 一般スタッフ
public function isPosSystem()   // POSシステム用

// 店舗アクセス制御
public function hasStoreAccess($storeId)
{
    return $this->isSuperAdmin() || $this->store_id == $storeId;
}
```

---

## 3. Livewire管理画面コンポーネント

### 3.1 ProductIndex コンポーネント (商品一覧)

#### 高度なフィルタリング機能
```php
// フィルタリング項目
public $search = '';              // 商品名・コード検索
public $selectedStore = '';       // 店舗フィルター
public $selectedCategory = '';    // カテゴリフィルター
public $availabilityFilter = '';  // 提供状態フィルター

// ソート機能
public $sortField = 'name';
public $sortDirection = 'asc';
```

#### 権限に基づくデータ表示
```php
$productsQuery = Product::with(['store', 'categories'])
    ->when(!$user->isSuperAdmin(), fn($query) => 
        $query->where('store_id', $user->store_id)
    );
```

#### リアクティブ更新
- 検索条件変更時の自動ページリセット
- フィルター連動（店舗選択→カテゴリ更新）
- インライン削除機能

### 3.2 ProductForm コンポーネント (商品作成・編集)

#### 動的フォーム制御
```php
// 税込価格の自動計算
public function updatedPrice()
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
```

#### ファイルアップロード対応
```php
use Livewire\WithFileUploads;

public $photo; // 画像アップロード

// 保存処理でファイル処理
if ($this->photo) {
    $filename = $this->photo->store('products', 'public');
    $this->image_url = asset('storage/' . $filename);
}
```

#### バリデーション
```php
protected $rules = [
    'code' => 'required|max:45',
    'name' => 'required|max:255',
    'price' => 'required|integer|min:-999999|max:999999', // マイナス価格対応
    'selectedCategories' => 'array',
    'photo' => 'nullable|image|max:2048',
];
```

### 3.3 CategoryIndex コンポーネント (カテゴリ一覧)

#### インライン編集機能
```php
// 編集状態管理
public $editingCategory = null;
public $editingName = '';
public $editingSortOrder = '';

public function editCategory($categoryId)
{
    $category = Category::find($categoryId);
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
}
```

#### 関連データ表示
```php
$categoriesQuery = Category::with(['store'])
    ->withCount('products')  // 関連商品数を表示
    ->orderBy($this->sortField, $this->sortDirection);
```

---

## 4. 認証・権限管理システム

### 4.1 認証フロー設計

#### シンプルな認証システム
```php
// routes/auth.php
Route::post('login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        // 最終ログイン時刻更新
        auth()->user()->update(['last_login_at' => now()]);
        return redirect()->intended(route('admin.dashboard'));
    }
    throw ValidationException::withMessages(['email' => __('auth.failed')]);
});
```

### 4.2 多層権限システム

#### 権限レベル定義
```
super_admin: 全店舗・全機能アクセス可能
admin:       所属店舗の管理機能アクセス可能  
staff:       所属店舗の基本機能のみ
pos_system:  API アクセス用（POS端末）
```

#### 権限チェック実装例
```php
// Livewire コンポーネント内
public function deleteProduct($productId)
{
    $product = Product::find($productId);
    $user = auth()->user();
    
    if (!$user->hasStoreAccess($product->store_id)) {
        $this->error('この商品を削除する権限がありません。');
        return;
    }
    
    $product->delete();
}
```

### 4.3 セッション管理
- Laravel標準のセッション機能を使用
- 管理者ログイン後は `admin.dashboard` へリダイレクト
- ログアウト時はセッション無効化とトークン再生成

---

## 5. ルーティング設計

### 5.1 管理画面ルーティング構造
```php
// routes/web.php
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Products
    Route::get('/products', ProductIndex::class)->name('products.index');
    Route::get('/products/create', ProductForm::class)->name('products.create');
    Route::get('/products/{product}/edit', ProductForm::class)->name('products.edit');
    
    // Categories  
    Route::get('/categories', CategoryIndex::class)->name('categories.index');
    Route::get('/categories/create', CategoryCreate::class)->name('categories.create');
});
```

### 5.2 Livewire ルーティングの特徴
- **コンポーネントベース**: 各機能を独立したLivewireコンポーネントとして実装
- **RESTful URL**: `/admin/products/create`, `/admin/categories` など直感的なURL
- **認証必須**: 全ルートに `auth` ミドルウェア適用

---

## 6. 特徴的な機能実装

### 6.1 多店舗対応システム

#### Super Admin の全店舗アクセス
```php
// DashboardController.php
$storeQuery = $user->isSuperAdmin() 
    ? Store::query() 
    : Store::where('id', $user->store_id);

$stats = [
    'total_stores' => $user->isSuperAdmin() ? Store::count() : 1,
    'total_products' => Product::when(!$user->isSuperAdmin(), function ($query) use ($user) {
        return $query->where('store_id', $user->store_id);
    })->count(),
];
```

#### 店舗フィルタリング
```php
// Livewireコンポーネント内
public function mount()
{
    $user = auth()->user();
    if (!$user->isSuperAdmin() && $user->store_id) {
        $this->selectedStore = $user->store_id; // 自動設定
    }
}
```

### 6.2 税込価格自動計算システム

#### TaxRate モデルによる計算
```php
class TaxRate extends Model
{
    public static function getRate($taxType)
    {
        $taxRate = static::where('tax_type', $taxType)->first();
        return $taxRate ? $taxRate->rate : 0;
    }

    public static function calculateTaxIncludedAmount($amount, $taxType)
    {
        $taxAmount = static::calculateTaxAmount($amount, $taxType);
        return $amount + $taxAmount;
    }
}
```

#### フォーム内でのリアルタイム計算
```php
// ProductForm.php
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
```

### 6.3 マイナス価格対応（値引き商品）

#### データベース制約
```sql
-- マイグレーション内
ALTER TABLE products ADD CONSTRAINT chk_products_price 
CHECK (price >= -999999.99 AND price <= 999999.99);
```

#### バリデーション
```php
// ProductForm.php
protected $rules = [
    'price' => 'required|integer|min:-999999|max:999999', // マイナス値許可
];
```

### 6.4 多言語対応準備

#### JSON翻訳フィールド設計
```php
// 翻訳データ構造例
{
  "ja": "ハンバーガー",
  "en": "Hamburger",
  "zh-TW": "漢堡",
  "zh-CN": "汉堡", 
  "ko": "햄버거"
}
```

#### アクセサメソッド
```php
public function getTranslatedNameAttribute($language = 'ja')
{
    if ($this->translations && isset($this->translations[$language]['name'])) {
        return $this->translations[$language]['name'];
    }
    return $this->name; // フォールバック
}
```

---

## 7. セキュリティ考慮事項

### 7.1 権限ベースアクセス制御

#### 全操作での権限チェック
```php
// 商品削除時の例
if (!$user->hasStoreAccess($product->store_id)) {
    $this->error('この商品を削除する権限がありません。');
    return;
}
```

#### データ取得時のスコープ制限
```php
$productsQuery = Product::when(!$user->isSuperAdmin(), fn($query) => 
    $query->where('store_id', $user->store_id)
);
```

### 7.2 バリデーション

#### Livewire バリデーション
- フロントエンドとバックエンドでの二重チェック
- CSRFトークン自動検証
- ファイルアップロード時のMIMEタイプ検証

#### データベースレベル制約
- 外部キー制約でデータ整合性保証
- CHECK制約で値の妥当性検証
- NOT NULL制約で必須項目強制

### 7.3 監査証跡

#### 最終ログイン記録
```php
// ログイン時
auth()->user()->update(['last_login_at' => now()]);
```

#### 将来的な変更ログ（Phase 4で実装予定）
- `change_logs` テーブルへの変更記録
- POS同期用の履歴管理

---

## 8. 今後の拡張予定

### 8.1 Phase 2-2: ビュー実装
- **管理画面テンプレート**: MaryUI + TailwindCSS
- **レスポンシブデザイン**: モバイル・タブレット対応
- **ダッシュボード**: 売上統計・在庫状況

### 8.2 Phase 3: お客様向け機能
- **QRコード読み取り**: セッション開始
- **メニュー表示**: カテゴリ別商品一覧
- **注文機能**: カート・オプション選択

### 8.3 Phase 4: POS連携
- **リアルタイム同期**: 在庫・注文状況
- **障害復旧**: オフライン時の継続運用
- **データ整合性**: 双方向同期保証

### 8.4 セキュリティ強化
- **2FA認証**: 管理者アカウント保護
- **API レート制限**: POS連携の安全性向上
- **監査ログ**: 全操作の追跡可能性

---

## 📝 実装時の学習ポイント

### Laravel + Livewire の組み合わせ
- **リアクティブ**: フロントエンドとバックエンドの境界がシームレス
- **開発効率**: SPAライクなUXを少ないコードで実現
- **保守性**: コンポーネント単位での機能分割

### 多店舗システム設計
- **スケーラビリティ**: 店舗数増加に対応可能な設計
- **権限分離**: 各店舗のデータ独立性保証
- **運用柔軟性**: Super Admin による一元管理

### データベース駆動開発
- **正規化**: データの一貫性とパフォーマンスのバランス
- **制約活用**: データベースレベルでの品質保証
- **リレーション**: Eloquentによる直感的なデータ操作

このPhase 2実装により、管理機能の基盤が完成し、次のフェーズでのUI実装やお客様向け機能開発の土台が整いました。