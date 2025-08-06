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

### 1.3 認証方式
- **席セッション**: QRコード読み取り後のトークン認証
- **ゲストセッション**: 自動生成トークン + デバイスフィンガープリント
- **POS API**: Bearer Token + IP制限（Laravel Sanctum）
- **管理画面**: Session認証（Laravel Breeze）

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

## 3. リクエスト形式

### 3.1 共通ヘッダー
```http
Content-Type: application/json
Accept: application/json
Accept-Language: ja,en;q=0.9
Authorization: Bearer {token}
X-Request-ID: {uuid}
```

### 3.2 リクエストボディ例
```json
// POST /api/v1/orders
{
  "session_id": "123e4567-e89b-12d3-a456-426614174000",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "options": [
        {
          "option_id": 1,
          "values": [1, 2]
        }
      ],
      "notes": "辛さ控えめ"
    }
  ]
}
```

### 3.3 クエリパラメータ
```
# ページネーション
GET /api/v1/products?page=1&per_page=20

# フィルタリング
GET /api/v1/products?category_id=1&availability_status=available

# 複数ステータス指定
GET /api/v1/products?availability_status[]=available&availability_status[]=preparing

# カテゴリーに紐付けられた商品のみ取得
GET /api/v1/categories/{id}/products

# ソート
GET /api/v1/products?sort=price&order=asc

# 検索
GET /api/v1/products?q=ラーメン
```

## 4. レスポンス形式

### 4.1 成功レスポンス

#### 単一リソース
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "醤油ラーメン",
    "price": 950,
    "created_at": "2024-01-01T12:00:00+09:00",
    "updated_at": "2024-01-01T12:00:00+09:00"
  },
  "message": null
}
```

#### リソースコレクション
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "醤油ラーメン",
      "price": 950
    },
    {
      "id": 2,
      "name": "味噌ラーメン",
      "price": 1050
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  },
  "message": null
}
```

#### 作成成功
```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_number": "2024010112345",
    "status": "pending",
    "total_amount": 2100
  },
  "message": "注文を受け付けました"
}
```

### 4.2 エラーレスポンス

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

#### リソース未発見（404）
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "指定されたリソースが見つかりません"
  }
}
```

#### サーバーエラー（500）
```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_SERVER_ERROR",
    "message": "サーバーエラーが発生しました。しばらく待ってから再度お試しください",
    "request_id": "123e4567-e89b-12d3-a456-426614174000"
  }
}
```

## 5. HTTPステータスコード

### 5.1 成功系
- **200 OK**: 取得・更新成功
- **201 Created**: 作成成功（Locationヘッダー付き）
- **204 No Content**: 削除成功

### 5.2 クライアントエラー系
- **400 Bad Request**: リクエスト形式エラー
- **401 Unauthorized**: 認証エラー
- **403 Forbidden**: 権限エラー
- **404 Not Found**: リソース未発見
- **409 Conflict**: リソース競合（重複等）
- **422 Unprocessable Entity**: バリデーションエラー
- **429 Too Many Requests**: レート制限超過

### 5.3 サーバーエラー系
- **500 Internal Server Error**: サーバー内部エラー
- **502 Bad Gateway**: 外部サービスエラー
- **503 Service Unavailable**: メンテナンス中
- **504 Gateway Timeout**: タイムアウト

## 6. API エンドポイント一覧

### 6.1 認証API
```
# 席セッション（QRコード読み取り）
POST   /api/v1/auth/session/start    # QRコードセッション開始
POST   /api/v1/auth/session/refresh  # セッショントークンリフレッシュ
POST   /api/v1/auth/session/end      # セッション終了

# ゲストセッション（自動）
POST   /api/v1/auth/guest/start      # ゲストセッション開始
POST   /api/v1/auth/guest/refresh    # ゲストトークンリフレッシュ
DELETE /api/v1/auth/guest/end        # ゲストセッション終了
```

### 6.2 メニューAPI
```
GET    /api/v1/categories            # カテゴリ一覧
GET    /api/v1/products              # 商品一覧
GET    /api/v1/products/{id}         # 商品詳細
GET    /api/v1/products/{id}/options # 商品のオプション一覧
GET    /api/v1/categories/{id}/products # カテゴリ別商品一覧
```

### 6.3 注文API
```
POST   /api/v1/orders                # 注文作成
GET    /api/v1/orders/{id}          # 注文詳細
GET    /api/v1/sessions/{id}/orders # セッションの注文履歴
POST   /api/v1/orders/{id}/cancel   # 注文キャンセル
```

### 6.4 POS連携API
```
GET    /api/v1/pos/changes           # 変更履歴取得（ポーリング）
GET    /api/v1/pos/changes/detail   # 変更詳細取得
POST   /api/v1/pos/changes/sync     # 同期完了通知
GET    /api/v1/pos/orders            # 注文一覧取得
PUT    /api/v1/pos/orders/{id}      # 注文ステータス更新
PUT    /api/v1/pos/products/{id}    # 商品提供状態更新
```

### 6.5 管理API
```
# メニュー管理
# 商品管理
GET    /api/v1/admin/products       # 商品一覧（管理用）
POST   /api/v1/admin/products       # 商品作成
PUT    /api/v1/admin/products/{id}  # 商品更新
DELETE /api/v1/admin/products/{id}  # 商品削除

# カテゴリ管理
GET    /api/v1/admin/categories     # カテゴリ一覧
POST   /api/v1/admin/categories     # カテゴリ作成
PUT    /api/v1/admin/categories/{id} # カテゴリ更新
DELETE /api/v1/admin/categories/{id} # カテゴリ削除

# オプション管理
GET    /api/v1/admin/options        # オプション一覧
POST   /api/v1/admin/options        # オプション作成
PUT    /api/v1/admin/options/{id}   # オプション更新
DELETE /api/v1/admin/options/{id}   # オプション削除

# セッション管理
GET    /api/v1/admin/sessions       # セッション一覧
POST   /api/v1/admin/sessions/qr    # QRコード生成
```

## 7. 共通仕様

### 7.1 日時フォーマット
- **ISO 8601形式**: `2024-01-01T12:00:00+09:00`
- **タイムゾーン**: Asia/Tokyo（JST）

### 7.2 文字コード
- **UTF-8**: 全ての文字列データ
- **絵文字対応**: UTF8MB4

### 7.3 画像URL
- **完全URL**: `https://example.com/storage/images/menu/1.webp`
- **WebP形式推奨**: モバイル最適化

### 7.4 多言語対応
- **Accept-Language**: ヘッダーで言語指定
- **対応言語**: ja, en, zh-TW, zh-CN, ko
- **デフォルト言語**: ja

### 7.5 レート制限
```
# レスポンスヘッダー
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1704067200
```

## 8. セキュリティ

### 8.1 CORS設定
```
Access-Control-Allow-Origin: https://example.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-ID
Access-Control-Max-Age: 86400
```

### 8.2 リクエスト署名（POS API）
```
X-Signature: sha256=HMAC-SHA256(request_body, secret_key)
X-Timestamp: 1704067200
```

### 8.3 セキュリティヘッダー
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## 9. 実装例（Laravel）

### 9.1 コントローラー実装
```php
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['categories', 'options'])
            ->where('is_active', true);
            
        // フィルタリング
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // ページネーション
        $items = $query->paginate($request->get('per_page', 20));
        
        return $this->successResponse(
            data: ProductResource::collection($items),
            meta: $this->paginationMeta($items)
        );
    }
    
    protected function successResponse($data, $meta = null, $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'message' => $message,
        ]);
    }
}
```

### 9.2 POS API: 商品提供状態更新
```php
// PUT /api/v1/pos/products/{id}
public function updateProductAvailability(Request $request, $id)
{
    $validated = $request->validate([
        'availability_status' => 'required|in:available,sold_out,not_arrived,preparing',
        'availability_message' => 'nullable|string|max:255',
        'expected_available_time' => 'nullable|date_format:H:i',
    ]);
    
    $product = Product::findOrFail($id);
    $product->update($validated);
    
    // 変更履歴を記録
    ChangeLog::create([
        'entity_type' => 'products',
        'entity_id' => $product->id,
        'action' => 'updated',
        'changes' => json_encode([
            'availability_status' => [
                'old' => $product->getOriginal('availability_status'),
                'new' => $validated['availability_status']
            ]
        ]),
        'user_id' => auth()->id(),
        'user_type' => 'pos_system',
    ]);
    
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $product->id,
            'availability_status' => $product->availability_status,
            'availability_message' => $product->availability_message,
            'expected_available_time' => $product->expected_available_time,
        ]
    ]);
}
```

### 9.3 商品詳細API（オプション付き）
```php
// GET /api/v1/products/{id}
public function show($id)
{
    $product = Product::with([
        'categories',
        'images' => function($query) {
            $query->orderBy('sort_order');
        },
        'options' => function($query) {
            $query->with(['optionProducts' => function($query) {
                $query->orderBy('sort_no');
            }]);
        }
    ])->findOrFail($id);
    
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $product->id,
            'code' => $product->code,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'tax_in_price' => $product->tax_in_price,
            'tax_type' => $product->tax_type,
            'availability_status' => $product->availability_status,
            'availability_message' => $product->availability_message,
            'expected_available_time' => $product->expected_available_time,
            'image_url' => $product->image_url,
            'images' => $product->images,
            'categories' => $product->categories,
            'options' => $product->options->map(function($option) {
                return [
                    'id' => $option->id,
                    'title' => $option->title,
                    'description' => $option->description,
                    'required' => $option->required,
                    'selection_type' => $option->selection_type,
                    'choices' => $option->optionProducts->map(function($choice) {
                        return [
                            'id' => $choice->id,
                            'name' => $choice->name,
                            'price' => $choice->price,
                            'tax_in_price' => $choice->tax_in_price,
                            'availability_status' => $choice->availability_status,
                            'default' => $choice->pivot->default,
                        ];
                    })
                ];
            })
        ]
    ]);
}
```

### 9.4 カテゴリ別商品一覧API
```php
// GET /api/v1/categories/{id}/products
public function getCategoryProducts($categoryId, Request $request)
{
    $products = Product::whereHas('categories', function($query) use ($categoryId) {
        $query->where('categories.id', $categoryId);
    })
    ->with(['categories', 'images'])
    ->where('availability_status', 'available')
    ->where('is_active', true)
    ->orderByRaw('
        (SELECT sort_no FROM category_product 
         WHERE category_product.product_id = products.id 
         AND category_product.category_id = ?) ASC
    ', [$categoryId])
    ->paginate($request->get('per_page', 20));
    
    return response()->json([
        'success' => true,
        'data' => $products->items(),
        'meta' => [
            'current_page' => $products->currentPage(),
            'total' => $products->total(),
            'per_page' => $products->perPage(),
        ]
    ]);
}
```

### 9.5 エラーハンドリング
```php
class ApiExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => '入力内容に誤りがあります',
                    'details' => $exception->errors(),
                ],
            ], 422);
        }
        
        // その他のエラー処理...
    }
}
```

### 9.6 ゲストセッション認証API仕様

#### ゲストセッション開始
```http
POST /api/v1/auth/guest/start
```

**リクエスト**
```json
{
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
    "device_fingerprint": "browser_chrome_win10_hash123",
    "store_id": 1
  },
  "message": "ゲストセッションを開始しました"
}
```

#### ゲストセッション更新
```http
POST /api/v1/auth/guest/refresh
Authorization: Bearer guest_abc123def456ghi789
```

**リクエスト**
```json
{
  "device_fingerprint": "browser_chrome_win10_hash123"
}
```

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "token": "guest_abc123def456ghi789",
    "expires_in": 1800,
    "last_access": "2024-01-01T12:30:00+09:00"
  }
}
```

#### ゲストカート操作API

**カートアイテム追加**
```http
POST /api/v1/guest/cart/items
Authorization: Bearer guest_abc123def456ghi789
```

**リクエスト**
```json
{
  "product_id": 1,
  "quantity": 2,
  "options": {
    "1": [5], // オプションID: [選択商品ID...]
    "2": [3, 4]
  },
  "notes": "辛さ控えめ"
}
```

**カート内容取得**
```http
GET /api/v1/guest/cart
Authorization: Bearer guest_abc123def456ghi789
```

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
        "unit_price": 950,
        "total_price": 1900,
        "options": [
          {
            "option_id": 1,
            "option_name": "麺の量",
            "choice_id": 5,
            "choice_name": "大盛り",
            "choice_price": 100
          }
        ],
        "notes": "辛さ控えめ"
      }
    ],
    "total_amount": 2000,
    "item_count": 2
  }
}
```

#### ゲスト注文作成API
```http
POST /api/v1/guest/orders
Authorization: Bearer guest_abc123def456ghi789
```

**リクエスト**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "options": {
        "1": [5]
      },
      "notes": "辛さ控えめ"
    }
  ],
  "notes": "テイクアウトでお願いします"
}
```

**レスポンス（成功）**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "order_number": "GUEST2024010112345",
    "guest_token": "guest_abc123def456ghi789",
    "status": "pending",
    "total_amount": 2000,
    "ordered_at": "2024-01-01T12:00:00+09:00"
  },
  "message": "ご注文を承りました"
}
```

#### ゲスト注文履歴取得API
```http
GET /api/v1/guest/orders
Authorization: Bearer guest_abc123def456ghi789
```

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "order_number": "GUEST2024010112345",
      "status": "preparing",
      "total_amount": 2000,
      "ordered_at": "2024-01-01T12:00:00+09:00",
      "items": [
        {
          "product_name": "醤油ラーメン",
          "quantity": 2,
          "unit_price": 950
        }
      ]
    }
  ],
  "meta": {
    "total": 1
  }
}
```

### 9.7 エラーハンドリング（ゲストセッション）

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

---

このAPI設計書に従って実装することで、一貫性のあるRESTful APIを構築できます。