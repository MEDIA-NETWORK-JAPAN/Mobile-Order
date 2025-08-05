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
- **モバイルAPI**: Bearer Token（Laravel Sanctum）
- **POS API**: Bearer Token + IP制限（Laravel Sanctum）
- **管理画面**: Session認証（Laravel Breeze）

## 2. API命名規則

### 2.1 エンドポイント命名
```
# リソースの集合
GET    /api/v1/menu-items         # 一覧取得
POST   /api/v1/menu-items         # 新規作成

# 単一リソース
GET    /api/v1/menu-items/{id}   # 詳細取得
PUT    /api/v1/menu-items/{id}   # 更新
DELETE /api/v1/menu-items/{id}   # 削除

# リソースのアクション
POST   /api/v1/orders/{id}/confirm     # 注文確認
POST   /api/v1/sessions/start          # セッション開始
```

### 2.2 命名ルール
- **小文字とハイフン**: `menu-items`（ケバブケース）
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
      "menu_item_id": 1,
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
GET /api/v1/menu-items?page=1&per_page=20

# フィルタリング
GET /api/v1/menu-items?category_id=1&is_available=true

# ソート
GET /api/v1/menu-items?sort=price&order=asc

# 検索
GET /api/v1/menu-items?q=ラーメン
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
      "items.1.menu_item_id": [
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
POST   /api/v1/auth/session          # QRコードセッション開始
POST   /api/v1/auth/refresh          # トークンリフレッシュ
POST   /api/v1/auth/logout           # ログアウト
```

### 6.2 メニューAPI
```
GET    /api/v1/menu-categories       # カテゴリ一覧
GET    /api/v1/menu-items            # メニュー一覧
GET    /api/v1/menu-items/{id}      # メニュー詳細
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
PUT    /api/v1/pos/menu-items/{id}  # メニュー在庫更新
```

### 6.5 管理API
```
# メニュー管理
GET    /api/v1/admin/menu-items     # メニュー一覧（管理用）
POST   /api/v1/admin/menu-items     # メニュー作成
PUT    /api/v1/admin/menu-items/{id} # メニュー更新
DELETE /api/v1/admin/menu-items/{id} # メニュー削除

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
class MenuItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::with(['category', 'options'])
            ->where('is_active', true);
            
        // フィルタリング
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // ページネーション
        $items = $query->paginate($request->get('per_page', 20));
        
        return $this->successResponse(
            data: MenuItemResource::collection($items),
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

### 9.2 エラーハンドリング
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

---

このAPI設計書に従って実装することで、一貫性のあるRESTful APIを構築できます。