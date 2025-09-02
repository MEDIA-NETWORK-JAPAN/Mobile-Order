# API設計書

## 📚 目次

- [1. API設計方針](#1-api設計方針)
  - [1.1 APIスタイル](#11-apiスタイル)
  - [1.2 バージョニング戦略](#12-バージョニング戦略)
  - [1.3 認証方式とセッション管理](#13-認証方式とセッション管理)
- [2. API命名規則](#2-api命名規則)
  - [2.1 エンドポイント命名](#21-エンドポイント命名)
  - [2.2 命名ルール](#22-命名ルール)
- [3. 共通仕様](#3-共通仕様)
  - [3.1 リクエストヘッダー](#31-リクエストヘッダー)
  - [3.2 レスポンス形式](#32-レスポンス形式)
  - [3.3 HTTPステータスコード](#33-httpステータスコード)
- [4. API エンドポイント一覧](#4-api-エンドポイント一覧)
  - [4.1 認証・セッション管理API](#41-認証セッション管理api)
  - [4.2 ゲストメニュー・注文（Webルート - Livewire実装）](#42-ゲストメニュー注文webルート-livewire実装)
  - [4.3 POS連携API](#43-pos連携api)
  - [4.4 POS専用API（商品データ操作）](#44-pos専用api商品データ操作)
  - [4.5 管理画面（Webルート - Livewire実装）](#45-管理画面webルート-livewire実装)
- [5. API詳細仕様](#5-api詳細仕様)
  - [5.1 認証・セッション管理API](#51-認証セッション管理api)
  - [5.2 ゲストメニュー・注文（Livewire実装）](#52-ゲストメニュー注文livewire実装)
  - [5.3 POS連携API](#53-pos連携api)
  - [5.4 POS専用API（商品データ操作）](#54-pos専用api商品データ操作)
  - [5.5 管理画面（Livewire実装）](#55-管理画面livewire実装)
- [6. エラーハンドリング](#6-エラーハンドリング)
  - [6.1 一般的なエラー](#61-一般的なエラー)
  - [6.2 ゲストセッション固有のエラー](#62-ゲストセッション固有のエラー)
- [7. セキュリティ対策](#7-セキュリティ対策)
  - [7.1 レート制限](#71-レート制限)
  - [7.2 CORS設定](#72-cors設定)
  - [7.3 セキュリティヘッダー](#73-セキュリティヘッダー)

---

## 1. API設計方針

### 1.1 APIスタイル
- **RESTful API**: リソース指向の設計
- **JSON形式**: リクエスト/レスポンスボディ
- **UTF-8エンコーディング**: 全ての文字列データ

### 1.2 バージョニング戦略
- **URLパス方式**: `/api/v1/`, `/api/v2/`
- **下位互換性**: 最低6ヶ月間は旧バージョンをサポート
- **非推奨通知**: レスポンスヘッダーで通知

### 1.3 認証方式とセッション管理
- **POS起点の統一管理**: 全ての席セッションIDはPOS端末で生成
- **2層認証システム（モバイルWeb）**: 
  - 第1層: 席セッション（POS生成 → WebでURL化）
  - 第2層: ゲストセッション（Laravel Breezeカスタム認証）
- **POS API**: Bearer Token認証（Laravel Sanctum）
- **管理画面**: Session認証（Laravel Breeze）
- **モバイルWeb**: Session認証（Laravel Breezeカスタム）※ APIではなくWebルートで実装
- **障害復旧**: cloud_synced=FALSEフラグでシンプル管理


## 2. API命名規則

### 2.1 エンドポイント命名
```
# リソースの集合
GET    /api/v1/products           # 一覧取得
POST   /api/v1/products           # 新規作成

# 単一リソース
GET    /api/v1/products/{id}     # 詳細取得
PUT    /api/v1/products/{id}     # 更新
DELETE /api/v1/products/{id}     # 削除

# リソースのアクション
POST   /api/v1/orders/{id}/confirm     # 注文確認
POST   /api/v1/sessions/start          # セッション開始
```

### 2.2 命名ルール
- **小文字とハイフン**: `products`、`categories`（ケバブケース）
- **複数形**: コレクションリソースは複数形
- **動詞は使わない**: RESTfulの原則に従う（例外：特殊アクション）

## 3. 共通仕様

### 3.1 リクエストヘッダー
```http
Content-Type: application/json
Accept: application/json
Accept-Language: ja,en;q=0.9
Authorization: Bearer {token}
X-Request-ID: {uuid}
```

### 3.2 レスポンス形式

#### 成功レスポンス
```json
{
  "success": true,
  "data": {...},
  "message": "操作が完了しました",
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

#### エラーレスポンス
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": {...}
  }
}
```

### 3.3 HTTPステータスコード
- **200 OK**: 取得・更新成功
- **201 Created**: 作成成功
- **400 Bad Request**: リクエスト形式エラー
- **401 Unauthorized**: 認証エラー
- **403 Forbidden**: 権限エラー
- **404 Not Found**: リソース未発見
- **422 Unprocessable Entity**: バリデーションエラー
- **500 Internal Server Error**: サーバー内部エラー

## 4. API エンドポイント一覧


### 4.1 認証・セッション管理API

#### 席セッション認証
- **[GET /order?session=xxx](#🔐-qrコードセッション開始)** - QRコードセッション開始（Webルート）

#### ゲストセッション（自動延長）
- **[POST /guest/register](#🔐-ゲスト登録)** - ゲスト登録
- **[POST /guest/logout](#🔐-ゲストログアウト)** - ゲストログアウト

**注：各API実行時に自動的にexpires_at延長（30分）**

### 4.2 ゲストメニュー・注文（Webルート - Livewire実装）

#### メニュー表示
- **[GET /menu](#📱-メニュー画面)** - メニュー画面表示（Livewire）
- **[GET /menu/categories](#📋-カテゴリ一覧)** - カテゴリ切り替え（Livewire）
- **[GET /menu/products/{id}](#🍜-商品詳細表示ステップバイステップ)** - 商品詳細モーダル（Livewire）

#### カート管理
- **[POST /cart/items](#🛒-カートアイテム追加)** - カートアイテム追加（Livewire）
- **[GET /cart](#🛒-カート表示下からスライドアップ)** - カート表示（Livewire）
- **[DELETE /cart/items/{product_id}](#🗑️-カートアイテム削除)** - アイテム削除（Livewire）
- **[DELETE /cart](#🗑️-カートクリア)** - カートクリア（Livewire）

#### 注文管理
- **[POST /orders](#📝-注文作成)** - 注文作成（Livewire）
- **[GET /order-history](#📋-注文履歴表示)** - 注文履歴表示（Livewire）

### 4.3 POS連携API

#### POS認証
- **[POST /api/v1/pos/auth/login](#🔐-posログイン認証)** - POSログイン認証
- **[POST /api/v1/pos/auth/refresh](#🔄-posトークン更新)** - POSトークン更新
- **[POST /api/v1/pos/auth/logout](#🔓-posログアウト)** - POSログアウト

#### システム監視
- **[POST /api/v1/pos/health/sync](#🔄-双方向ヘルスチェック同期)** - 双方向ヘルスチェック同期

#### セッション・注文管理
- **[POST /api/v1/pos/sessions](#🆕-セッション新規作成pos側)** - 席セッション作成
- **[POST /api/v1/pos/sessions/extend](#席セッション延長)** - 席セッション延長
- **[POST /api/v1/pos/sessions/complete](#席セッション完了会計処理)** - 席セッション完了（会計処理）
- **[GET /api/v1/pos/changes](#🔄-変更履歴取得ポーリング)** - 変更履歴取得（ポーリング）
- **[POST /api/v1/pos/changes/sync](#✅-同期完了通知)** - 同期完了通知
- **[PUT /api/v1/pos/orders/{id}](#pos注文ステータス更新)** - 注文ステータス更新
- **[POST /api/v1/pos/orders/{id}/cancel](#注文キャンセルハンディ端末専用)** - 注文キャンセル（ハンディ端末専用）

#### 障害復旧・同期
- **[POST /api/v1/pos/recovery/start](#障害復旧開始)** - 障害復旧開始
- **[POST /api/v1/pos/recovery/complete](#障害復旧完了)** - 障害復旧完了
- **[POST /api/v1/pos/sync-sessions](#セッション同期)** - セッション同期
- **[POST /api/v1/pos/sync-orders](#注文同期)** - 注文同期

#### その他
- **[PUT /api/v1/pos/products/{id}](#商品提供状態更新)** - 商品提供状態更新
- **[POST /api/v1/pos/translations/sync](#多言語翻訳同期)** - 多言語翻訳同期

### 4.4 POS専用API（商品データ操作）

#### 商品マスター管理（POS端末からのみアクセス可能）
- **[POST /api/v1/pos/products/verify](#📦-pos商品同期検証)** - 商品同期検証
- **[POST /api/v1/pos/products](#📦-pos商品作成)** - 商品作成
- **[PUT /api/v1/pos/products/{id}](#pos商品更新)** - 商品更新
- **[DELETE /api/v1/pos/products/{id}](#pos商品削除)** - 商品削除

#### カテゴリマスター管理（POS端末からのみアクセス可能）
- **[POST /api/v1/pos/categories/verify](#📦-posカテゴリ同期検証)** - カテゴリ同期検証
- **[POST /api/v1/pos/categories](#カテゴリ作成)** - カテゴリ作成
- **[PUT /api/v1/pos/categories/{id}](#カテゴリ更新)** - カテゴリ更新
- **[DELETE /api/v1/pos/categories/{id}](#posカテゴリ削除)** - カテゴリ削除

#### オプションマスター管理（POS端末からのみアクセス可能）
- **[POST /api/v1/pos/options/verify](#📦-posオプション同期検証)** - オプション同期検証
- **[POST /api/v1/pos/options](#posオプション作成)** - オプション作成
- **[PUT /api/v1/pos/options/{id}](#posオプション更新)** - オプション更新
- **[DELETE /api/v1/pos/options/{id}](#posオプション削除)** - オプション削除

### 4.5 管理画面（Webルート - Livewire実装）

#### メニュー閲覧（POS専用のCRUD操作により作成されたデータの表示のみ）
- **[GET /admin/products](#📋-商品一覧管理画面用)** - 商品一覧（読み取り専用）
- **[GET /admin/products/{id}](#管理商品詳細)** - 商品詳細（読み取り専用）
- **[GET /admin/categories](#管理カテゴリ一覧)** - カテゴリ一覧（読み取り専用）
- **[GET /admin/categories/{id}](#管理カテゴリ詳細)** - カテゴリ詳細（読み取り専用）
- **[GET /admin/options](#管理オプション一覧)** - オプション一覧（読み取り専用）
- **[GET /admin/options/{id}](#管理オプション詳細)** - オプション詳細（読み取り専用）

#### Web固有設定管理（管理画面で変更可能）
- **[GET /admin/settings](#⚙️-システム設定管理)** - システム設定一覧
- **[PUT /admin/settings/{key}](#⚙️-システム設定管理)** - システム設定更新

#### ユーザー管理（管理画面で変更可能）
- **[GET /admin/users](#📋-ユーザー一覧管理画面用)** - ユーザー一覧
- **[POST /admin/users](#管理ユーザー作成)** - ユーザー作成
- **[PUT /admin/users/{id}](#管理ユーザー更新)** - ユーザー更新
- **[DELETE /admin/users/{id}](#管理ユーザー削除)** - ユーザー削除

#### セッション管理（読み取り専用）
- **[GET /admin/sessions](#セッション一覧読み取り専用)** - セッション一覧（読み取り専用）
- **[GET /admin/sessions/{id}](#管理セッション詳細)** - セッション詳細（読み取り専用）

#### レポート（読み取り専用）
- **[GET /admin/reports/sales](#📊-レポート画面)** - 売上レポート
- **[GET /admin/reports/products](#📊-レポート画面)** - 商品分析レポート



## 5. API詳細仕様

### 5.1 認証・セッション管理API

---

## 🔐 QRコードセッション開始
```
操作: GET /order?session=xxx
実装: Laravelコントローラー SessionController::start()
```

**呼び出しタイミング**
- **誰が**: スマホブラウザ（QRコードアクセス）
- **いつ**: QRコード読み取り→直接URLアクセス
- **目的**: 席セッションの検証とゲスト登録画面へリダイレクト
- **技術的補足**: sessionパラメータでコントローラーがセッション検証し、正常時は/guest/registerへリダイレクト

**リクエスト**
```
GET /order?session=SESSION_POS_FIXED_a7b3c9d4e5f61829_12
※ URLパラメータのみ、JSONボディなし
```

**レスポンス**
```
302 Redirect
Location: /guest/register
※ セッション検証成功時は/guest/registerへリダイレクト

セッション無効時は適切なエラーページへリダイレクト：
- 404エラー画面: 存在しないsession_id
- 期限切れ画面: expires_atを過ぎたセッション
```



## 🔐 ゲスト登録
```
操作: POST /guest/register
実装: Livewireアクション registerGuest($deviceFingerprint, $language)
```

**呼び出しタイミング**
- **誰が**: スマホブラウザ（Webフォーム）
- **いつ**: QRコード読み取り後のデバイス識別登録
- **目的**: Breezeカスタム認証でゲストセッション作成

**リクエスト**
```json
{
  "session_id": "SESSION_POS_FIXED_a7b3c9d4e5f61829_12",
  "device_fingerprint": "device_fingerprint_hash_xyz",
  "language": "ja"
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "guest_token": "guest_abc123def456",
    "expires_in": 1800,
    "session_info": {
      "session_id": "SESSION_POS_FIXED_a7b3c9d4e5f61829_12",
      "table_number": "12"
    }
  },
  "message": "ゲストセッションが開始されました"
}
```

**注：各API呼び出し時に自動的にexpires_at延長（30分）**

- DB TTL管理: guest_sessions.expires_at
- Redis TTL管理: guest_cart:{token} (カートデータ)
- Laravel Scheduled Taskで期限切れデータ自動削除
- 自動延長により継続的な利用をサポート


## 🔐 ゲストログアウト
```
操作: POST /guest/logout
実装: Livewireアクション logout()
```

**呼び出しタイミング**
- **誰が**: スマホブラウザ（Webフォーム）
- **いつ**: 利用終了時（任意）
- **目的**: Breezeセッションの明示的な終了

**レスポンス**
```json
{
  "success": true,
  "message": "ゲストセッションが終了されました"
}
```

### 5.2 ゲストメニュー・注文（Livewire実装）

#### メニュー表示

---

## 📱 メニュー画面
```
操作: GET /menu
実装: Livewireコンポーネント MenuPage::class
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン（ゲスト認証済み）
- **いつ**: ゲスト登録完了後の初回表示・メニュー画面アクセス時
- **目的**: 商品一覧・カテゴリ表示・注文機能を統合したメイン画面

**Livewire実装**
```php
// app/Livewire/Guest/MenuPage.php
class MenuPage extends Component
{
    public $selectedCategory = null;
    public $cartItems = [];
    public $cartTotal = 0;
    
    public function mount()
    {
        $this->loadCartItems();
    }
    
    public function selectCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;
    }
    
    public function addToCart($productId, $options = [])
    {
        // カートに商品追加
        CartService::addItem(auth('guest')->user(), $productId, $options);
        $this->loadCartItems();
        $this->dispatch('cart-updated');
    }
    
    public function render()
    {
        $categories = Category::active()->orderBy('sort_order')->get();
        $products = Product::with(['options', 'category'])
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->available()
            ->get();
            
        return view('livewire.guest.menu-page', compact('categories', 'products'));
    }
}
```

**Bladeテンプレート**
```blade
<div class="min-h-screen bg-gray-50">
    <!-- カテゴリタブ -->
    <div class="bg-white shadow-sm">
        <div class="flex overflow-x-auto">
            @foreach($categories as $category)
            <button wire:click="selectCategory({{ $category->id }})" 
                    class="px-4 py-2 whitespace-nowrap {{ $selectedCategory === $category->id ? 'border-b-2 border-amber-500' : '' }}">
                {{ $category->name }}
            </button>
            @endforeach
        </div>
    </div>
    
    <!-- 商品一覧 -->
    <div class="grid grid-cols-2 gap-4 p-4">
        @foreach($products as $product)
        <x-mary-card wire:click="$dispatch('show-product-modal', { productId: {{ $product->id }} })" 
                     class="cursor-pointer hover:shadow-md">
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-32 object-cover">
            <h3 class="font-bold">{{ $product->name }}</h3>
            <p class="text-gray-600">¥{{ number_format($product->price) }}</p>
            <x-mary-badge :value="$product->availability_status" />
        </x-mary-card>
        @endforeach
    </div>
    
    <!-- 下部固定カート -->
    @if(count($cartItems) > 0)
    <div class="fixed bottom-0 left-0 right-0 bg-amber-500 text-white p-4" 
         wire:click="$dispatch('show-cart')">
        <div class="flex justify-between items-center">
            <span>🛒 カート ({{ count($cartItems) }}点)</span>
            <span>¥{{ number_format($cartTotal) }}</span>
        </div>
        <div class="text-center text-sm">▼ タップして注文へ進む ▼</div>
    </div>
    @endif
</div>
```


## 📋 カテゴリ一覧
```
操作: GET /menu/categories (概念)
実装: Livewireアクション selectCategory($categoryId)
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: メニュー画面でカテゴリタブ切り替え時
- **目的**: カテゴリ別商品フィルタリング

**Livewire実装**
```php
// MenuPageコンポーネント内
public function selectCategory($categoryId)
{
    $this->selectedCategory = $categoryId;
    // Livewireが自動的に再描画してフィルタリングされた商品を表示
}
```

**Bladeテンプレート例**
```blade
<!-- カテゴリタブ -->
<div class="bg-white shadow-sm">
    <div class="flex overflow-x-auto">
        <button wire:click="selectCategory(null)" 
                class="px-4 py-2 whitespace-nowrap {{ $selectedCategory === null ? 'border-b-2 border-amber-500' : '' }}">
            すべて
        </button>
        @foreach($categories as $category)
        <button wire:click="selectCategory({{ $category->id }})" 
                class="px-4 py-2 whitespace-nowrap {{ $selectedCategory === $category->id ? 'border-b-2 border-amber-500' : '' }}">
            {{ $category->name }}
        </button>
        @endforeach
    </div>
</div>
```


## 🍜 商品詳細表示（ステップバイステップ）
```
操作: GET /menu/products/{id} (概念)
実装: Livewireアクション showProductModal($productId) + Alpine.jsモーダル
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: 商品カードタップ時
- **目的**: 商品詳細表示とオプション選択

**Livewire実装（段階的オプション選択）**
```php
// app/Livewire/Guest/ProductModal.php
class ProductModal extends Component
{
    public $product;
    public $selectedOptions = [];
    public $quantity = 1;
    public $totalPrice = 0;
    public $showModal = false;
    public $currentStep = 'basic'; // basic -> required -> optional -> confirm
    public $requiredOptions = [];
    public $optionalOptions = [];
    
    protected $listeners = ['show-product-modal' => 'showProduct'];
    
    public function showProduct($productId)
    {
        $this->product = Product::with(['options.option_details'])->find($productId);
        $this->selectedOptions = [];
        $this->quantity = 1;
        $this->currentStep = 'basic';
        
        // オプションを必須・任意に分類
        $this->requiredOptions = $this->product->options->where('is_required', true);
        $this->optionalOptions = $this->product->options->where('is_required', false);
        
        $this->calculatePrice();
        $this->showModal = true;
    }
    
    public function nextStep()
    {
        switch ($this->currentStep) {
            case 'basic':
                // 必須オプションがある場合は必須選択画面へ、なければ任意選択画面へ
                if ($this->requiredOptions->count() > 0) {
                    $this->currentStep = 'required';
                } elseif ($this->optionalOptions->count() > 0) {
                    $this->currentStep = 'optional';
                } else {
                    $this->currentStep = 'confirm';
                }
                break;
                
            case 'required':
                // 必須オプションのバリデーション
                foreach ($this->requiredOptions as $option) {
                    if (!isset($this->selectedOptions[$option->id])) {
                        session()->flash('error', $option->name . 'を選択してください');
                        return;
                    }
                }
                
                // 任意オプションがある場合は任意選択画面へ、なければ確認画面へ
                if ($this->optionalOptions->count() > 0) {
                    $this->currentStep = 'optional';
                } else {
                    $this->currentStep = 'confirm';
                }
                break;
                
            case 'optional':
                $this->currentStep = 'confirm';
                break;
        }
    }
    
    public function previousStep()
    {
        switch ($this->currentStep) {
            case 'required':
                $this->currentStep = 'basic';
                break;
            case 'optional':
                if ($this->requiredOptions->count() > 0) {
                    $this->currentStep = 'required';
                } else {
                    $this->currentStep = 'basic';
                }
                break;
            case 'confirm':
                if ($this->optionalOptions->count() > 0) {
                    $this->currentStep = 'optional';
                } elseif ($this->requiredOptions->count() > 0) {
                    $this->currentStep = 'required';
                } else {
                    $this->currentStep = 'basic';
                }
                break;
        }
    }
    
    public function selectOption($optionId, $choiceId)
    {
        $this->selectedOptions[$optionId] = $choiceId;
        $this->calculatePrice();
    }
    
    public function calculatePrice()
    {
        $basePrice = $this->product->price;
        $optionsPrice = 0;
        
        foreach ($this->selectedOptions as $optionId => $choiceId) {
            $choice = OptionDetail::find($choiceId);
            if ($choice) {
                $optionsPrice += $choice->price;
            }
        }
        
        $this->totalPrice = ($basePrice + $optionsPrice) * $this->quantity;
    }
    
    public function addToCart()
    {
        $this->dispatch('add-to-cart', [
            'productId' => $this->product->id,
            'quantity' => $this->quantity,
            'options' => $this->selectedOptions
        ]);
        
        $this->showModal = false;
        session()->flash('message', '商品をカートに追加しました');
    }
    
    public function getStepTitle()
    {
        return match ($this->currentStep) {
            'basic' => '商品詳細',
            'required' => '必須オプション選択',
            'optional' => '任意オプション選択',
            'confirm' => '注文内容確認',
            default => '商品詳細'
        };
    }
}
```

**Bladeテンプレート例（段階的遷移）**
```blade
<x-mary-modal wire:model="showModal" :title="getStepTitle()" persistent>
    @if($product)
    
    <!-- ステップインジケーター -->
    <div class="flex justify-center mb-4">
        <div class="flex space-x-2">
            <div class="w-3 h-3 rounded-full {{ $currentStep === 'basic' ? 'bg-amber-500' : 'bg-gray-300' }}"></div>
            @if($requiredOptions->count() > 0)
            <div class="w-3 h-3 rounded-full {{ $currentStep === 'required' ? 'bg-amber-500' : 'bg-gray-300' }}"></div>
            @endif
            @if($optionalOptions->count() > 0)
            <div class="w-3 h-3 rounded-full {{ $currentStep === 'optional' ? 'bg-amber-500' : 'bg-gray-300' }}"></div>
            @endif
            <div class="w-3 h-3 rounded-full {{ $currentStep === 'confirm' ? 'bg-amber-500' : 'bg-gray-300' }}"></div>
        </div>
    </div>

    <div class="space-y-4">
        @if($currentStep === 'basic')
        <!-- Step 1: 商品基本情報 -->
        <div>
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover rounded">
            <h2 class="text-xl font-bold mt-2">{{ $product->name }}</h2>
            <p class="text-gray-600">{{ $product->description }}</p>
            <p class="text-lg font-bold text-amber-600">¥{{ number_format($product->price) }}</p>
        </div>
        
        <!-- 数量選択 -->
        <div class="flex items-center justify-center space-x-4">
            <span class="font-bold">数量:</span>
            <button wire:click="$set('quantity', quantity > 1 ? quantity - 1 : 1)" 
                    class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">-</button>
            <span class="w-8 text-center text-xl font-bold">{{ $quantity }}</span>
            <button wire:click="$set('quantity', quantity + 1)" 
                    class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">+</button>
        </div>

        @elseif($currentStep === 'required')
        <!-- Step 2: 必須オプション選択 -->
        <div class="text-center mb-4">
            <h3 class="text-lg font-bold text-red-600">必須オプションを選択してください</h3>
        </div>
        
        @foreach($requiredOptions as $option)
        <div class="border-2 border-red-200 rounded-lg p-4">
            <h3 class="font-bold mb-3 text-red-600">
                {{ $option->name }} <span class="text-red-500">*</span>
            </h3>
            <div class="space-y-3">
                @foreach($option->option_details as $choice)
                <label class="flex items-center space-x-3 p-2 border rounded-lg cursor-pointer hover:bg-gray-50
                           {{ isset($selectedOptions[$option->id]) && $selectedOptions[$option->id] == $choice->id ? 'border-amber-500 bg-amber-50' : 'border-gray-200' }}">
                    <input type="radio" 
                           wire:click="selectOption({{ $option->id }}, {{ $choice->id }})"
                           name="option_{{ $option->id }}"
                           class="text-amber-500"
                           {{ isset($selectedOptions[$option->id]) && $selectedOptions[$option->id] == $choice->id ? 'checked' : '' }}>
                    <span class="flex-1">{{ $choice->name }}</span>
                    @if($choice->price > 0)
                    <span class="text-amber-600 font-bold">+¥{{ number_format($choice->price) }}</span>
                    @endif
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

        @elseif($currentStep === 'optional')
        <!-- Step 3: 任意オプション選択 -->
        <div class="text-center mb-4">
            <h3 class="text-lg font-bold text-blue-600">お好みのオプションを選択（任意）</h3>
            <p class="text-sm text-gray-600">選択しなくても注文できます</p>
        </div>
        
        @foreach($optionalOptions as $option)
        <div class="border-2 border-blue-200 rounded-lg p-4">
            <h3 class="font-bold mb-3 text-blue-600">{{ $option->name }}</h3>
            <div class="space-y-3">
                @foreach($option->option_details as $choice)
                <label class="flex items-center space-x-3 p-2 border rounded-lg cursor-pointer hover:bg-gray-50
                           {{ isset($selectedOptions[$option->id]) && $selectedOptions[$option->id] == $choice->id ? 'border-amber-500 bg-amber-50' : 'border-gray-200' }}">
                    <input type="radio" 
                           wire:click="selectOption({{ $option->id }}, {{ $choice->id }})"
                           name="option_{{ $option->id }}"
                           class="text-amber-500"
                           {{ isset($selectedOptions[$option->id]) && $selectedOptions[$option->id] == $choice->id ? 'checked' : '' }}>
                    <span class="flex-1">{{ $choice->name }}</span>
                    @if($choice->price > 0)
                    <span class="text-amber-600 font-bold">+¥{{ number_format($choice->price) }}</span>
                    @endif
                </label>
                @endforeach
            </div>
        </div>
        @endforeach

        @elseif($currentStep === 'confirm')
        <!-- Step 4: 注文内容確認 -->
        <div class="bg-amber-50 rounded-lg p-4">
            <h3 class="text-lg font-bold mb-3">注文内容確認</h3>
            
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-bold">{{ $product->name }}</span>
                    <span>¥{{ number_format($product->price) }}</span>
                </div>
                
                @foreach($selectedOptions as $optionId => $choiceId)
                    @php
                        $option = $product->options->find($optionId);
                        $choice = $option->option_details->find($choiceId);
                    @endphp
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>　└ {{ $option->name }}: {{ $choice->name }}</span>
                        @if($choice->price > 0)
                        <span>+¥{{ number_format($choice->price) }}</span>
                        @endif
                    </div>
                @endforeach
                
                <div class="flex justify-between text-sm">
                    <span>数量: {{ $quantity }}個</span>
                    <span></span>
                </div>
                
                <hr class="my-2">
                <div class="flex justify-between text-xl font-bold text-amber-600">
                    <span>合計</span>
                    <span>¥{{ number_format($totalPrice) }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <x-slot:actions>
        @if($currentStep !== 'basic')
        <x-mary-button label="戻る" wire:click="previousStep" />
        @endif
        
        <x-mary-button label="キャンセル" wire:click="$set('showModal', false)" />
        
        @if($currentStep === 'confirm')
        <x-mary-button label="カートに追加" wire:click="addToCart" class="btn-primary" />
        @else
        <x-mary-button label="次へ" wire:click="nextStep" class="btn-primary" />
        @endif
    </x-slot:actions>
    @endif
</x-mary-modal>
```

## 🛒 カートアイテム追加
```
操作: POST /cart/items
実装: Livewireアクション addToCart($productId, $quantity, $options)
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: 商品詳細モーダルでオプション選択後
- **目的**: 選択した商品をカートに追加

**Livewire実装**
```php
// Livewireアクション内
public function addToCart($productId, $quantity = 1, $options = [])
{
    try {
        $guestSession = auth('guest')->user();
        $cartItem = CartService::addItem($guestSession, $productId, $quantity, $options);
        
        $this->loadCartItems();
        $this->dispatch('cart-updated', [
            'total' => $this->cartTotal,
            'count' => count($this->cartItems)
        ]);
        
        session()->flash('message', '商品をカートに追加しました');
        
    } catch (Exception $e) {
        session()->flash('error', $e->getMessage());
    }
}
```

## 🛒 カート表示（下からスライドアップ）
```
操作: GET /cart (概念)
実装: Livewireアクション showCart() + Alpine.jsモーダル
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: 下部固定カートタップ時・カート確認時
- **目的**: カート内容の表示と編集（アニメーション付き全画面展開）

**Livewire実装（スライドアップ対応）**
```php
// app/Livewire/Guest/MenuPage.php（メニューページ内でカート表示）
class MenuPage extends Component
{
    public $selectedCategory = null;
    public $cartItems = [];
    public $cartTotal = 0;
    public $showCartModal = false;  // カートモーダル表示フラグ
    
    protected $listeners = [
        'show-cart' => 'showCart',
        'cart-updated' => 'loadCartItems'
    ];
    
    public function showCart()
    {
        $this->loadCartItems();
        $this->showCartModal = true;
    }
    
    public function hideCart()
    {
        $this->showCartModal = false;
    }
    
    public function removeCartItem($itemId)
    {
        CartService::removeItem(auth('guest')->user(), $itemId);
        $this->loadCartItems();
        
        // カートが空になったら自動で閉じる
        if (count($this->cartItems) === 0) {
            $this->showCartModal = false;
        }
    }
    
    public function clearCart()
    {
        CartService::clearCart(auth('guest')->user());
        $this->loadCartItems();
        $this->showCartModal = false;
    }
    
    public function proceedToOrder()
    {
        if (count($this->cartItems) > 0) {
            return redirect()->route('guest.order-confirm');
        }
    }
    
    public function loadCartItems()
    {
        $guestSession = auth('guest')->user();
        $this->cartItems = CartService::getItems($guestSession);
        $this->cartTotal = CartService::getTotal($guestSession);
    }
}
```

**Bladeテンプレート（スライドアップアニメーション）**
```blade
<!-- メニュー画面のメインテンプレート -->
<div class="min-h-screen bg-gray-50">
    <!-- カテゴリタブ・商品一覧... -->
    
    <!-- 下部固定カート -->
    @if(count($cartItems) > 0)
    <div class="fixed bottom-0 left-0 right-0 bg-amber-500 text-white p-4 cursor-pointer z-10" 
         wire:click="showCart">
        <div class="flex justify-between items-center">
            <span>🛒 カート ({{ count($cartItems) }}点)</span>
            <span>¥{{ number_format($cartTotal) }}</span>
        </div>
        <div class="text-center text-sm">▼ タップして注文へ進む ▼</div>
    </div>
    @endif
    
    <!-- カートモーダル（下からスライドアップ） -->
    <div class="fixed inset-0 z-50 {{ $showCartModal ? 'block' : 'hidden' }}"
         x-data="{ show: @entangle('showCartModal') }"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- 背景オーバーレイ -->
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="hideCart"></div>
        
        <!-- カートコンテンツ（下からスライドアップ） -->
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl max-h-[80vh] overflow-hidden"
             x-show="show"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- ハンドルバー -->
            <div class="flex justify-center pt-3 pb-2">
                <div class="w-12 h-1 bg-gray-300 rounded-full"></div>
            </div>
            
            <!-- ヘッダー -->
            <div class="flex justify-between items-center px-6 py-4 border-b">
                <h2 class="text-xl font-bold">カート内容</h2>
                <button wire:click="hideCart" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- カートアイテム一覧 -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                @if(count($cartItems) > 0)
                    @foreach($cartItems as $index => $item)
                    <div class="flex items-center space-x-4 py-4 border-b border-gray-100">
                        <!-- 商品画像 -->
                        <img src="{{ $item['product_image'] ?? '/images/no-image.png' }}" 
                             alt="{{ $item['product_name'] }}" 
                             class="w-16 h-16 rounded-lg object-cover">
                        
                        <!-- 商品情報 -->
                        <div class="flex-1">
                            <h3 class="font-bold">{{ $item['product_name'] }}</h3>
                            
                            <!-- オプション表示 -->
                            @if(isset($item['options']) && count($item['options']) > 0)
                            <div class="text-sm text-gray-600 mt-1">
                                @foreach($item['options'] as $option)
                                <div>{{ $option['name'] }}: {{ $option['option_detail'] }}</div>
                                @endforeach
                            </div>
                            @endif
                            
                            <!-- 価格・数量 -->
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-amber-600 font-bold">¥{{ number_format($item['unit_price']) }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-600">×{{ $item['quantity'] }}</span>
                                    <button wire:click="removeCartItem({{ $index }})" 
                                            class="text-red-500 hover:text-red-700 ml-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-8 text-gray-500">
                        <div class="text-4xl mb-4">🛒</div>
                        <p>カートは空です</p>
                    </div>
                @endif
            </div>
            
            <!-- フッター -->
            @if(count($cartItems) > 0)
            <div class="px-6 py-4 border-t bg-gray-50">
                <!-- 合計金額 -->
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-bold">合計</span>
                    <span class="text-2xl font-bold text-amber-600">¥{{ number_format($cartTotal) }}</span>
                </div>
                
                <!-- アクションボタン -->
                <div class="space-y-2">
                    <button wire:click="proceedToOrder" 
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded-lg">
                        注文する
                    </button>
                    <button wire:click="clearCart" 
                            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded-lg">
                        カートをクリア
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
```

**Alpine.js + TailwindCSS アニメーション仕様**
- **エントランス**: `translate-y-full` → `translate-y-0` （300ms ease-out）
- **エグジット**: `translate-y-0` → `translate-y-full` （200ms ease-in）
- **背景**: フェードイン・アウト（opacity）
- **スワイプ対応**: 下方向スワイプで閉じる
- **ハンドルバー**: iOS風のドラッグ可能表示



## 🗑️ カートアイテム削除
```
操作: DELETE /cart/items/{product_id}
実装: Livewireアクション removeCartItem($itemId)
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: カート表示画面で商品削除ボタンタップ時
- **目的**: 特定の商品をカートから削除

**Livewire実装**
```php
// Livewireアクション内
public function removeCartItem($itemId)
{
    try {
        $guestSession = auth('guest')->user();
        CartService::removeItem($guestSession, $itemId);
        
        $this->loadCartItems();
        
        // カートが空になったら自動で閉じる
        if (count($this->cartItems) === 0) {
            $this->showCartModal = false;
            session()->flash('message', 'カートが空になりました');
        } else {
            $this->dispatch('cart-updated', [
                'total' => $this->cartTotal,
                'count' => count($this->cartItems)
            ]);
            session()->flash('message', '商品を削除しました');
        }
        
    } catch (Exception $e) {
        session()->flash('error', '削除に失敗しました: ' . $e->getMessage());
    }
}
```

**CartService実装**
```php
public static function removeItem($guestSession, $itemId)
{
    $cartKey = "guest_cart:{$guestSession->guest_token}";
    
    // Redisから現在のカートを取得
    $cart = Redis::get($cartKey);
    if (!$cart) {
        throw new Exception('カートが見つかりません');
    }
    
    $cartData = json_decode($cart, true);
    
    // アイテムを削除
    if (isset($cartData['items'][$itemId])) {
        $removedItem = $cartData['items'][$itemId];
        unset($cartData['items'][$itemId]);
        
        // カート合計を再計算
        $cartData['total'] = array_sum(array_column($cartData['items'], 'subtotal'));
        
        // Redisに保存
        Redis::setex($cartKey, 1800, json_encode($cartData));
        
        // ログ記録
        CartLog::create([
            'guest_token' => $guestSession->guest_token,
            'session_id' => $guestSession->session_id,
            'action' => 'remove',
            'product_id' => $removedItem['product_id'],
            'quantity' => $removedItem['quantity'],
            'unit_price' => $removedItem['unit_price'],
            'cart_total' => $cartData['total'],
            'is_success' => true
        ]);
        
        return true;
    }
    
    throw new Exception('指定された商品がカートに見つかりません');
}
```

## 🗑️ カートクリア
```
操作: DELETE /cart
実装: Livewireアクション clearCart()
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: カート表示画面で「カートをクリア」ボタンタップ時
- **目的**: カート内の全商品を一括削除

**Livewire実装**
```php
// Livewireアクション内
public function clearCart()
{
    try {
        $guestSession = auth('guest')->user();
        $itemCount = count($this->cartItems);
        
        CartService::clearCart($guestSession);
        
        $this->loadCartItems();
        $this->showCartModal = false;
        
        $this->dispatch('cart-cleared');
        session()->flash('message', "{$itemCount}点の商品をクリアしました");
        
    } catch (Exception $e) {
        session()->flash('error', 'カートのクリアに失敗しました: ' . $e->getMessage());
    }
}
```

**CartService実装**
```php
public static function clearCart($guestSession)
{
    $cartKey = "guest_cart:{$guestSession->guest_token}";
    
    // 削除前のカートを取得（ログ用）
    $cart = Redis::get($cartKey);
    $oldCartData = $cart ? json_decode($cart, true) : null;
    
    // Redisからカートを削除
    Redis::del($cartKey);
    
    // ログ記録
    CartLog::create([
        'guest_token' => $guestSession->guest_token,
        'session_id' => $guestSession->session_id,
        'action' => 'clear',
        'product_id' => null,
        'quantity' => $oldCartData ? count($oldCartData['items']) : 0,
        'unit_price' => null,
        'cart_total' => 0,
        'is_success' => true,
        'metadata' => $oldCartData ? json_encode(['cleared_items' => $oldCartData['items']]) : null
    ]);
    
    return true;
}
```

**UIデザイン（確認ダイアログ）**
```blade
<!-- カートクリア確認モーダル -->
<div x-data="{ showClearConfirm: false }">
    <button @click="showClearConfirm = true" 
            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded-lg">
        カートをクリア
    </button>
    
    <!-- 確認ダイアログ -->
    <div x-show="showClearConfirm" class="fixed inset-0 z-60 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-50" @click="showClearConfirm = false"></div>
        <div class="relative bg-white rounded-lg p-6 max-w-sm mx-4">
            <h3 class="text-lg font-bold mb-4">カートをクリアしますか？</h3>
            <p class="text-gray-600 mb-6">カート内の全ての商品が削除されます。この操作は取り消せません。</p>
            <div class="flex space-x-2">
                <button @click="showClearConfirm = false" 
                        class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded-lg">
                    キャンセル
                </button>
                <button @click="showClearConfirm = false; $wire.clearCart()" 
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">
                    クリア
                </button>
            </div>
        </div>
    </div>
</div>
```

## 📝 注文作成
```
操作: POST /orders
実装: Livewireアクション createOrder()
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: カート確認後の注文確定時
- **目的**: 注文をデータベースに記録し、POSに通知

**Livewire実装**
```php
public function createOrder()
{
    try {
        $guestSession = auth('guest')->user();
        $order = OrderService::createFromCart($guestSession);
        
        // POS同期用の変更ログ作成
        ChangeLogService::logOrderCreated($order);
        
        // カートクリア
        CartService::clearCart($guestSession);
        
        session()->flash('success', '注文を受け付けました');
        return redirect()->route('guest.order-history');
        
    } catch (Exception $e) {
        session()->flash('error', '注文に失敗しました: ' . $e->getMessage());
    }
}
```

## 📋 注文履歴表示
```
操作: GET /order-history
実装: Livewireコンポーネント OrderHistory::class
```

**呼び出しタイミング**
- **誰が**: お客様のスマートフォン
- **いつ**: 注文後・注文状況確認時・会計前確認時
- **目的**: 席全体の注文履歴表示（動物アイコンで個人識別）

**Livewire実装**
```php
// app/Livewire/Guest/OrderHistory.php
class OrderHistory extends Component
{
    public $orders = [];
    public $sessionInfo = [];
    public $totalAmount = 0;
    
    protected $listeners = ['refresh-orders' => 'loadOrders'];
    
    public function mount()
    {
        $this->loadOrders();
    }
    
    public function loadOrders()
    {
        $guestSession = auth('guest')->user();
        $sessionId = $guestSession->session_id;
        
        $this->orders = Order::where('session_id', $sessionId)
            ->with(['items', 'guestSession'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $this->totalAmount = $this->orders->sum('total_amount');
        $this->sessionInfo = Session::find($sessionId);
    }
    
    public function render()
    {
        return view('livewire.guest.order-history');
    }
}
```

**Bladeテンプレート例（最終仕様）**
```blade
<div class="space-y-6">
    @foreach($orders as $order)
    <!-- 注文グループ（区切り線のみ） -->
    <div class="border-b border-gray-200 pb-4">
        <div class="flex items-center space-x-2 mb-3">
            <span class="text-2xl">{{ $order->guestSession->icon ?? '🐶' }}</span>
            <span class="font-medium">{{ $order->created_at->format('H:i') }}に注文</span>
        </div>
        
        <!-- 商品一覧（商品ごとにステータス表示） -->
        <div class="space-y-2 ml-8">
            @foreach($order->items as $item)
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span>{{ $item->product_name }} ×{{ $item->quantity }}</span>
                        <span class="font-bold">¥{{ number_format($item->unit_price * $item->quantity) }}</span>
                        <x-mary-badge :value="$item->status" class="text-xs" />
                    </div>
                    
                    <!-- オプション表示 -->
                    @if($item->options->count() > 0)
                    <div class="text-sm text-gray-600 ml-4 mt-1">
                        @foreach($item->options as $option)
                        <div>• {{ $option->name }}: {{ $option->option_detail }}
                            @if($option->price > 0) (+¥{{ number_format($option->price) }}) @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
    
    <!-- 合計金額エリア -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <div class="flex justify-between items-center mb-2">
            <span class="text-lg font-bold">合計</span>
            <span class="text-2xl font-bold text-amber-600">¥{{ number_format($totalAmount) }}</span>
        </div>
        <div class="flex justify-between text-gray-600 text-sm">
            <span>1人あたり</span>
            <span>¥{{ number_format($totalAmount / ($sessionInfo->customer_count ?? 1)) }}（{{ $sessionInfo->customer_count }}人）</span>
        </div>
    </div>
    
    <!-- 会計ボタン -->
    <x-mary-button wire:click="proceedToPayment" 
                   class="w-full bg-amber-500 hover:bg-amber-600" 
                   size="lg">
        会計する（¥{{ number_format($totalAmount) }}）
    </x-mary-button>
</div>
```

**商品ステータス定義**
```php
// OrderItem モデルでのステータス管理
enum OrderItemStatus: string 
{
    case PENDING = 'pending';     // 📋受付中
    case COOKING = 'cooking';     // 🍳調理中  
    case COMPLETED = 'completed'; // ✅完了
}
```

### 5.3 POS連携API



## 🔐 POSログイン認証
```
操作: POST /api/v1/pos/auth/login
実装: APIコントローラー PosAuthController::login()
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: POS起動時の自動ログイン処理
- **目的**: Bearer Token取得によるAPI認証基盤の確立

**POS自動ログインフロー**
```
1. POS起動
2. 設定ファイルからメール・パスワード読み込み
3. POST /api/v1/pos/auth/login（自動実行）
4. Bearer Token取得（24時間TTL）
5. 以降のAPI呼び出しで自動的にBearer Token使用
```

**リクエスト**
```json
{
  "email": "pos@example.com",              // 必須: POSシステム用メールアドレス
  "password": "pos_secure_password",       // 必須: POSシステム用パスワード
  "store_id": "store_001"                 // 必須: 店舗識別ID
}
```

**リクエストパラメータ**

- `email` (string, 必須): POSシステム専用のログインメールアドレス
- `password` (string, 必須): POSシステム専用のログインパスワード
- `store_id` (string, 必須): 店舗を識別するためのID


**レスポンス**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...", // 必須: JWT Bearer Token（24時間TTL）
    "token_type": "Bearer",                // 必須: トークンタイプ（常に"Bearer"）
    "expires_in": 86400,                    // 必須: トークン有効期限（秒単位、24時間=86400秒）
    "user": {                               // 必須: 認証されたユーザー情報
      "id": 1,                             // 必須: ユーザーID（数値）
      "email": "pos@example.com",          // 必須: ログインメールアドレス
      "role": "pos_system",                // 必須: ユーザーロール（pos_system固定）
      "store_id": "store_001"              // 必須: 店舗ID
    }
  },
  "message": "POSシステム認証成功"            // 必須: 成功メッセージ（日本語）
}
```


**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `data.access_token` (string, 必須): 以降のAPI呼び出しで使用するBearer Token
- `data.token_type` (string, 必須): トークンの種類（常に"Bearer"）
- `data.expires_in` (integer, 必須): トークンの有効期限（秒、86400=24時間）
- `data.user` (object, 必須): 認証されたPOSユーザーの詳細情報
- `message` (string, 必須): 人間可読な成功メッセージ



## 🔄 POSトークン更新
```
操作: POST /api/v1/pos/auth/refresh
実装: APIコントローラー PosAuthController::refresh()
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: トークン有効期限切れ前（22時間後）の自動更新
- **目的**: Bearer Tokenの無期限継続利用

**リクエスト**
```json
{
  "refresh_token": "current_bearer_token"  // 必須: 現在のBearer Token
}
```

**リクエストパラメータ**
- `refresh_token` (string, 必須): 現在保持しているBearer Token（期限切れ前のもの）

**レスポンス**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...", // 必須: 新しいJWT Bearer Token（24時間TTL）
    "token_type": "Bearer",                // 必須: トークンタイプ（常に"Bearer"）
    "expires_in": 86400                     // 必須: 新しいトークンの有効期限（秒単位）
  },
  "message": "トークン更新完了"               // 必須: 更新完了メッセージ（日本語）
}
```

**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `data.access_token` (string, 必須): 新しく発行されたBearer Token
- `data.token_type` (string, 必須): トークンの種類（常に"Bearer"）
- `data.expires_in` (integer, 必須): 新しいトークンの有効期限（秒、86400=24時間）
- `message` (string, 必須): 人間可読な更新完了メッセージ


## 🔓 POSログアウト
```
操作: POST /api/v1/pos/auth/logout
実装: APIコントローラー PosAuthController::logout()
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: POS終了時・管理者による明示的ログアウト
- **目的**: Bearer Tokenの無効化・セキュリティ確保

**リクエスト**
```json
{
  "force_logout": false                    // オプション: 強制ログアウトフラグ（デフォルト: false）
}
```

**リクエストパラメータ**
- `force_logout` (boolean, オプション): 強制ログアウトを実行するかのフラグ。true=他のセッションも無効化、false=現在のトークンのみ無効化（デフォルト: false）

**レスポンス**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "message": "POSログアウト完了"             // 必須: ログアウト完了メッセージ（日本語）
}
```

**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `message` (string, 必須): 人間可読なログアウト完了メッセージ


## 🔄 双方向ヘルスチェック同期
```
操作: POST /api/v1/pos/health/sync
実装: APIコントローラー PosHealthController::sync()
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 30秒間隔での定期通信
- **目的**: POS⇔Web間の双方向ヘルスチェック・状態同期

**POS→Web ヘルスチェック送信と Web→POS ステータス応答を1つのAPIで実現**

**リクエスト**
```json
{
  "pos_status": {                          // 必須: POSシステムの現在ステータス
    "status": "healthy",                   // 必須: POS全体ステータス（healthy/warning/error）
    "last_sync": "2024-01-01T14:30:00+09:00", // 必須: 最終同期時刻（ISO 8601形式）
    "services": {                          // 必須: 各サービスの状態
      "database": "up",                    // 必須: POSデータベース状態（up/down）
      "firebird": "up",                   // 必須: FireBirdデータベース状態（up/down）
      "handy_terminals": "connected"       // 必須: ハンディ端末接続状態（connected/disconnected）
    }
  },
  "request_web_status": true               // 必須: Webシステムのステータスを要求するかのフラグ（常にtrue）
}
```

**リクエストパラメータ**
- `pos_status` (object, 必須): POSシステムの健康状態情報
- `pos_status.status` (string, 必須): POS全体ステータス（"healthy", "warning", "error"）
- `pos_status.last_sync` (string, 必須): 最終同期時刻（ISO 8601形式）
- `pos_status.services` (object, 必須): 各サービスの個別状態
- `request_web_status` (boolean, 必須): Webシステムのステータス情報を要求するかのフラグ（常にtrue）

**レスポンス（双方向情報を含む）**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "data": {
    "web_status": {                        // 必須: Webシステムの現在ステータス
      "status": "healthy",                 // 必須: Web全体ステータス（healthy/warning/error）
      "timestamp": "2024-01-01T14:30:00+09:00", // 必須: ステータス生成時刻（ISO 8601形式）
      "services": {                        // 必須: Webシステム各サービスの状態
        "database": "up",                  // 必須: Laravelデータベース状態（up/down）
        "redis": "up",                     // 必須: Redisキャッシュ状態（up/down）
        "queue": "up"                      // 必須: キューシステム状態（up/down）
      }
    },
    "pos_status_received": true,           // 必須: POSステータスを正常受信したかのフラグ
    "sync_required": false,                // 必須: 同期処理が必要かのフラグ
    "actions": {                           // 必須: POSが実行すべきアクションの指示
      "check_pending_orders": false,      // 必須: 保留中注文をチェックすべきか
      "sync_menu_changes": false          // 必須: メニュー変更を同期すべきか
    }
  },
  "message": "双方向ヘルスチェック完了"     // 必須: 人間可読な完了メッセージ（日本語）
}
```

**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `data.web_status` (object, 必須): Webシステムの健康状態情報
- `data.pos_status_received` (boolean, 必須): POSからのステータス情報を正常受信したか
- `data.sync_required` (boolean, 必須): データ同期が必要かのフラグ
- `data.actions` (object, 必須): POSが実行すべきアクションの指示一覧
- `message` (string, 必須): 人間可読な処理完了メッセージ


**統合されたフロー**
```
1. POS → Web: POSステータス送信 + Webステータス要求
2. Web処理: POSステータス受信 + Webステータス生成 + 同期要否判定
3. Web → POS: Webステータス応答 + 同期指示 + 必要アクション通知
4. 30秒後に再実行（単一エンドポイントでの効率的な双方向通信）
```

**効果**
- API呼び出し回数の削減（2回→1回）
- 通信効率の向上
- 双方向状態の整合性確保
- 障害検知の高速化

## 🆕 セッション新規作成（POS側）
```http
POST /api/v1/pos/sessions
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: QRコード発行時・席案内時
- **目的**: 新しい席セッションの作成

**リクエスト**
```json
{
  "session_id": "SESSION_POS_FIXED_a7b3c9d4e5f61829_12",     // 必須: 一意なセッションID（POS生成）
  "table_number": "12",                  // 必須: テーブル番号（文字列）
  "qr_mode": "dynamic",                  // 必須: QRコード運用モード（fixed/dynamic）
  "time_limit_hours": 3                  // オプション: 有効期限（時間単位、dynamicモードのみ）
}
```

**リクエストパラメータ**
- `session_id` (string, 必須): POSシステムで生成された一意なセッションID
- `table_number` (string, 必須): 対象テーブルの番号（数値でも文字列として扱う）
- `qr_mode` (string, 必須): QRコードの運用モード。"fixed"=固定モード（無期限）、"dynamic"=都度発行モード（時間制限）
- `time_limit_hours` (integer, オプション): セッションの有効期限（時間単位）。dynamicモードのみ指定、fixedモードでは無視

**qr_mode パラメータ詳細**

| 値 | 名称 | 有効期限設定 | 使用場面 | 説明 |
|---|---|---|---|---|
| `fixed` | 固定QRモード | `expires_at: null` | テーブル固定QR | QRコードを印刷してテーブルに貼り付け、長期利用 |
| `dynamic` | 都度発行モード | `expires_at: null` | レジ発行QR | 席案内時にレシートプリンターから発行、無期限がデフォルト |

**qr_mode別の運用フロー**

**固定QRモード（`"qr_mode": "fixed"`）**
```
1. 店舗準備: 各テーブルにQRコードを印刷・設置
2. POS設定: session_idをテーブル番号ベースで固定生成
3. お客様利用: QRコード読み取り→即座にメニュー画面
4. 有効期限: なし（expires_at: null）
5. セッション管理: テーブル単位で永続的に利用可能
```

**都度発行モード（`"qr_mode": "dynamic"`）**
```
1. 席案内: 店員がPOSで席番号入力
2. QR生成: session_idに現在時刻を含めて一意生成  
3. 印刷: レシートプリンターからQRコード印刷
4. 有効期限: 3時間（expires_at設定）
5. セッション管理: 時間経過で自動無効化
```

**技術仕様**

**session_id生成ルール（セキュリティ強化版）**

推測可能なIDは不正アクセスの原因となるため、暗号学的に安全なID生成を実装します。

```php
// 固定QRモード - 暗号学的に安全なID生成
$hash = hash('sha256', $tableNumber . config('app.key') . 'FIXED_MODE');
$sessionId = "SESSION_POS_FIXED_" . substr($hash, 0, 16) . "_{$tableNumber}";
// 例: "SESSION_POS_FIXED_a7b3c9d4e5f61829_12"

// 都度発行モード - タイムスタンプ + ランダム要素
$timestamp = time();
$random = bin2hex(random_bytes(8));
$sessionId = "SESSION_POS_TEMP_{$tableNumber}_{$timestamp}_{$random}";
// 例: "SESSION_POS_TEMP_12_1692345678_a1b2c3d4e5f6"
```

**Delphi 2007対応実装例**
```pascal
unit SessionIDGenerator;

interface

uses
  SysUtils, DateUtils;

type
  TSessionIDGenerator = class
  public
    class function GenerateFixedSessionID(TableNumber: Integer): string;
    class function GenerateTempSessionID(TableNumber: Integer): string;
  private
    class function SimpleHash(Input: string): string;
    class function GenerateRandomHex(Length: Integer): string;
  end;

implementation

class function TSessionIDGenerator.GenerateFixedSessionID(TableNumber: Integer): string;
var
  Input, Hash: string;
begin
  Input := IntToStr(TableNumber) + 'FIXED_SECRET_KEY_2024' + 'FIXED_MODE';
  Hash := SimpleHash(Input);
  Result := 'SESSION_POS_FIXED_' + Copy(Hash, 1, 16) + '_' + IntToStr(TableNumber);
end;

class function TSessionIDGenerator.GenerateTempSessionID(TableNumber: Integer): string;
var
  Timestamp: Int64;
  RandomHex: string;
begin
  Timestamp := DateTimeToUnix(Now);
  RandomHex := GenerateRandomHex(16);
  Result := 'SESSION_POS_TEMP_' + IntToStr(TableNumber) + '_' + 
            IntToStr(Timestamp) + '_' + RandomHex;
end;

class function TSessionIDGenerator.SimpleHash(Input: string): string;
var
  i: Integer;
  Hash: Cardinal;
  C: Char;
begin
  Hash := 5381;
  for i := 1 to Length(Input) do
  begin
    C := Input[i];
    Hash := ((Hash shl 5) + Hash) + Ord(C);
  end;
  Result := IntToHex(Hash, 8) + IntToHex(Hash xor $12345678, 8);
end;

class function TSessionIDGenerator.GenerateRandomHex(Length: Integer): string;
var
  i: Integer;
begin
  Randomize;
  Result := '';
  for i := 1 to Length do
    Result := Result + IntToHex(Random(16), 1);
end;

end.
```

**使用例**
```pascal
// 固定QRモード
sessionID := TSessionIDGenerator.GenerateFixedSessionID(12);
// 例: "SESSION_POS_FIXED_a7b3c9d4e5f61829_12"

// 都度発行モード
sessionID := TSessionIDGenerator.GenerateTempSessionID(12);
// 例: "SESSION_POS_TEMP_12_1692345678_a1b2c3d4e5f67890"
```

**店舗設定での選択**
- **小規模店舗**: 固定QRモード推奨（運用コスト削減）
- **大規模店舗**: 都度発行モード推奨（セキュリティ・回転率向上）
- **混在運用**: テーブル種別によって使い分け可能

**レスポンス**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "data": {
    "session_id": "SESSION_POS_FIXED_a7b3c9d4e5f61829_12",     // 必須: 作成されたセッションID（リクエストと同じ）
    "qr_url": "https://example.com/order?session=SESSION_POS_FIXED_a7b3c9d4e5f61829_12", // 必須: お客様がアクセスするQRコードURL
    "created_at": "2024-01-01T14:00:00+09:00" // 必須: セッション作成時刻（ISO 8601形式）
  },
  "message": "セッションが作成されました"   // 必須: 人間可読な作成完了メッセージ（日本語）
}
```

**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `data.session_id` (string, 必須): 作成されたセッションID（リクエストで指定したものと同じ）
- `data.qr_url` (string, 必須): お客様がスマホでアクセスするためのQRコードURL
- `data.created_at` (string, 必須): セッション作成日時（ISO 8601形式）
- `message` (string, 必須): 人間可読な作成完了メッセージ


## 🔄 変更履歴取得（ポーリング）
```http
GET /api/v1/pos/changes
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 5秒間隔でのポーリング（デフォルト、設定変更可能）
- **目的**: Web側で発生した新しい注文の取得（is_synced=falseのレコードのみ）

**パラメータ**
```
?since=2024-01-01T14:30:00+09:00&limit=100
```

**クエリパラメータ**
- `since` (string, オプション): 取得開始日時（ISO 8601形式）。指定した時刻以降の変更のみ取得。未指定の場合は直近の変更を取得
- `limit` (integer, オプション): 取得件数の上限（デフォルト: 100、最大: 1000）

**レスポンス**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "data": {
    "changes": [                            // 必須: 変更履歴アイテムの配列
      {
        "id": 456,                          // 必須: 変更履歴のID（数値、一意）
        "type": "order_created",             // 必須: 変更タイプ（order_created/order_updated/order_cancelled）
        "order_id": 123,                   // 必須: 関連する注文ID（数値）
        "session_id": "SESSION_POS_FIXED_a7b3c9d4e5f61829_12", // 必須: 対象セッションID
        "data": {                           // 必須: 変更内容の詳細データ
          "table_number": "12",             // 必須: テーブル番号
          "total_amount": 1600,              // 必須: 注文合計金額（数値）
          "items": [                        // 必須: 注文商品一覧
            {
              "product_id": 1,              // 必須: 商品ID（数値）
              "quantity": 2,                // 必須: 数量（数値）
              "options": [                  // オプション: 選択されたオプション情報の配列
                {
                  "option_id": 5,           // 必須: オプションID（数値）
                  "option_detail_id": 12,   // 必須: オプション詳細ID（選択された値）
                  "product_id": 45,         // 必須: 選択された商品ID（option_detailに紐づく）
                  "price": 100,             // 必須: オプション価格（数値、マイナス可）
                  "name": "チャーシュー増量" // 必須: オプション名（表示用）
                }
              ]
            }
          ]
        },
        "created_at": "2024-01-01T14:35:00+09:00" // 必須: 変更発生時刻（ISO 8601形式）
      }
    ],
    "has_more": false,                     // 必須: さらに取得できるデータがあるかのフラグ
    "next_poll_at": "2024-01-01T14:36:00+09:00" // 必須: 次回ポーリング推奨時刻（ISO 8601形式）
  }
}
```

**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `data.changes` (array, 必須): 変更履歴アイテムの配列（空の場合もあり）
- `data.has_more` (boolean, 必須): さらに取得可能なデータがあるかのフラグ
- `data.next_poll_at` (string, 必須): 次回ポーリングの推奨時刻（ISO 8601形式）


## ✅ 同期完了通知
```http
POST /api/v1/pos/changes/sync
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 変更履歴の処理完了時
- **目的**: change_logsテーブルのis_syncedフラグをtrueに更新し、次回ポーリング時に重複取得を防止

**リクエスト**
```json
{
  "change_ids": [456, 457, 458],           // 必須: 処理完了した変更履歴IDの配列
  "sync_status": "completed"               // 必須: 同期ステータス（completed/failed）
}
```

**リクエストパラメータ**
- `change_ids` (array, 必須): POS側で処理を完了した変更履歴IDの配列（数値配列）
- `sync_status` (string, 必須): 同期処理の結果ステータス。"completed"=成功、"failed"=失敗

**レスポンス**
```json
{
  "success": true,                         // 必須: API実行結果のステータス（boolean）
  "message": "同期完了を記録しました"     // 必須: 人間可読な処理完了メッセージ（日本語）
}
```

**レスポンスパラメータ**
- `success` (boolean, 必須): API実行結果（true=成功）
- `message` (string, 必須): 人間可読な同期完了メッセージ

### 5.4 POS専用API（商品データ操作）


## 🔄 同期方式の設計思想

**現在の実装方針**
- **通常時**: 各CRUD API（作成・更新・削除）により、Web-POS間の同期は自動的に維持される
- **緊急時**: 全件取得APIによる同期確認・整合性チェック（データ不整合時の最終手段）

**将来的な最適化の可能性**
```
現在の方式：全件データ取得（シンプル・確実）
  ↓
将来の改善案：
1. 差分同期（modified_since パラメータ使用）
2. チェックサム比較（ハッシュ値による軽量チェック）  
3. 変更ログベース同期（change_logsテーブル活用）
4. リアルタイム同期（WebSocket/Server-Sent Events）
```

**実装上の注意**
- 現在の全件取得方式は**開発・運用の簡素化**を優先した設計
- 店舗規模拡大時は上記最適化手法への移行を検討
- APIインターフェースの後方互換性は維持予定


## 📦 POS商品同期検証
```http
POST /api/v1/pos/products/verify
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 商品マスター同期確認時（起動時・定期実行）
- **目的**: **WebとPOSの商品全件同期検証** - POSから全レコードを送信し、Web側と比較して不整合を検出

**技術仕様**
- **全レコード送信**: POSから全商品データを送信
- **差分検出**: Web側でPOSデータと比較し、不整合を検出
- **レスポンス**: 同期状態と不整合がある場合はその詳細を返却

**リクエスト**
```json
{
  "products": [
    {
      "id": 1,
      "name": "醤油ラーメン",
      "description": "あっさりとした醤油ベースのラーメン",
      "price": 800,
      "menu_number": "R001",
      "category_id": 1,
      "availability_status": "available",
      "display_order": 1,
      "is_active": true,
      "deleted_at": null
    },
    {
      "id": 2,
      "name": "味噌ラーメン",
      "description": "濃厚な味噌ベースのラーメン",
      "price": 900,
      "menu_number": "R002",
      "category_id": 1,
      "availability_status": "available",
      "display_order": 2,
      "is_active": true,
      "deleted_at": null
    }
  ]
}
```

**レスポンス（全て同期済み）**
```json
{
  "success": true,
  "data": {
    "is_synced": true,
    "pos_count": 2,
    "web_count": 2,
    "unsynced_pos_ids": [],
    "unsynced_web_ids": [],
    "mismatched_items": []
  },
  "message": "全ての商品が同期されています"
}
```

**レスポンス（不整合あり）**
```json
{
  "success": true,
  "data": {
    "is_synced": false,
    "pos_count": 3,
    "web_count": 2,
    "unsynced_pos_ids": [3],
    "unsynced_web_ids": [],
    "mismatched_items": [
      {
        "id": 1,
        "field": "price",
        "pos_value": 850,
        "web_value": 800
      }
    ]
  },
  "message": "同期が必要な項目があります"
}
```

**リクエストフィールド説明**
- `products`: 商品配列（必須、配列）
  - `id`: 商品ID（必須、数値）
  - `name`: 商品名（必須、文字列）
  - `description`: 商品説明（任意、文字列）
  - `price`: 価格（必須、数値、マイナス値可）
  - `menu_number`: メニュー番号（必須、文字列）
  - `category_id`: カテゴリID（必須、数値）
  - `availability_status`: 提供状態（必須、列挙型: available/sold_out/not_arrived/preparing）
  - `display_order`: 表示順（必須、数値）
  - `is_active`: 有効フラグ（必須、真偽値）
  - `deleted_at`: 削除日時（任意、日時またはnull）

**レスポンスフィールド説明**
- `is_synced`: 完全同期状態（真偽値）
- `pos_count`: POS側のレコード数（数値）
- `web_count`: Web側のレコード数（数値）
- `unsynced_pos_ids`: POSにのみ存在するID配列（配列）
- `unsynced_web_ids`: Webにのみ存在するID配列（配列）
- `mismatched_items`: 不整合項目の詳細（配列）
  - `id`: 対象ID（数値）
  - `field`: 不整合フィールド名（文字列）
  - `pos_value`: POS側の値
  - `web_value`: Web側の値


## 📦 POS商品作成
```http
POST /api/v1/pos/products
```

**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 新商品登録時
- **目的**: **商品登録時の同期** - POS側で登録した商品をWebシステムに即座に反映

**リクエスト**
```json
{
  "name": "塩ラーメン",
  "description": "さっぱりとした塩ベースのラーメン",
  "price": 850,
  "menu_number": "R002",
  "category_id": 1,
  "availability_status": "available",
  "image_url": "https://example.com/images/shio_ramen.jpg"
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "塩ラーメン",
    "price": 850,
    "menu_number": "R002"
  },
  "message": "商品が作成されました"
}
```

## 📝 POS商品更新
```http
PUT /api/v1/pos/products/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 商品情報更新時、商品提供状態変更時
- **目的**: **商品変更時の同期** - POS側での商品情報更新をWebシステムに即座に反映（価格、在庫状況、availability_status等）

**技術仕様**
- **全カラム送信方式**: POSから全てのカラムデータを送信、Web側で差分を検出して変更箇所のみ更新
- **変更検出**: LaravelのisDirty()メソッドで変更有無を判定
- **変更記録**: 変更があった場合のみchange_logsテーブルに記録

**リクエスト**
```json
{
  "name": "醤油ラーメン",
  "description": "あっさりとした醤油ベースのラーメン",
  "price": 800,
  "menu_number": "R001",
  "category_id": 1,
  "availability_status": "available",
  "display_order": 1,
  "is_active": true,
  "is_takeout_available": true,
  "alcohol_content": 0,
  "allergen_info": "小麦、卵、大豆",
  "nutritional_info": {
    "calories": 450,
    "protein": 15,
    "fat": 12,
    "carbohydrate": 65
  },
  "image_url": "https://example.com/images/shoyu_ramen.jpg",
  "preparation_time": 10,
  "max_quantity_per_order": 10,
  "min_quantity_per_order": 1,
  "translations": {
    "en": {
      "name": "Soy Sauce Ramen",
      "description": "Light soy sauce based ramen"
    },
    "zh-TW": {
      "name": "醬油拉麵",
      "description": "清爽的醬油湯底拉麵"
    },
    "zh-CN": {
      "name": "酱油拉面",
      "description": "清爽的酱油汤底拉面"
    },
    "ko": {
      "name": "간장 라멘",
      "description": "담백한 간장 베이스 라멘"
    }
  }
}
```

**フィールド説明**
- `name`: 商品名（必須、文字列、最大100文字）
- `description`: 商品説明（必須、文字列、最大500文字）
- `price`: 価格（必須、数値、マイナス値可）
- `menu_number`: メニュー番号（必須、文字列、店舗内でユニーク）
- `category_id`: カテゴリID（必須、数値、存在するカテゴリのID）
- `availability_status`: 提供状態（必須、列挙型: available/sold_out/not_arrived/preparing）
- `display_order`: 表示順（必須、数値）
- `is_active`: 有効フラグ（必須、真偽値）
- `is_takeout_available`: テイクアウト可能（必須、真偽値）
- `alcohol_content`: アルコール度数（必須、数値、0の場合はノンアルコール）
- `allergen_info`: アレルゲン情報（任意、文字列）
- `nutritional_info`: 栄養成分情報（任意、オブジェクト）
- `image_url`: 商品画像URL（任意、文字列）
- `preparation_time`: 調理時間（分）（必須、数値）
- `max_quantity_per_order`: 1注文あたりの最大数量（必須、数値）
- `min_quantity_per_order`: 1注文あたりの最小数量（必須、数値）
- `translations`: 多言語翻訳（必須、オブジェクト）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "醤油ラーメン（リニューアル）",
    "description": "コクのある醤油ベースのラーメン",
    "price": 900,
    "menu_number": "R001",
    "category_id": 1,
    "availability_status": "sold_out",
    "image_url": "https://example.com/images/shoyu_ramen_v2.jpg",
    "updated_at": "2024-01-15T10:30:00Z"
  },
  "message": "商品が更新されました"
}
```

## 🗑️ POS商品削除
```http
DELETE /api/v1/pos/products/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 商品廃止時、メニュー整理時
- **目的**: **商品削除時の同期** - POS側で削除した商品をWebシステムからも除去し、両システムの整合性を保持

**パラメータ**
- `id`: 削除する商品のID（必須、数値）

**レスポンス（成功）**
```json
{
  "success": true,
  "message": "商品が削除されました",
  "data": {
    "deleted_id": 1,
    "deleted_at": "2024-01-15T10:30:00Z"
  }
}
```

**レスポンス（削除失敗）**
```json
{
  "success": false,
  "error": {
    "code": "PRODUCT_IN_USE",
    "message": "この商品は注文で使用中のため削除できません"
  }
}
```

## 📦 POSカテゴリ同期検証
```http
POST /api/v1/pos/categories/verify
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: カテゴリマスター同期確認時（起動時・定期実行）
- **目的**: **WebとPOSのカテゴリ全件同期検証** - POSから全レコードを送信し、Web側と比較して不整合を検出

**技術仕様**
- **全レコード送信**: POSから全カテゴリデータを送信
- **差分検出**: Web側でPOSデータと比較し、不整合を検出
- **レスポンス**: 同期状態と不整合がある場合はその詳細を返却

**リクエスト**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "ラーメン",
      "description": "各種ラーメンメニュー",
      "display_order": 1,
      "is_active": true,
      "parent_category_id": null,
      "deleted_at": null
    },
    {
      "id": 2,
      "name": "サイドメニュー",
      "description": "餃子、チャーハンなど",
      "display_order": 2,
      "is_active": true,
      "parent_category_id": null,
      "deleted_at": null
    }
  ]
}
```

**レスポンス（全て同期済み）**
```json
{
  "success": true,
  "data": {
    "is_synced": true,
    "pos_count": 2,
    "web_count": 2,
    "unsynced_pos_ids": [],
    "unsynced_web_ids": [],
    "mismatched_items": []
  },
  "message": "全てのカテゴリが同期されています"
}
```

**レスポンス（不整合あり）**
```json
{
  "success": true,
  "data": {
    "is_synced": false,
    "pos_count": 3,
    "web_count": 2,
    "unsynced_pos_ids": [3],
    "unsynced_web_ids": [],
    "mismatched_items": [
      {
        "id": 1,
        "field": "display_order",
        "pos_value": 2,
        "web_value": 1
      }
    ]
  },
  "message": "同期が必要な項目があります"
}
```

**リクエストフィールド説明**
- `categories`: カテゴリ配列（必須、配列）
  - `id`: カテゴリID（必須、数値）
  - `name`: カテゴリ名（必須、文字列）
  - `description`: カテゴリ説明（任意、文字列）
  - `display_order`: 表示順（必須、数値）
  - `is_active`: 有効フラグ（必須、真偽値）
  - `parent_category_id`: 親カテゴリID（任意、数値またはnull）
  - `deleted_at`: 削除日時（任意、日時またはnull）

**レスポンスフィールド説明**
- `is_synced`: 完全同期状態（真偽値）
- `pos_count`: POS側のレコード数（数値）
- `web_count`: Web側のレコード数（数値）
- `unsynced_pos_ids`: POSにのみ存在するID配列（配列）
- `unsynced_web_ids`: Webにのみ存在するID配列（配列）
- `mismatched_items`: 不整合項目の詳細（配列）


## 📁 カテゴリ作成
```http
POST /api/v1/pos/categories
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 新カテゴリ追加時
- **目的**: **カテゴリ登録時の同期** - POS側で追加したカテゴリをWebシステムに即座に反映

**リクエスト**
```json
{
  "name": "ドリンク",
  "display_order": 3,
  "translations": {
    "en": "Drinks",
    "zh-TW": "飲料",
    "zh-CN": "饮料",
    "ko": "음료"
  }
}
```

**フィールド説明**
- `name`: カテゴリ名（必須、文字列、最大50文字）
- `display_order`: 表示順（必須、数値、正の整数）
- `translations`: 多言語翻訳（任意、オブジェクト）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "ドリンク",
    "display_order": 3,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "products_count": 0,
    "translations": {
      "en": "Drinks",
      "zh-TW": "飲料",
      "zh-CN": "饮料",
      "ko": "음료"
    }
  },
  "message": "カテゴリが作成されました"
}
```


## 📝 カテゴリ更新
```http
PUT /api/v1/pos/categories/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: カテゴリ情報更新時、表示順変更時
- **目的**: **カテゴリ更新時の同期** - POS側でのカテゴリ情報変更をWebシステムに即座に反映

**技術仕様**
- **全カラム送信方式**: POSから全てのカラムデータを送信、Web側で差分を検出して変更箇所のみ更新
- **変更検出**: LaravelのisDirty()メソッドで変更有無を判定
- **変更記録**: 変更があった場合のみchange_logsテーブルに記録

**リクエスト**
```json
{
  "name": "ラーメン",
  "description": "各種ラーメンメニュー",
  "display_order": 1,
  "is_active": true,
  "icon": "🍜",
  "color_code": "#FF6B35",
  "parent_category_id": null,
  "is_featured": true,
  "available_time_start": "11:00:00",
  "available_time_end": "22:00:00",
  "translations": {
    "en": {
      "name": "Ramen",
      "description": "Various ramen menu"
    },
    "zh-TW": {
      "name": "拉麵",
      "description": "各種拉麵菜單"
    },
    "zh-CN": {
      "name": "拉面",
      "description": "各种拉面菜单"
    },
    "ko": {
      "name": "라멘",
      "description": "각종 라멘 메뉴"
    }
  }
}
```

**フィールド説明**
- `name`: カテゴリ名（必須、文字列、最大50文字）
- `description`: カテゴリ説明（任意、文字列、最大200文字）
- `display_order`: 表示順（必須、数値）
- `is_active`: 有効フラグ（必須、真偽値）
- `icon`: アイコン（任意、文字列、絵文字またはアイコンコード）
- `color_code`: カラーコード（任意、文字列、#RRGGBB形式）
- `parent_category_id`: 親カテゴリID（任意、数値、階層構造の場合）
- `is_featured`: おすすめ表示（必須、真偽値）
- `available_time_start`: 提供開始時間（任意、時刻、HH:MM:SS形式）
- `available_time_end`: 提供終了時間（任意、時刻、HH:MM:SS形式）
- `translations`: 多言語翻訳（必須、オブジェクト）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 3,
    "name": "アルコール・ドリンク",
    "display_order": 4,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T11:00:00Z",
    "products_count": 5,
    "translations": {
      "en": "Alcohol & Drinks",
      "zh-TW": "酒精飲料",
      "zh-CN": "酒精饮料",
      "ko": "알코올 음료"
    }
  },
  "message": "カテゴリが更新されました"
}
```

## 🗑️ POSカテゴリ削除
```http
DELETE /api/v1/pos/categories/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: カテゴリ廃止時、メニュー整理時
- **目的**: **カテゴリ削除時の同期** - POS側で削除したカテゴリをWebシステムからも除去し、両システムの整合性を保持

**パラメータ**
- `id`: 削除するカテゴリのID（必須、数値）

**レスポンス（成功）**
```json
{
  "success": true,
  "message": "カテゴリが削除されました",
  "data": {
    "deleted_id": 3,
    "deleted_at": "2024-01-15T11:30:00Z"
  }
}
```

**レスポンス（削除失敗）**
```json
{
  "success": false,
  "error": {
    "code": "CATEGORY_IN_USE",
    "message": "このカテゴリには商品が登録されているため削除できません"
  }
}
```
## 📦 POSオプション同期検証
```http
POST /api/v1/pos/options/verify
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: オプションマスター同期確認時（起動時・定期実行）
- **目的**: **WebとPOSのオプション全件同期検証** - POSから全レコードを送信し、Web側と比較して不整合を検出

**技術仕様**
- **全レコード送信**: POSから全オプションデータを送信（option_details含む）
- **差分検出**: Web側でPOSデータと比較し、不整合を検出
- **レスポンス**: 同期状態と不整合がある場合はその詳細を返却

**リクエスト**
```json
{
  "options": [
    {
      "id": 1,
      "name": "麺の硬さ",
      "description": "お好みの麺の硬さをお選びください",
      "category": "texture",
      "is_required": true,
      "is_multiple": false,
      "min_selections": 1,
      "max_selections": 1,
      "display_order": 1,
      "is_active": true,
      "deleted_at": null,
      "option_details": [
        {
          "id": 101,
          "option_id": 1,
          "product_id": 201,
          "name": "バリカタ",
          "price": 0,
          "display_order": 1,
          "is_default": false,
          "is_active": true,
          "deleted_at": null
        },
        {
          "id": 102,
          "option_id": 1,
          "product_id": 202,
          "name": "普通",
          "price": 0,
          "display_order": 2,
          "is_default": true,
          "is_active": true,
          "deleted_at": null
        }
      ]
    }
  ]
}
```

**レスポンス（全て同期済み）**
```json
{
  "success": true,
  "data": {
    "is_synced": true,
    "pos_count": 1,
    "web_count": 1,
    "unsynced_pos_ids": [],
    "unsynced_web_ids": [],
    "mismatched_items": [],
    "option_details_status": {
      "is_synced": true,
      "pos_count": 2,
      "web_count": 2,
      "unsynced_pos_ids": [],
      "unsynced_web_ids": [],
      "mismatched_items": []
    }
  },
  "message": "全てのオプションが同期されています"
}
```

**レスポンス（不整合あり）**
```json
{
  "success": true,
  "data": {
    "is_synced": false,
    "pos_count": 2,
    "web_count": 1,
    "unsynced_pos_ids": [2],
    "unsynced_web_ids": [],
    "mismatched_items": [
      {
        "id": 1,
        "field": "is_required",
        "pos_value": false,
        "web_value": true
      }
    ],
    "option_details_status": {
      "is_synced": false,
      "pos_count": 4,
      "web_count": 2,
      "unsynced_pos_ids": [103, 104],
      "unsynced_web_ids": [],
      "mismatched_items": []
    }
  },
  "message": "同期が必要な項目があります"
}
```

**リクエストフィールド説明**
- `options`: オプション配列（必須、配列）
  - `id`: オプションID（必須、数値）
  - `name`: オプション名（必須、文字列）
  - `description`: オプション説明（任意、文字列）
  - `category`: カテゴリ（必須、文字列: texture/topping/size/taste/other）
  - `is_required`: 必須選択（必須、真偽値）
  - `is_multiple`: 複数選択可（必須、真偽値）
  - `min_selections`: 最小選択数（必須、数値）
  - `max_selections`: 最大選択数（必須、数値）
  - `display_order`: 表示順（必須、数値）
  - `is_active`: 有効フラグ（必須、真偽値）
  - `deleted_at`: 削除日時（任意、日時またはnull）
  - `option_details`: オプション詳細配列（必須、配列）
    - `id`: 詳細ID（必須、数値）
    - `option_id`: 親オプションID（必須、数値）
    - `product_id`: 紐づく商品ID（必須、数値）
    - `name`: 選択肢名（必須、文字列）
    - `price`: 追加価格（必須、数値、マイナス可）
    - `display_order`: 表示順（必須、数値）
    - `is_default`: デフォルト選択（必須、真偽値）
    - `is_active`: 有効フラグ（必須、真偽値）
    - `deleted_at`: 削除日時（任意、日時またはnull）

**レスポンスフィールド説明**
- `is_synced`: 完全同期状態（真偽値）
- `pos_count`: POS側のレコード数（数値）
- `web_count`: Web側のレコード数（数値）
- `unsynced_pos_ids`: POSにのみ存在するID配列（配列）
- `unsynced_web_ids`: Webにのみ存在するID配列（配列）
- `mismatched_items`: 不整合項目の詳細（配列）
- `option_details_status`: option_detailsの同期状態（オブジェクト）


## ➕ POSオプション作成
```http
POST /api/v1/pos/options
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 新オプション追加時
- **目的**: **オプション登録時の同期** - POS側で追加したオプションをWebシステムに即座に反映

**リクエスト**
```json
{
  "name": "スープの濃さ",
  "category": "taste",
  "is_required": true,
  "is_multiple": false,
  "display_order": 2,
  "option_details": [
    {
      "name": "あっさり",
      "price": 0,
      "display_order": 1,
      "translations": {
        "en": "Light",
        "zh-TW": "清淡",
        "zh-CN": "清淡",
        "ko": "담백하게"
      }
    }
  ],
  "translations": {
    "en": "Soup Richness",
    "zh-TW": "湯頭濃度",
    "zh-CN": "汤头浓度",
    "ko": "국물 진하기"
  }
}
```

**フィールド説明**
- `name`: オプション名（必須、文字列、最大50文字）
- `category`: オプションカテゴリ（必須、列挙型: topping/size/taste）
- `is_required`: 必須選択フラグ（必須、真偽値）
- `is_multiple`: 複数選択可能フラグ（必須、真偽値）
- `display_order`: 表示順（必須、数値、正の整数）
- `option_details`: 選択肢配列（必須、配列、最低1個）
- `translations`: 多言語翻訳（任意、オブジェクト）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 6,
    "name": "スープの濃さ",
    "category": "taste",
    "is_required": true,
    "is_multiple": false,
    "display_order": 2,
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "option_details": [
      {
        "id": 11,
        "name": "あっさり",
        "price": 0,
        "display_order": 1
      }
    ]
  },
  "message": "オプションが作成されました"
}
```

## 📝 POSオプション更新
```http
PUT /api/v1/pos/options/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: オプション情報更新時、選択肢変更時
- **目的**: **オプション更新時の同期** - POS側でのオプション情報変更をWebシステムに即座に反映

**技術仕様**
- **全カラム送信方式**: POSから全てのカラムデータを送信、Web側で差分を検出して変更箇所のみ更新
- **変更検出**: LaravelのisDirty()メソッドで変更有無を判定
- **変更記録**: 変更があった場合のみchange_logsテーブルに記録
- **関連データ**: option_detailsも含めて全データを送信

**リクエスト**
```json
{
  "name": "麺の硬さ",
  "description": "お好みの麺の硬さをお選びください",
  "category": "texture",
  "is_required": true,
  "is_multiple": false,
  "min_selections": 1,
  "max_selections": 1,
  "display_order": 1,
  "is_active": true,
  "applicable_time_start": null,
  "applicable_time_end": null,
  "option_details": [
    {
      "id": 101,
      "product_id": 201,
      "name": "バリカタ",
      "price": 0,
      "display_order": 1,
      "is_default": false,
      "is_active": true,
      "max_quantity": 1,
      "translations": {
        "en": {"name": "Extra Firm"},
        "zh-TW": {"name": "超硬"},
        "zh-CN": {"name": "超硬"},
        "ko": {"name": "아주 단단함"}
      }
    },
    {
      "id": 102,
      "product_id": 202,
      "name": "カタ",
      "price": 0,
      "display_order": 2,
      "is_default": false,
      "is_active": true,
      "max_quantity": 1,
      "translations": {
        "en": {"name": "Firm"},
        "zh-TW": {"name": "硬"},
        "zh-CN": {"name": "硬"},
        "ko": {"name": "단단함"}
      }
    },
    {
      "id": 103,
      "product_id": 203,
      "name": "普通",
      "price": 0,
      "display_order": 3,
      "is_default": true,
      "is_active": true,
      "max_quantity": 1,
      "translations": {
        "en": {"name": "Regular"},
        "zh-TW": {"name": "普通"},
        "zh-CN": {"name": "普通"},
        "ko": {"name": "보통"}
      }
    },
    {
      "id": 104,
      "product_id": 204,
      "name": "やわ",
      "price": 0,
      "display_order": 4,
      "is_default": false,
      "is_active": true,
      "max_quantity": 1,
      "translations": {
        "en": {"name": "Soft"},
        "zh-TW": {"name": "軟"},
        "zh-CN": {"name": "软"},
        "ko": {"name": "부드러움"}
      }
    }
  ],
  "translations": {
    "en": {
      "name": "Noodle Firmness",
      "description": "Please select your preferred noodle firmness"
    },
    "zh-TW": {
      "name": "麵條硬度",
      "description": "請選擇您喜歡的麵條硬度"
    },
    "zh-CN": {
      "name": "面条硬度",
      "description": "请选择您喜欢的面条硬度"
    },
    "ko": {
      "name": "면 단단함",
      "description": "원하시는 면의 단단함을 선택해주세요"
    }
  }
}
```

**フィールド説明**
- `name`: オプション名（必須、文字列、最大100文字）
- `description`: オプション説明（任意、文字列、最大300文字）
- `category`: カテゴリ（必須、文字列: texture/topping/size/taste/other）
- `is_required`: 必須選択（必須、真偽値）
- `is_multiple`: 複数選択可（必須、真偽値）
- `min_selections`: 最小選択数（必須、数値）
- `max_selections`: 最大選択数（必須、数値）
- `display_order`: 表示順（必須、数値）
- `is_active`: 有効フラグ（必須、真偽値）
- `applicable_time_start`: 適用開始時間（任意、時刻）
- `applicable_time_end`: 適用終了時間（任意、時刻）
- `option_details`: オプション詳細配列（必須、配列）
  - `id`: 既存詳細のID（更新時は必須、新規は省略）
  - `product_id`: 紐づく商品ID（必須、数値）
  - `name`: 選択肢名（必須、文字列）
  - `price`: 追加価格（必須、数値、マイナス可）
  - `display_order`: 表示順（必須、数値）
  - `is_default`: デフォルト選択（必須、真偽値）
  - `is_active`: 有効フラグ（必須、真偽値）
  - `max_quantity`: 最大数量（必須、数値）
  - `translations`: 多言語翻訳（必須、オブジェクト）
- `translations`: オプション全体の多言語翻訳（必須、オブジェクト）

**注意**: 既存のoption_detailsを更新する場合は `id` を含める。新規詳細は `id` なしで作成される。

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 6,
    "name": "スープの濃さ（リニューアル）",
    "category": "taste",
    "is_required": false,
    "is_multiple": false,
    "display_order": 3,
    "updated_at": "2024-01-15T11:00:00Z",
    "option_details": [
      {
        "id": 11,
        "name": "あっさり",
        "price": 0,
        "display_order": 1
      }
    ]
  },
  "message": "オプションが更新されました"
}
```

## 🗑️ POSオプション削除
```http
DELETE /api/v1/pos/options/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: オプション廃止時、メニュー整理時
- **目的**: **オプション削除時の同期** - POS側で削除したオプションをWebシステムからも除去し、両システムの整合性を保持

**パラメータ**
- `id`: 削除するオプションのID（必須、数値）

**レスポンス（成功）**
```json
{
  "success": true,
  "message": "オプションが削除されました",
  "data": {
    "deleted_id": 6,
    "deleted_at": "2024-01-15T11:30:00Z"
  }
}
```

## 📝 POS注文ステータス更新
```http
PUT /api/v1/pos/orders/{id}
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 調理開始時、完成時、お客様提供時
- **目的**: 注文ステータスの更新と進捗管理

**リクエスト**
```json
{
  "status": "preparing",
  "estimated_ready_time": "2024-01-15T12:25:00Z",
  "notes": "チャーシュー追加のため少し時間がかかります"
}
```

**フィールド説明**
- `status`: 注文ステータス（必須、列挙型: pending/confirmed/preparing/ready/completed/cancelled）
- `estimated_ready_time`: 完成予定時刻（任意、日時、ISO8601形式）
- `notes`: 備考（任意、文字列、最大500文字）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "session_id": 1,
    "status": "preparing",
    "estimated_ready_time": "2024-01-15T12:25:00Z",
    "notes": "チャーシュー追加のため少し時間がかかります",
    "updated_at": "2024-01-15T12:06:00Z"
  },
  "message": "注文ステータスが更新されました"
}
```

## ❌ 注文キャンセル（ハンディ端末専用）
```http
POST /api/v1/pos/orders/{id}/cancel
```
**呼び出しタイミング**
- **誰が**: ハンディ端末
- **いつ**: 注文キャンセル時、食材不足時
- **目的**: 注文のキャンセル処理

**リクエスト**
```json
{
  "reason": "食材不足",
  "cancel_type": "store_issue",
  "refund_amount": 2150,
  "notes": "チャーシューが品切れのため全体キャンセル"
}
```

**フィールド説明**
- `reason`: キャンセル理由（必須、文字列、最大100文字）
- `cancel_type`: キャンセル区分（必須、列挙型: customer_request/store_issue/system_error）
- `refund_amount`: 返金額（必須、数値、0以上）
- `notes`: 備考（任意、文字列、最大500文字）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "cancelled",
    "reason": "食材不足",
    "cancel_type": "store_issue",
    "refund_amount": 2150,
    "cancelled_at": "2024-01-15T12:10:00Z",
    "notes": "チャーシューが品切れのため全体キャンセル"
  },
  "message": "注文がキャンセルされました"
}
```
## 🔄 障害復旧開始
```http
POST /api/v1/pos/recovery/start
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: クラウド接続復旧時
- **目的**: 障害復旧プロセス開始とオフライン期間の特定

**リクエスト**
```json
{
  "offline_start_time": "2024-01-15T10:00:00Z",
  "offline_end_time": "2024-01-15T12:00:00Z",
  "recovery_type": "network_restore",
  "offline_orders_count": 25,
  "offline_sessions_count": 8
}
```

**フィールド説明**
- `offline_start_time`: オフライン開始時刻（必須、日時、ISO8601形式）
- `offline_end_time`: オフライン終了時刻（必須、日時、ISO8601形式）
- `recovery_type`: 復旧タイプ（必須、列挙型: network_restore/system_restart/manual_recovery）
- `offline_orders_count`: オフライン期間の注文数（必須、数値、0以上）
- `offline_sessions_count`: オフライン期間のセッション数（必須、数値、0以上）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "recovery_id": "REC-20240115-001",
    "status": "in_progress",
    "offline_duration_minutes": 120,
    "sync_required": true,
    "priority_sync_items": [
      "orders",
      "sessions",
      "product_status"
    ],
    "estimated_sync_time_minutes": 15
  },
  "message": "障害復旧プロセスを開始しました"
}
```

## ✅ 障害復旧完了
```http
POST /api/v1/pos/recovery/complete
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: データ同期完了時
- **目的**: 障害復旧プロセスの完了通知

**リクエスト**
```json
{
  "recovery_id": "REC-20240115-001",
  "synced_orders_count": 25,
  "synced_sessions_count": 8,
  "sync_errors": [],
  "completion_time": "2024-01-15T12:15:00Z"
}
```

**フィールド説明**
- `recovery_id`: 復旧ID（必須、文字列）
- `synced_orders_count`: 同期した注文数（必須、数値、0以上）
- `synced_sessions_count`: 同期したセッション数（必須、数値、0以上）
- `sync_errors`: 同期エラー一覧（任意、配列）
- `completion_time`: 完了時刻（必須、日時、ISO8601形式）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "recovery_id": "REC-20240115-001",
    "status": "completed",
    "total_sync_duration_minutes": 15,
    "sync_summary": {
      "orders_synced": 25,
      "sessions_synced": 8,
      "products_updated": 12,
      "errors_count": 0
    },
    "system_status": "operational"
  },
  "message": "障害復旧が完了しました"
}
```

## 🔄 セッション同期
```http
POST /api/v1/pos/sync-sessions
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: オフライン復旧時、定期同期時
- **目的**: オフライン期間中のセッションデータ同期

**リクエスト**
```json
{
  "sessions": [
    {
      "local_id": "SESS-LOCAL-001",
      "table_number": "T03",
      "qr_code": "QR-T03-20240115-001",
      "status": "active",
      "created_at": "2024-01-15T10:30:00Z",
      "expires_at": "2024-01-15T16:30:00Z",
      "guest_count": 2,
      "total_orders": 3
    }
  ],
  "sync_timestamp": "2024-01-15T12:00:00Z"
}
```

**フィールド説明**
- `sessions`: セッション配列（必須、配列）
  - `local_id`: ローカルセッションID（必須、文字列）
  - `table_number`: テーブル番号（必須、文字列）
  - `qr_code`: QRコード（必須、文字列）
  - `status`: ステータス（必須、列挙型: active/completed/expired）
- `sync_timestamp`: 同期実行時刻（必須、日時、ISO8601形式）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "synced_sessions": 2,
    "created_sessions": 2,
    "updated_sessions": 0,
    "mapping": [
      {
        "local_id": "SESS-LOCAL-001",
        "cloud_id": 15,
        "status": "created"
      }
    ]
  },
  "message": "セッション同期が完了しました"
}
```

## 🔄 注文同期
```http
POST /api/v1/pos/sync-orders
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: オフライン復旧時、定期同期時
- **目的**: オフライン期間中の注文データ同期

**リクエスト**
```json
{
  "orders": [
    {
      "local_id": "ORDER-LOCAL-001",
      "session_local_id": "SESS-LOCAL-001",
      "table_number": "T03",
      "guest_name": "山田様",
      "status": "completed",
      "total_amount": 1800,
      "created_at": "2024-01-15T10:45:00Z",
      "completed_at": "2024-01-15T11:15:00Z",
      "items": [
        {
          "product_id": 1,
          "product_name": "醤油ラーメン",
          "quantity": 2,
          "unit_price": 800,
          "subtotal": 1600
        }
      ]
    }
  ],
  "sync_timestamp": "2024-01-15T12:00:00Z"
}
```

**フィールド説明**
- `orders`: 注文配列（必須、配列）
  - `local_id`: ローカル注文ID（必須、文字列）
  - `session_local_id`: セッションローカルID（必須、文字列）
  - `items`: 注文アイテム配列（必須、配列）
- `sync_timestamp`: 同期実行時刻（必須、日時、ISO8601形式）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "synced_orders": 1,
    "created_orders": 1,
    "updated_orders": 0,
    "total_amount_synced": 1800,
    "mapping": [
      {
        "local_id": "ORDER-LOCAL-001",
        "cloud_id": 25,
        "cloud_session_id": 15,
        "status": "created"
      }
    ]
  },
  "message": "注文同期が完了しました"
}
```

## 🌐 多言語翻訳同期
```http
POST /api/v1/pos/translations/sync
```
**呼び出しタイミング**
- **誰が**: POSシステム
- **いつ**: 翻訳データ更新時、定期同期時
- **目的**: 多言語翻訳データの同期

**リクエスト**
```json
{
  "translations": [
    {
      "entity_type": "product",
      "entity_id": 1,
      "translations": {
        "en": "Soy Sauce Ramen",
        "zh-TW": "醬油拉麵",
        "zh-CN": "酱油拉面",
        "ko": "간장 라멘"
      }
    }
  ],
  "sync_timestamp": "2024-01-15T12:00:00Z"
}
```

**フィールド説明**
- `translations`: 翻訳配列（必須、配列）
  - `entity_type`: エンティティタイプ（必須、列挙型: product/category/option/option_detail）
  - `entity_id`: エンティティID（必須、数値）
  - `translations`: 言語別翻訳（必須、オブジェクト）
- `sync_timestamp`: 同期実行時刻（必須、日時、ISO8601形式）

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "synced_translations": 3,
    "created_translations": 2,
    "updated_translations": 1,
    "supported_languages": [
      "en",
      "zh-TW", 
      "zh-CN",
      "ko"
    ],
    "sync_summary": {
      "products": 1,
      "categories": 1,
      "options": 1,
      "option_details": 0
    }
  },
  "message": "翻訳データの同期が完了しました"
}
```

### 5.5 管理画面（Livewire実装）

---


## 🔐 管理画面認証（Laravel Breeze）
```
操作: GET /admin/*
実装: Laravel Breezeセッション認証 + ミドルウェア
```

**実装方針**
- **技術スタック**: Laravel Livewire + Mary UI
- **認証方式**: Laravel Breezeセッション認証
- **CSRF保護**: 自動適用（Webルート）
- **データ取得**: Eloquentモデル直接アクセス（APIではない）


## 📋 商品一覧（管理画面用）
```
操作: GET /admin/products
実装: Livewireコンポーネント ProductList::class
```

**呼び出しタイミング**
- **誰が**: 管理者（ブラウザ画面）
- **いつ**: 管理画面の商品管理ページアクセス時
- **目的**: POS専用CRUD操作により作成されたデータの表示（読み取り専用）

**Livewire実装**
```php
// app/Livewire/Admin/ProductList.php
class ProductList extends Component
{
    public function render()
    {
        $products = Product::with(['categories', 'images'])
            ->where('store_id', auth()->user()->store_id)
            ->paginate(20);
            
        return view('livewire.admin.product-list', compact('products'));
    }
}
```

**レスポンス（Blade）**
```blade
<div class="space-y-4">
    @foreach($products as $product)
    <x-mary-card>
        <h3>{{ $product->name }}</h3>
        <p>{{ $product->description }}</p>
        <span class="font-bold">¥{{ number_format($product->price) }}</span>
        <x-mary-badge value="{{ $product->availability_status }}" />
    </x-mary-card>
    @endforeach
    {{ $products->links() }}
</div>
```

## 📋 ユーザー一覧（管理画面用）
```
操作: GET /admin/users
実装: Livewireコンポーネント UserManagement::class
```

**呼び出しタイミング**
- **誰が**: 管理者（ブラウザ画面）
- **いつ**: 管理画面のユーザー管理ページアクセス時
- **目的**: 店舗ユーザーの一覧表示・管理

**Livewire実装**
```php
// app/Livewire/Admin/UserManagement.php
class UserManagement extends Component
{
    public $users;
    public $showCreateModal = false;
    
    public function mount()
    {
        $this->loadUsers();
    }
    
    public function loadUsers()
    {
        $this->users = User::where('store_id', auth()->user()->store_id)
            ->whereIn('role', ['admin', 'staff'])
            ->get();
    }
    
    public function createUser($formData)
    {
        User::create([
            'name' => $formData['name'],
            'email' => $formData['email'],
            'password' => Hash::make($formData['password']),
            'role' => $formData['role'],
            'store_id' => auth()->user()->store_id,
        ]);
        
        $this->loadUsers();
        $this->showCreateModal = false;
        session()->flash('message', 'ユーザーが作成されました');
    }
}
```

## ⚙️ システム設定管理
```
操作: GET /admin/settings
操作: PUT /admin/settings/{key}
実装: Livewireコンポーネント SettingsManagement::class
```

**呼び出しタイミング**
- **誰が**: 管理者（ブラウザ画面）
- **いつ**: 管理画面の設定ページアクセス・設定変更時
- **目的**: Web固有設定（POS設定とは分離）の管理

**Livewire実装**
```php
// app/Livewire/Admin/SystemSettings.php
class SystemSettings extends Component
{
    public $settings = [];
    
    public function mount()
    {
        $this->settings = SystemSetting::where('store_id', auth()->user()->store_id)
            ->pluck('value', 'key')
            ->toArray();
    }
    
    public function updateSetting($key, $value)
    {
        SystemSetting::updateOrCreate(
            ['key' => $key, 'store_id' => auth()->user()->store_id],
            ['value' => $value]
        );
        
        session()->flash('message', '設定が更新されました');
    }
}
```


## 📊 レポート画面
```
操作: GET /admin/reports/sales
操作: GET /admin/reports/products
実装: Livewireコンポーネント SalesReport::class, ProductReport::class
```

**呼び出しタイミング**
- **誰が**: 管理者（ブラウザ画面）
- **いつ**: 管理画面のレポートページアクセス時
- **目的**: 売上・商品分析データの表示（読み取り専用）

**Livewire実装**
```php
// app/Livewire/Admin/SalesReport.php
class SalesReport extends Component
{
    public $startDate;
    public $endDate;
    public $salesData = [];
    
    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->generateReport();
    }
    
    public function generateReport()
    {
        $this->salesData = Order::where('store_id', auth()->user()->store_id)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as daily_total, COUNT(*) as order_count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }
}
```

**技術的補足**:
- **Livewireのリアルタイム更新**: wire:poll機能で定期的なデータ更新
- **Mary UIコンポーネント**: テーブル、フォーム、モーダルの統一デザイン
- **CSRF保護**: Livewireフォームで自動適用
- **権限チェック**: Livewireコンポーネント内でGateやPolicyを使用
## 6. エラーハンドリング

### 6.1 一般的なエラー

#### バリデーションエラー（422）
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": {
      "items.0.quantity": [
        "数量は1以上を指定してください"
      ],
      "items.1.product_id": [
        "指定されたメニューは存在しません"
      ]
    }
  }
}
```

#### 認証エラー（401）
```json
{
  "success": false,
  "error": {
    "code": "UNAUTHENTICATED", 
    "message": "認証が必要です"
  }
}
```

#### 権限エラー（403）
```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "この操作を実行する権限がありません"
  }
}
```

### 6.2 ゲストセッション固有のエラー

#### セッション期限切れ（401）
```json
{
  "success": false,
  "error": {
    "code": "SESSION_EXPIRED",
    "message": "セッションの有効期限が切れました。再度アクセスしてください"
  }
}
```

#### デバイス不一致（403）
```json
{
  "success": false,
  "error": {
    "code": "DEVICE_MISMATCH",
    "message": "別のデバイスからアクセスされています"
  }
}
```

## 7. セキュリティ対策

### 7.1 レート制限
```
# レスポンスヘッダー
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1704067200
```

### 7.2 CORS設定
```
Access-Control-Allow-Origin: https://example.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-ID
Access-Control-Max-Age: 86400
```

### 7.3 セキュリティヘッダー
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## 8. SPA Navigation考慮事項

### 8.1 Livewire Navigationとの統合

#### wire:navigateによる最適化
```
# 従来のフルページリロード
GET /menu → 完全なHTMLページ返却 → ブラウザ全体再描画

# wire:navigateによるSPA遷移
GET /menu → 差分HTMLのみ返却 → <body>部分のみ更新
```

**利点**:
- ページリロードなしでURL変更
- JavaScriptステート保持
- アセットの再読み込み不要
- 2倍以上の体感速度向上

#### API設計への影響
```php
// Livewireコンポーネントでの実装
public function mount()
{
    // wire:navigate経由のアクセス判定
    if (request()->header('X-Livewire')) {
        // 差分データのみ準備
        return $this->loadPartialData();
    }
    // 通常アクセス時は全データ
    return $this->loadFullData();
}
```

### 8.2 セッション・状態管理

#### ブラウザバック対策
```javascript
// History API制御
window.addEventListener('livewire:navigate', (event) => {
    // 注文フロー中はバック操作を無効化
    if (window.location.pathname.includes('/order/')) {
        history.pushState(null, null, location.href);
    }
});
```

#### セッション継続性
- **席セッション**: wire:navigate遷移でも維持
- **ゲストセッション**: Cookieベースで永続化
- **カート状態**: Redisキャッシュで高速アクセス

### 8.3 パフォーマンス最適化

#### Prefetch戦略
```blade
{{-- 商品一覧でのプリフェッチ --}}
@foreach($products as $product)
    <a href="/product/{{ $product->id }}" 
       wire:navigate.hover
       data-prefetch-priority="{{ $loop->index < 5 ? 'high' : 'low' }}">
        {{ $product->name }}
    </a>
@endforeach
```

#### APIレスポンス最適化
```php
// 条件付きレスポンス
if ($request->header('X-Livewire-Navigate')) {
    // 最小限のデータセット
    return response()->json([
        'html' => view('partial.menu')->render(),
        'title' => 'メニュー',
        'meta' => ['cart_count' => $cartCount]
    ]);
}
// フルレスポンス
return view('menu', compact('products', 'categories'));
```

### 8.4 エラーハンドリング

#### Navigation失敗時の処理
```javascript
document.addEventListener('livewire:navigate-error', (event) => {
    // フォールバック処理
    if (event.detail.statusCode === 419) {
        // CSRFトークン期限切れ
        window.location.reload();
    } else if (event.detail.statusCode === 401) {
        // 認証切れ
        window.location.href = '/login';
    }
});
```

### 8.5 実装チェックリスト

- [ ] 全内部リンクに`wire:navigate`追加
- [ ] 重要ページに`wire:navigate.hover`設定
- [ ] プログラマティックナビゲーション対応
- [ ] ブラウザバック無効化実装
- [ ] @persistによる要素永続化
- [ ] アセットトラッキング設定
- [ ] エラーハンドリング実装
- [ ] パフォーマンス計測・最適化

---

このAPI設計書に従って実装することで、一貫性のあるRESTful APIを構築できます。
