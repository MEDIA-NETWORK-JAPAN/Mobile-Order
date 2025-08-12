# API設計書

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
- **2層認証システム（モバイル）**: 
  - 第1層: 席セッション（POS生成 → WebでURL化）
  - 第2層: ゲストセッション（個人識別 + 不正防止）
- **POS API**: Bearer Token認証（Laravel Sanctum）
- **管理画面**: Session認証（Laravel Breeze）
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
```
POST   /api/v1/auth/session/start    # QRコードセッション開始
POST   /api/v1/auth/session/refresh  # セッショントークンリフレッシュ
POST   /api/v1/auth/session/end      # セッション終了
```

#### ゲストセッション（自動延長）
```
POST   /api/v1/auth/guest/start      # ゲストセッション開始
DELETE /api/v1/auth/guest/end        # ゲストセッション終了
# 注：各API実行時に自動的にTTL延長（30分）
```

### 4.2 メニューAPI（読み取り専用）
```
GET    /api/v1/categories            # カテゴリ一覧
GET    /api/v1/products              # 商品一覧
GET    /api/v1/products/{id}         # 商品詳細
GET    /api/v1/products/{id}/options # 商品のオプション一覧
GET    /api/v1/categories/{id}/products # カテゴリ別商品一覧
```

### 4.3 注文・カートAPI
```
# 注文管理
POST   /api/v1/orders                # 注文作成
GET    /api/v1/orders/{id}          # 注文詳細
GET    /api/v1/sessions/{id}/orders # セッション注文履歴（席全体）

# カート管理
POST   /api/v1/guest/cart/items     # カートアイテム追加
GET    /api/v1/guest/cart           # カート内容取得
DELETE /api/v1/guest/cart/items/{product_id} # アイテム削除
DELETE /api/v1/guest/cart           # カートクリア
```

### 4.4 POS連携API
```
GET    /api/v1/pos/changes           # 変更履歴取得（ポーリング）
POST   /api/v1/pos/changes/sync     # 同期完了通知
GET    /api/v1/pos/orders            # 注文一覧取得
PUT    /api/v1/pos/orders/{id}      # 注文ステータス更新
POST   /api/v1/pos/orders/{id}/cancel # 注文キャンセル（ハンディ端末専用）
PUT    /api/v1/pos/products/{id}    # 商品提供状態更新
POST   /api/v1/pos/sessions/extend   # 席セッション延長
POST   /api/v1/pos/auth/login        # POSログイン認証
POST   /api/v1/pos/auth/refresh      # POSトークン更新
POST   /api/v1/pos/translations/sync # 多言語翻訳同期
```

### 4.5 POS専用API（商品データ操作）
```
# 商品マスター管理（POS端末からのみアクセス可能）
GET    /api/v1/pos/products         # 商品一覧
POST   /api/v1/pos/products         # 商品作成
PUT    /api/v1/pos/products/{id}    # 商品更新
DELETE /api/v1/pos/products/{id}    # 商品削除

# カテゴリマスター管理（POS端末からのみアクセス可能）
GET    /api/v1/pos/categories       # カテゴリ一覧
POST   /api/v1/pos/categories       # カテゴリ作成
PUT    /api/v1/pos/categories/{id}  # カテゴリ更新
DELETE /api/v1/pos/categories/{id}  # カテゴリ削除

# オプションマスター管理（POS端末からのみアクセス可能）
GET    /api/v1/pos/options          # オプション一覧
POST   /api/v1/pos/options          # オプション作成
PUT    /api/v1/pos/options/{id}     # オプション更新
DELETE /api/v1/pos/options/{id}     # オプション削除
```

### 4.6 管理API（読み取り専用）
```
# メニュー閲覧（POS専用のCRUD操作により作成されたデータの表示のみ）
GET    /api/v1/admin/products       # 商品一覧（読み取り専用）
GET    /api/v1/admin/products/{id}  # 商品詳細（読み取り専用）
GET    /api/v1/admin/categories     # カテゴリ一覧（読み取り専用）
GET    /api/v1/admin/categories/{id} # カテゴリ詳細（読み取り専用）
GET    /api/v1/admin/options        # オプション一覧（読み取り専用）
GET    /api/v1/admin/options/{id}   # オプション詳細（読み取り専用）

# Web固有設定管理（管理画面で変更可能）
GET    /api/v1/admin/settings       # システム設定一覧
PUT    /api/v1/admin/settings/{key} # システム設定更新

# ユーザー管理（管理画面で変更可能）
GET    /api/v1/admin/users          # ユーザー一覧
POST   /api/v1/admin/users          # ユーザー作成
PUT    /api/v1/admin/users/{id}     # ユーザー更新
DELETE /api/v1/admin/users/{id}     # ユーザー削除

# セッション管理（読み取り専用）
GET    /api/v1/admin/sessions       # セッション一覧（読み取り専用）
GET    /api/v1/admin/sessions/{id}  # セッション詳細（読み取り専用）

# レポート（読み取り専用）
GET    /api/v1/admin/reports/sales  # 売上レポート
GET    /api/v1/admin/reports/products # 商品分析レポート
```

## 5. API詳細仕様

### 5.0 メニューAPI（読み取り専用）

#### カテゴリ一覧
```http
GET /api/v1/categories
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: メニュー画面初回表示時
- **目的**: 利用可能な商品カテゴリ一覧を取得しメニュー構成を表示

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "ラーメン",
      "translations": {
        "en": "Ramen",
        "zh-TW": "拉麵"
      },
      "sort_order": 1,
      "is_active": true
    },
    {
      "id": 2,
      "name": "サイドメニュー",
      "translations": {
        "en": "Side Menu",
        "zh-TW": "配菜"
      },
      "sort_order": 2,
      "is_active": true
    }
  ]
}
```

#### 商品一覧
```http
GET /api/v1/products?category_id=1
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: カテゴリ選択後、商品一覧表示時
- **目的**: 指定カテゴリの商品一覧を取得（価格、在庫状況含む）

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "醤油ラーメン",
      "description": "昔ながらの醤油ベース",
      "price": 800,
      "tax_in_price": 880,
      "availability_status": "available",
      "image_url": "https://example.com/images/shoyu-ramen.jpg",
      "translations": {
        "en": "Soy Sauce Ramen",
        "zh-TW": "醬油拉麵"
      }
    },
    {
      "id": 2,
      "name": "味噌ラーメン",
      "description": "コクのある味噌ベース",
      "price": 900,
      "tax_in_price": 990,
      "availability_status": "sold_out",
      "availability_message": "本日売り切れ",
      "image_url": "https://example.com/images/miso-ramen.jpg"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 15
  }
}
```

#### 商品詳細
```http
GET /api/v1/products/{id}
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 商品タップ時、商品詳細画面表示時
- **目的**: 商品の詳細情報と選択可能オプションを表示

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "醤油ラーメン",
    "description": "昔ながらの醤油ベース。コクと旨味が絶妙にバランス",
    "price": 800,
    "tax_in_price": 880,
    "availability_status": "available",
    "image_url": "https://example.com/images/shoyu-ramen.jpg",
    "translations": {
      "en": {
        "name": "Soy Sauce Ramen",
        "description": "Traditional soy sauce based ramen with rich flavor"
      }
    },
    "has_options": true
  }
}
```

#### 商品オプション一覧
```http
GET /api/v1/products/{id}/options
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 商品詳細画面表示時（オプションが存在する場合）
- **目的**: 商品に関連するオプション（サイズ、トッピング等）を取得

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "麺の硬さ",
      "required": true,
      "selection_type": "single",
      "choices": [
        {
          "product_id": 3,
          "name": "やわらか",
          "price": 0,
          "default_selected": false
        },
        {
          "product_id": 4,
          "name": "普通",
          "price": 0,
          "default_selected": true
        },
        {
          "product_id": 5,
          "name": "かため",
          "price": 0,
          "default_selected": false
        }
      ]
    },
    {
      "id": 2,
      "title": "トッピング",
      "required": false,
      "selection_type": "multiple",
      "choices": [
        {
          "product_id": 10,
          "name": "チャーシュー",
          "price": 200,
          "default_selected": false
        },
        {
          "product_id": 11,
          "name": "ネギ",
          "price": 100,
          "default_selected": false
        }
      ]
    }
  ]
}
```

### 5.1 ゲストセッション認証

#### ゲストセッション開始
```http
POST /api/v1/auth/guest/start
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: QRコード読み取り → 席セッション取得後に自動実行
- **目的**: 個人識別・不正防止・デバイス特定のため

**リクエスト**
```json
{
  "session_id": 123,
  "device_fingerprint": "browser_chrome_win10_hash123",
  "store_id": 1,
  "language": "ja"
}
```

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "token": "guest_abc123def456ghi789",
    "expires_in": 1800,
    "session_id": 123,
    "device_fingerprint": "browser_chrome_win10_hash123",
    "store_id": 1
  },
  "message": "ゲストセッションを開始しました"
}
```

#### ゲストセッション自動延長
```
各API呼び出し時に自動的にTTLを30分に延長
- ミドルウェアで透明に処理
- ゲストトークン（guest_*）の場合のみ実行
- Redis TTL: guest_session:{token}, guest_cart:{token}
- last_access フィールドも自動更新
```

**実装例（ミドルウェア）**
```php
class ExtendGuestSession
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if ($token = $request->bearerToken()) {
            if (str_starts_with($token, 'guest_')) {
                Redis::expire("guest_session:{$token}", 1800);
                Redis::expire("guest_cart:{$token}", 1800);
                Redis::hset("guest_session:{$token}", 'last_access', now()->toISOString());
            }
        }
        
        return $response;
    }
}
```

### 5.2 カート操作API

#### カートアイテム追加
```http
POST /api/v1/guest/cart/items
Authorization: Bearer guest_abc123def456ghi789
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 商品詳細画面で「カートに追加」ボタンクリック時
- **目的**: 選択した商品とオプションをカートに追加

**リクエスト**
```json
{
  "product_id": 1,     // メイン商品ID
  "quantity": 2,       // 数量
  "options": {
    "1": [5],          // オプションID=1（麺の硬さ）で商品ID=5（かため）を選択
    "2": [10, 11]      // オプションID=2（トッピング）で商品ID=10,11を複数選択
  }
}
```

**※注意**: options形式は統一商品マスター設計に基づく
- キー: オプションID（options.id）
- 値: 選択された商品IDの配列（products.id）
- 全ての選択肢は商品マスターで管理される

#### カート内容取得
```http
GET /api/v1/guest/cart
Authorization: Bearer guest_abc123def456ghi789
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: カート画面表示時、注文確認画面表示時
- **目的**: 現在のカート内容と合計金額を表示

**レスポンス**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "product_id": 1,
        "product_name": "醤油ラーメン",
        "quantity": 2,
        "unit_price": 1200,
        "options": [
          {
            "option_id": 10,
            "product_id": 201,
            "name": "チャーシュー追加",
            "price": 300
          }
        ],
        "subtotal": 3000
      }
    ],
    "total_amount": 3000,
    "item_count": 2
  }
}
```

### 5.3 注文作成API

#### ゲスト注文作成（2層認証対応）
```http
POST /api/v1/guest/orders
Authorization: Bearer guest_abc123def456ghi789
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）、POS経由ハンディ端末
- **いつ**: 注文確認画面で「注文を確定する」ボタンクリック時
- **目的**: カート内容を正式な注文として登録

**リクエスト**
```json
{
  "session_id": 123,  // 席セッション（注文履歴共有用）
  "device_fingerprint": "browser_chrome_win10_hash123",  // デバイス識別（不正防止）
  "items": [
    {
      "product_id": 1,     // メイン商品ID（例：ラーメン）
      "quantity": 2,       // 数量
      "options": {
        "1": [5],          // オプションID=1（麺の硬さ）で商品ID=5（かため）を選択
        "2": [10, 11]      // オプションID=2（トッピング）で商品ID=10,11（チャーシュー、ネギ）を複数選択
      },
      "memo": "辛さ控えめ"
    }
  ],
  "memo": "テイクアウトでお願いします"
}
```

**options構造の詳細説明:**
```json
// options: { "オプションID": [選択された商品IDの配列] }
"options": {
  "1": [5],      // オプション「麺の硬さ」で「かため」を選択
  "2": [10, 11]  // オプション「トッピング」で「チャーシュー」と「ネギ」を選択
}
```

**統一商品マスター設計による構造:**
```
商品マスター（products）に全てを格納:
- id=1: ラーメン（メイン商品）
- id=5: かため（オプション選択肢）
- id=10: チャーシュー（オプション選択肢）
- id=11: ネギ（オプション選択肢）

オプションマスター（options）:
- id=1: 麺の硬さ（single選択）
- id=2: トッピング（multiple選択）

紐付け（option_detail）:
- option_id=1, product_id=3（やわらか）
- option_id=1, product_id=4（普通）
- option_id=1, product_id=5（かため）
- option_id=2, product_id=10（チャーシュー）
- option_id=2, product_id=11（ネギ）
```

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_number": "2024010112345",
    "session_id": 123,
    "guest_token": "guest_abc123def456ghi789",
    "device_fingerprint": "browser_chrome_win10_hash123",
    "status": "pending",
    "total_amount": 2000,
    "ordered_at": "2024-01-01T12:00:00+09:00"
  },
  "message": "ご注文を承りました"
}
```

### 5.4 セッション注文履歴API（席全体の注文状況）

#### 注文履歴取得（ゲスト識別アイコン付き）
```http
GET /api/v1/sessions/{id}/orders
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 注文履歴画面表示時、注文完了後の確認時
- **目的**: 同席者全体の注文状況と個人識別アイコンを表示

**レスポンス**
```json
{
  "success": true,
  "data": {
    "session": {
      "id": 123,
      "table_number": "8",
      "customer_count": 3,
      "status": "active"
    },
    "orders": [
      {
        "id": 1,
        "order_number": "20240101001",
        "guest_identifier": "🐶",
        "guest_color": "#FF6B6B",
        "status": "preparing",
        "total_amount": 1200,
        "ordered_at": "2024-01-01T12:30:00+09:00",
        "items": [
          {
            "product_name": "醤油ラーメン",
            "quantity": 2,
            "unit_price": 600,
            "options": "チャーシュー追加、麺かため"
          }
        ]
      },
      {
        "id": 2,
        "order_number": "20240101002", 
        "guest_identifier": "🐱",
        "guest_color": "#4ECDC4",
        "status": "completed",
        "total_amount": 800,
        "ordered_at": "2024-01-01T12:32:00+09:00",
        "items": [
          {
            "product_name": "チャーハン",
            "quantity": 1,
            "unit_price": 800,
            "options": ""
          }
        ]
      },
      {
        "id": 3,
        "order_number": "20240101003",
        "guest_identifier": "🐰",
        "guest_color": "#95E1D3",
        "status": "preparing", 
        "total_amount": 600,
        "ordered_at": "2024-01-01T12:35:00+09:00",
        "items": [
          {
            "product_name": "餃子",
            "quantity": 1,
            "unit_price": 600,
            "options": ""
          }
        ]
      }
    ],
    "summary": {
      "total_orders": 3,
      "total_amount": 2600,
      "pending_orders": 2,
      "completed_orders": 1
    }
  }
}
```

#### ゲスト識別アイコン生成システム

**基本仕様**
- **動物アイコン**: 親しみやすく覚えやすい
- **装飾子付与**: 大人数時の重複回避
- **カラーパレット**: 背景色で更なる識別性向上

**実装例**
```php
// 基本アイコン（10種類）
$baseIcons = ['🐶', '🐱', '🐰', '🐼', '🐸', '🐧', '🦊', '🐨', '🐯', '🐻'];

// 装飾子（大人数時の重複回避）
$modifiers = ['', '✨', '🎀', '🌟'];

// 色パレット（識別用背景色）
$colors = ['#FF6B6B', '#4ECDC4', '#95E1D3', '#FFA726', '#AB47BC', 
           '#5C6BC0', '#26A69A', '#66BB6A', '#FFCC02', '#FF7043'];

function generateGuestIdentifier($deviceFingerprint, $sessionId) {
    $hash = substr(md5($deviceFingerprint . $sessionId), 0, 8);
    $iconIndex = hexdec(substr($hash, 0, 2)) % 10;
    $modifierIndex = hexdec(substr($hash, 2, 2)) % 4;
    $colorIndex = hexdec(substr($hash, 4, 2)) % 10;
    
    return [
        'icon' => $baseIcons[$iconIndex] . $modifiers[$modifierIndex],
        'color' => $colors[$colorIndex]
    ];
}
```

**UI表示イメージ**
```
テーブル8番の注文状況:

🐶 醤油ラーメン×2     ¥1,200  準備中  12:30
   チャーシュー追加、麺かため
   
🐱 チャーハン×1       ¥800   完了   12:32

🐰 餃子×1           ¥600   準備中  12:35

合計: ¥2,600 (3件の注文)
```

### 5.5 POS連携API

#### 変更履歴取得（ポーリング）
```http
GET /api/v1/pos/changes?since=2024-01-01T12:00:00+09:00&store_id=1
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（Delphiアプリケーション）
- **いつ**: 30秒〜1分間隔の定期実行
- **目的**: クラウドで発生した注文等の変更を取得・同期

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "entity_type": "orders",
      "entity_id": 456,
      "action": "created",
      "changes": {
        "session_id": 789,
        "total_amount": 1500,
        "items": [
          {
            "product_id": 1,
            "quantity": 2,
            "options": {"1": [5]}
          }
        ]
      },
      "created_at": "2024-01-01T12:30:00+09:00"
    }
  ],
  "meta": {
    "last_sync_time": "2024-01-01T12:30:00+09:00",
    "total_changes": 1
  }
}
```

#### 同期完了通知
```http
POST /api/v1/pos/changes/sync
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（Delphiアプリケーション）
- **いつ**: 変更履歴をローカルDBに反映後
- **目的**: 同期完了を通知しchange_logsのis_syncedフラグを更新

**リクエスト**
```json
{
  "change_log_ids": [123, 124, 125],
  "synced_at": "2024-01-01T12:31:00+09:00",
  "store_id": 1
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "synced_count": 3,
    "synced_ids": [123, 124, 125]
  },
  "message": "同期完了を記録しました"
}
```

#### 注文キャンセル（ハンディ端末専用）
```http
POST /api/v1/pos/orders/{id}/cancel
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS経由ハンディ端末（スタッフ操作）
- **いつ**: 注文キャンセルが必要な場合（品切れ等）
- **目的**: 既に受けた注文をキャンセルして変更履歴に記録

#### 商品提供状態更新
```http
PUT /api/v1/pos/products/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（在庫管理機能）
- **いつ**: 商品の在庫状況変化時（品切れ、未入荷、準備中等）
- **目的**: リアルタイムな在庫状況をクラウドに同期

**リクエスト**
```json
{
  "availability_status": "sold_out",
  "availability_message": "本日売り切れ",
  "expected_available_time": null
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "醤油ラーメン",
    "availability_status": "sold_out",
    "availability_message": "本日売り切れ",
    "updated_at": "2024-01-01T12:32:00+09:00"
  },
  "message": "商品提供状態を更新しました"
}
```

### 5.6 管理API（読み取り専用）

#### 商品一覧
```http
GET /api/v1/admin/products
Authorization: Cookie (Laravel Breeze)
```

**呼び出しタイミング**
- **誰が**: 管理者・スタッフ（管理画面）
- **いつ**: 管理画面の商品一覧ページ表示時
- **目的**: POSで作成された商品データの閲覧（編集不可）

#### システム設定管理
```http
PUT /api/v1/admin/settings/{key}
Authorization: Cookie (Laravel Breeze)
```

**呼び出しタイミング**
- **誰が**: 管理者（管理画面）
- **いつ**: システム設定変更時（表示言語、タイムアウト時間等）
- **目的**: Web固有の設定を変更（POSには影響せず）

### 5.7 POS専用API（商品データ操作）

#### 商品作成
```http
POST /api/v1/pos/products
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（商品マスター管理機能）
- **いつ**: 新商品登録時、メニュー追加時
- **目的**: 新しい商品をクラウドに同期しchange_logsに記録

#### カテゴリ管理
```http
PUT /api/v1/pos/categories/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（カテゴリマスター管理機能）
- **いつ**: カテゴリ名変更、表示順変更時
- **目的**: カテゴリ情報をクラウドに同期しchange_logsに記録

#### 多言語翻訳同期
```http
POST /api/v1/pos/translations/sync
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（翻訳サービス連携機能）
- **いつ**: 新商品登録後、既存商品名変更後
- **目的**: Dify経由で多言語翻訳を取得し商品マスターを更新

### 5.8 追加のAPIエンドポイント

#### 席セッション開始
```http
POST /api/v1/auth/session/start
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: QRコード読み取り後、初回アクセス時
- **目的**: POS生成の席セッションIDを検証しテーブル情報を取得

**リクエスト**
```json
{
  "session_id": "SESSION_POS_20240101_001",
  "customer_count": 2
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "session_id": 123,
    "table_number": "8",
    "customer_count": 2,
    "expires_at": "2024-01-01T15:00:00+09:00",
    "store_id": 1
  },
  "message": "席セッションを開始しました"
}
```

#### ゲストセッション終了
```http
DELETE /api/v1/auth/guest/end
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: ユーザーがサイトを離脚する時、タイムアウト時
- **目的**: Redisからゲストセッションとカートデータを削除

#### カートクリア
```http
DELETE /api/v1/guest/cart
Authorization: Bearer guest_abc123def456ghi789
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: ユーザーが「カートを空にする」ボタンクリック時
- **目的**: カート内の全アイテムを削除しカートログに記録

**レスポンス**
```json
{
  "success": true,
  "data": {
    "cleared_items": 3,
    "total_amount_cleared": 2400
  },
  "message": "カートをクリアしました"
}
```

#### カートアイテム削除
```http
DELETE /api/v1/guest/cart/items/{product_id}
Authorization: Bearer guest_abc123def456ghi789
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: カート画面で特定商品の「削除」ボタンクリック時
- **目的**: 指定した商品をカートから削除しカートログに記録

**レスポンス**
```json
{
  "success": true,
  "data": {
    "removed_product_id": 1,
    "removed_quantity": 2,
    "removed_amount": 1600,
    "remaining_total": 800
  },
  "message": "商品をカートから削除しました"
}
```

#### 注文詳細取得
```http
GET /api/v1/orders/{id}
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）、POS端末
- **いつ**: 注文完了後の詳細確認時、POSが注文情報を取得する時
- **目的**: 注文の詳細内容や現在のステータスを取得

#### POS注文一覧取得
```http
GET /api/v1/pos/orders
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（注文管理機能）
- **いつ**: 注文状況確認時、一覧表示リフレッシュ時
- **目的**: 店舗内の全注文状況を取得しステータス管理

#### POS注文ステータス更新
```http
PUT /api/v1/pos/orders/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（注文状況管理機能）
- **いつ**: 注文ステータス変更時（調理中→提供済み等）
- **目的**: 注文の進捗状況をクラウドに同期しchange_logsに記録

#### 席セッション延長
```http
POST /api/v1/pos/sessions/extend
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（セッション管理機能）
- **いつ**: 席の利用時間を延長する必要がある時
- **目的**: アクティブな席セッションの有効期限を延長

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

---

このAPI設計書に従って実装することで、一貫性のあるRESTful APIを構築できます。