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
  - [4.2 メニューAPI（読み取り専用）](#42-メニューapi読み取り専用)
  - [4.3 注文・カートAPI](#43-注文カートapi)
  - [4.4 POS連携API](#44-pos連携api)
  - [4.5 POS専用API（商品データ操作）](#45-pos専用api商品データ操作)
  - [4.6 管理API（読み取り専用）](#46-管理api読み取り専用)
- [5. API詳細仕様](#5-api詳細仕様)
  - [5.0 メニューAPI（読み取り専用）](#50-メニューapi読み取り専用)
  - [5.1 認証・セッション管理API](#51-認証セッション管理api)
  - [5.2 カート操作API](#52-カート操作api)
  - [5.3 注文作成API](#53-注文作成api)
  - [5.4 セッション注文履歴API（席全体の注文状況）](#54-セッション注文履歴api席全体の注文状況)
  - [5.5 POS連携API](#55-pos連携api)
  - [5.6 管理API（読み取り専用）](#56-管理api読み取り専用)
  - [5.7 POS専用API（商品データ操作）](#57-pos専用api商品データ操作)
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
- **[POST /api/v1/auth/session/start](#席セッション開始)** - QRコードセッション開始
- **[POST /api/v1/auth/session/refresh](#セッションリフレッシュ)** - セッショントークンリフレッシュ
- **[POST /api/v1/auth/session/end](#セッション終了)** - セッション終了

#### ゲストセッション（自動延長）
- **[POST /api/v1/auth/guest/start](#ゲストセッション開始)** - ゲストセッション開始
- **[DELETE /api/v1/auth/guest/end](#ゲストセッション終了)** - ゲストセッション終了

**注：各API実行時に自動的にexpires_at延長（30分）**

### 4.2 メニューAPI（読み取り専用）
- **[GET /api/v1/categories](#カテゴリ一覧)** - カテゴリ一覧（フィルタリング用）
- **[GET /api/v1/products](#商品一覧統合メニュー対応)** - 全商品一覧（画像・番号両モード対応）
- **[GET /api/v1/products/{id}](#商品詳細)** - 商品詳細
- **[GET /api/v1/products/{id}/options](#商品オプション一覧)** - 商品のオプション一覧

### 4.3 注文・カートAPI

#### 注文管理
- **[POST /api/v1/orders](#ゲスト注文作成2層認証対応)** - 注文作成
- **[GET /api/v1/orders/{id}](#注文詳細取得)** - 注文詳細
- **[GET /api/v1/sessions/{id}/orders](#注文履歴取得ゲスト識別アイコン付き)** - セッション注文履歴（席全体）

#### カート管理
- **[POST /api/v1/guest/cart/items](#カートアイテム追加)** - カートアイテム追加
- **[GET /api/v1/guest/cart](#カート内容取得)** - カート内容取得
- **[DELETE /api/v1/guest/cart/items/{product_id}](#カートアイテム削除)** - アイテム削除
- **[DELETE /api/v1/guest/cart](#カートクリア)** - カートクリア

### 4.4 POS連携API
- **[GET /api/v1/pos/health](#webシステムヘルスチェック)** - Webシステムヘルスチェック
- **[POST /api/v1/pos/health/check](#posヘルスチェック更新)** - POSヘルスチェック更新
- **[POST /api/v1/pos/sessions](#セッション新規作成pos側)** - 席セッション作成
- **[POST /api/v1/pos/sessions/extend](#席セッション延長)** - 席セッション延長
- **[POST /api/v1/pos/sessions/complete](#席セッション完了会計処理)** - 席セッション完了（会計処理）
- **[GET /api/v1/pos/changes](#変更履歴取得ポーリング)** - 変更履歴取得（ポーリング）
- **[POST /api/v1/pos/changes/sync](#同期完了通知)** - 同期完了通知
- **[POST /api/v1/pos/recovery/start](#障害復旧開始)** - 障害復旧開始
- **[POST /api/v1/pos/recovery/complete](#障害復旧完了)** - 障害復旧完了
- **[POST /api/v1/pos/sync-sessions](#セッション同期)** - セッション同期
- **[POST /api/v1/pos/sync-orders](#注文同期)** - 注文同期
- **[GET /api/v1/pos/orders](#pos注文一覧取得)** - 注文一覧取得
- **[PUT /api/v1/pos/orders/{id}](#pos注文ステータス更新)** - 注文ステータス更新
- **[POST /api/v1/pos/orders/{id}/cancel](#注文キャンセルハンディ端末専用)** - 注文キャンセル（ハンディ端末専用）
- **[PUT /api/v1/pos/products/{id}](#商品提供状態更新)** - 商品提供状態更新
- **[POST /api/v1/pos/auth/login](#posログイン認証)** - POSログイン認証
- **[POST /api/v1/pos/auth/refresh](#posトークン更新)** - POSトークン更新
- **[POST /api/v1/pos/translations/sync](#多言語翻訳同期)** - 多言語翻訳同期

### 4.5 POS専用API（商品データ操作）

#### 商品マスター管理（POS端末からのみアクセス可能）
- **[GET /api/v1/pos/products](#pos商品一覧)** - 商品一覧
- **[POST /api/v1/pos/products](#商品作成)** - 商品作成
- **[PUT /api/v1/pos/products/{id}](#pos商品更新)** - 商品更新
- **[DELETE /api/v1/pos/products/{id}](#pos商品削除)** - 商品削除

#### カテゴリマスター管理（POS端末からのみアクセス可能）
- **[GET /api/v1/pos/categories](#posカテゴリ一覧)** - カテゴリ一覧
- **[POST /api/v1/pos/categories](#カテゴリ作成)** - カテゴリ作成
- **[PUT /api/v1/pos/categories/{id}](#カテゴリ更新)** - カテゴリ更新
- **[DELETE /api/v1/pos/categories/{id}](#posカテゴリ削除)** - カテゴリ削除

#### オプションマスター管理（POS端末からのみアクセス可能）
- **[GET /api/v1/pos/options](#posオプション一覧)** - オプション一覧
- **[POST /api/v1/pos/options](#posオプション作成)** - オプション作成
- **[PUT /api/v1/pos/options/{id}](#posオプション更新)** - オプション更新
- **[DELETE /api/v1/pos/options/{id}](#posオプション削除)** - オプション削除

### 4.6 管理API（読み取り専用）

#### メニュー閲覧（POS専用のCRUD操作により作成されたデータの表示のみ）
- **[GET /api/v1/admin/products](#商品一覧管理画面用)** - 商品一覧（読み取り専用）
- **[GET /api/v1/admin/products/{id}](#管理商品詳細)** - 商品詳細（読み取り専用）
- **[GET /api/v1/admin/categories](#管理カテゴリ一覧)** - カテゴリ一覧（読み取り専用）
- **[GET /api/v1/admin/categories/{id}](#管理カテゴリ詳細)** - カテゴリ詳細（読み取り専用）
- **[GET /api/v1/admin/options](#管理オプション一覧)** - オプション一覧（読み取り専用）
- **[GET /api/v1/admin/options/{id}](#管理オプション詳細)** - オプション詳細（読み取り専用）

#### Web固有設定管理（管理画面で変更可能）
- **[GET /api/v1/admin/settings](#システム設定一覧)** - システム設定一覧
- **[PUT /api/v1/admin/settings/{key}](#システム設定更新)** - システム設定更新

#### ユーザー管理（管理画面で変更可能）
- **[GET /api/v1/admin/users](#ユーザー一覧)** - ユーザー一覧
- **[POST /api/v1/admin/users](#管理ユーザー作成)** - ユーザー作成
- **[PUT /api/v1/admin/users/{id}](#管理ユーザー更新)** - ユーザー更新
- **[DELETE /api/v1/admin/users/{id}](#管理ユーザー削除)** - ユーザー削除

#### セッション管理（読み取り専用）
- **[GET /api/v1/admin/sessions](#セッション一覧読み取り専用)** - セッション一覧（読み取り専用）
- **[GET /api/v1/admin/sessions/{id}](#管理セッション詳細)** - セッション詳細（読み取り専用）

#### レポート（読み取り専用）
- **[GET /api/v1/admin/reports/sales](#売上レポート)** - 売上レポート
- **[GET /api/v1/admin/reports/products](#商品分析レポート)** - 商品分析レポート

#### 店舗URL管理（複数店舗対応）
- **[GET /api/v1/admin/stores/{id}/admin-urls](#店舗url履歴一覧)** - 店舗URL履歴一覧
- **[POST /api/v1/admin/stores/{id}/admin-urls](#新url作成)** - 新URL作成
- **[PUT /api/v1/admin/stores/{id}/admin-urls/{id}](#url無効化)** - URL無効化

#### ゲスト識別管理
- **[GET /api/v1/guest/identifier](#ゲスト識別情報取得)** - ゲスト識別情報取得
- **[POST /api/v1/guest/identifier](#ゲスト識別情報作成)** - ゲスト識別情報作成

## 5. API詳細仕様

### 5.1 認証・セッション管理API

---

#### 🔐 席セッション開始
```http
POST /api/v1/auth/session/start
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: QRコード読み取り→画面表示後のLivewire/JavaScript処理で自動実行
- **目的**: 席セッションの検証と開始
- **技術的補足**: QRコードは GET `/s/{session_token}` でページ表示、その後このPOST APIを自動実行

**リクエスト**
```json
{
  "session_id": "SESSION_POS_20240101_140000_12_001",
  "guest_token": "guest_abc123def456",
  "device_fingerprint": "device_fingerprint_hash_xyz",
  "language": "ja"
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "session": {
      "session_id": "SESSION_POS_20240101_140000_12_001",
      "table_number": "12",
      "customer_count": null,
      "status": "active",
      "expires_at": "2024-01-01T17:00:00+09:00",
      "started_at": "2024-01-01T14:00:00+09:00"
    },
    "guest_token": "guest_abc123def456",
    "expires_in": 1800
  },
  "message": "セッションが開始されました"
}
```

---

#### 🔐 セッションリフレッシュ
```http
POST /api/v1/auth/session/refresh
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: セッション期限が近づいた時（自動）
- **目的**: セッションの有効期限延長

**リクエスト**
```json
{
  "session_id": "SESSION_POS_20240101_140000_12_001",
  "guest_token": "guest_abc123def456"
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "new_expires_at": "2024-01-01T18:00:00+09:00",
    "expires_in": 1800
  },
  "message": "セッションが延長されました"
}
```

---

#### 🔐 セッション終了
```http
POST /api/v1/auth/session/end
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 利用終了時（任意）
- **目的**: セッションの明示的な終了

**リクエスト**
```json
{
  "session_id": "SESSION_POS_20240101_140000_12_001",
  "guest_token": "guest_abc123def456"
}
```

**レスポンス**
```json
{
  "success": true,
  "message": "セッションが終了されました"
}
```

---

#### 🔐 ゲストセッション開始
```http
POST /api/v1/auth/guest/start
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 席セッション開始後の自動処理
- **目的**: 個人識別用ゲストトークンの生成

**リクエスト**
```json
{
  "session_id": "SESSION_POS_20240101_140000_12_001",
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
      "session_id": "SESSION_POS_20240101_140000_12_001",
      "table_number": "12"
    }
  },
  "message": "ゲストセッションが開始されました"
}
```

**注：各API呼び出し時に自動的にexpires_at延長（30分）**

- DB TTL管理: guest_sessions.expires_at, carts.expires_at
- Laravel Scheduled Taskで期限切れデータ自動削除
- 自動延長により継続的な利用をサポート

---

#### 🔐 ゲストセッション終了
```http
DELETE /api/v1/auth/guest/end
Authorization: Bearer guest_abc123def456
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 利用終了時（任意）
- **目的**: ゲストセッションの明示的な終了

**レスポンス**
```json
{
  "success": true,
  "message": "ゲストセッションが終了されました"
}
```

### 5.2 メニューAPI（読み取り専用）

---

#### 📋 カテゴリ一覧
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

**カテゴリオブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | カテゴリID |
| name | string | ✓ | カテゴリ名 |
| translations | object | - | 多言語翻訳データ（キー：言語コード、値：翻訳文） |
| sort_order | integer | ✓ | 表示順序 |
| is_active | boolean | ✓ | 有効フラグ |

---

#### 📋 商品一覧（統合メニュー対応）
```http
GET /api/v1/products
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: メニュー画面表示時・カテゴリ切り替え時
- **目的**: 全商品一覧（画像・番号両モード対応）

**パラメータ**
```
?category_id=1&language=ja&mode=image
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "醤油ラーメン",
        "description": "あっさりとした醤油ベースのラーメン",
        "price": 800,
        "image_url": "https://example.com/images/shoyu_ramen.jpg",
        "menu_number": "R001",
        "category_id": 1,
        "availability_status": "available",
        "options": [
          {
            "id": 1,
            "name": "麺の硬さ",
            "is_required": true,
            "choices": [
              {"id": 1, "name": "固め", "price": 0},
              {"id": 2, "name": "普通", "price": 0},
              {"id": 3, "name": "柔らかめ", "price": 0}
            ]
          }
        ]
      }
    ]
  }
}
```

---

#### 📋 商品詳細
```http
GET /api/v1/products/{id}
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 商品タップ時・詳細確認時
- **目的**: 特定商品の詳細情報取得

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "醤油ラーメン",
    "description": "あっさりとした醤油ベースのラーメン",
    "price": 800,
    "image_url": "https://example.com/images/shoyu_ramen.jpg",
    "menu_number": "R001",
    "category_id": 1,
    "availability_status": "available",
    "allergens": ["小麦", "大豆"],
    "nutritional_info": {
      "calories": 450,
      "protein": 18,
      "carbs": 65,
      "fat": 12
    }
  }
}
```

---

#### 📋 商品オプション一覧
```http
GET /api/v1/products/{id}/options
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: 商品詳細モーダル表示時
- **目的**: 商品のオプション一覧取得

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "麺の硬さ",
      "is_required": true,
      "choices": [
        {"id": 1, "name": "固め", "price": 0},
        {"id": 2, "name": "普通", "price": 0},
        {"id": 3, "name": "柔らかめ", "price": 0}
      ]
    },
    {
      "id": 2,
      "name": "トッピング",
      "is_required": false,
      "choices": [
        {"id": 4, "name": "チャーシュー", "price": 200},
        {"id": 5, "name": "メンマ", "price": 100},
        {"id": 6, "name": "ネギ", "price": 50}
      ]
    }
  ]
}
```

### 5.3 注文・カートAPI

---

#### 🛒 注文作成
```http
POST /api/v1/orders
Authorization: Bearer guest_abc123def456
```

**呼び出しタイミング**
- **誰が**: スマホアプリ（フロントエンド）
- **いつ**: カート内容確定後の注文送信時
- **目的**: ゲスト注文作成（2層認証対応）

**リクエスト**
```json
{
  "session_id": "SESSION_POS_20240101_140000_12_001",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "options": [
        {"option_id": 1, "choice_id": 2},
        {"option_id": 2, "choice_id": 4}
      ]
    }
  ]
}
```

**レスポンス**
```json
{
  "success": true,
  "data": {
    "order_id": 123,
    "total_amount": 1600,
    "estimated_time": 15,
    "items": [
      {
        "product_name": "醤油ラーメン",
        "quantity": 2,
        "unit_price": 800,
        "options": [
          {"name": "麺の硬さ", "choice": "普通"},
          {"name": "トッピング", "choice": "チャーシュー"}
        ]
      }
    ]
  },
  "message": "注文を受け付けました"
}
```

---

**📝 注：** 
- この5章は4章のエンドポイント一覧の順序に完全対応するよう整理されています
- 各APIタイトルには絵文字を使用して視認性を向上させています
- 詳細な仕様は段階的に追加予定です

**🔄 今後の追加予定セクション:**
- **5.4 POS連携API** - ヘルスチェック、セッション管理、変更履歴、障害復旧
- **5.5 POS専用API** - 商品データ操作、マスター管理  
- **5.6 管理API** - 読み取り専用の管理機能
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
