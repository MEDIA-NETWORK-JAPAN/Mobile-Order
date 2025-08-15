# POS連携API仕様書

このドキュメントは、Mobile Order SystemにおけるPOSシステム連携専用のAPI仕様をまとめたものです。POS端末（Delphi + FireBird）からWebシステム（Laravel）への接続に必要な全APIを網羅しています。

---

## 目次

1. [認証・基本事項](#1-認証基本事項)
2. [システム監視API](#2-システム監視api)
3. [セッション管理API](#3-セッション管理api)
4. [注文管理API](#4-注文管理api)
5. [データ同期API](#5-データ同期api)
6. [商品マスター管理API](#6-商品マスター管理api)
7. [カテゴリマスター管理API](#7-カテゴリマスター管理api)
8. [多言語翻訳API](#8-多言語翻訳api)
9. [エラーハンドリング](#9-エラーハンドリング)
10. [認証API](#10-認証api)

---

## 1. 認証・基本事項

### 認証方式
- **認証**: Laravel Sanctum（Bearer Token）
- **ヘッダー**: `Authorization: Bearer pos_system_token`

### ベースURL
- **本番**: `https://your-domain.com/api/v1/pos`
- **開発**: `http://localhost:8080/api/v1/pos`

### 共通レスポンス形式
```json
{
  "success": true,
  "data": { ... },
  "message": "成功メッセージ",
  "timestamp": "2024-01-01T12:00:00+09:00"
}
```

---

## 2. システム監視API

### 2.1 Webシステムヘルスチェック

```http
GET /api/v1/pos/health
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（Delphiアプリケーション）
- **いつ**: 5秒毎の定期ヘルスチェック
- **目的**: Webシステムの死活監視、通信断絶の早期検知

**レスポンス（正常時）**
```json
{
  "status": "ok",
  "timestamp": "2024-01-01T12:30:00+09:00",
  "server_time": "2024-01-01T12:30:00+09:00",
  "database_status": "connected",
  "redis_status": "connected"
}
```

**レスポンス（異常時）**
```json
{
  "status": "error",
  "timestamp": "2024-01-01T12:30:00+09:00", 
  "errors": [
    "Database connection failed",
    "Redis connection timeout"
  ]
}
```

**ステータスコード**
- **200 OK**: システム正常
- **503 Service Unavailable**: システム異常

---

## 3. セッション管理API

### 3.1 席セッション作成

```http
POST /api/v1/pos/sessions
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（Delphiアプリケーション）
- **いつ**: スタッフが席番号入力後
- **目的**: QRコード発行のための席セッション作成

**リクエスト**
```json
{
  "session_token": "SESSION_POS_123_20241213_120000",
  "table_number": 8,
  "store_id": 1,
  "expires_at": "2024-12-13T15:00:00+09:00"
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| session_token | string | ✓ | POS端末で生成したセッションID |
| table_number | integer | ✓ | テーブル番号 |
| store_id | integer | ✓ | 店舗ID |
| expires_at | datetime | ✓ | セッション有効期限 |

**レスポンス（成功時）**
```json
{
  "success": true,
  "session_id": 456,
  "qr_url": "/s/SESSION_POS_123_20241213_120000",
  "created_at": "2024-12-13T12:00:00+09:00"
}
```

**ステータスコード**
- **201 Created**: セッション作成成功
- **400 Bad Request**: リクエストパラメータエラー
- **409 Conflict**: セッショントークンの重複

### 3.2 席セッション延長

```http
POST /api/v1/pos/sessions/extend
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末
- **いつ**: セッション有効期限延長が必要な時
- **目的**: 営業時間延長等でのセッション継続

**リクエスト**
```json
{
  "session_token": "SESSION_POS_123_20241213_120000",
  "extend_minutes": 60
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| session_token | string | ✓ | 延長対象のセッションID |
| extend_minutes | integer | ✓ | 延長時間（分）|

**レスポンス**
```json
{
  "success": true,
  "session": {
    "session_token": "SESSION_POS_123_20241213_120000",
    "table_number": 8,
    "new_expires_at": "2024-12-13T16:00:00+09:00",
    "extended_minutes": 60,
    "total_extensions": 1
  },
  "message": "セッションを延長しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| session | object | ✓ | セッション情報 |
| session.session_token | string | ✓ | セッションID |
| session.table_number | integer | ✓ | テーブル番号 |
| session.new_expires_at | datetime | ✓ | 新しい有効期限 |
| session.extended_minutes | integer | ✓ | 延長された時間（分） |
| session.total_extensions | integer | ✓ | 延長回数 |
| message | string | ✓ | 処理結果メッセージ |

---

## 4. 注文管理API

### 4.1 注文一覧取得

```http
GET /api/v1/pos/orders
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（注文管理機能）
- **いつ**: 注文状況確認時、一覧表示リフレッシュ時
- **目的**: 店舗内の全注文状況を取得しステータス管理

**クエリパラメータ（オプション）**

| パラメータ | 型 | 説明 |
|-----------|---|------|
| status | string | 注文ステータスでフィルタ（"pending", "preparing", "completed", "cancelled"） |
| table_number | string | テーブル番号でフィルタ |
| limit | integer | 取得件数制限（デフォルト：50） |
| offset | integer | オフセット（ページネーション用） |

**レスポンス**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "order_number": "2024010112345",
      "session_id": 789,
      "table_number": "8",
      "status": "preparing",
      "total_amount": 1500,
      "order_source": "mobile_web",
      "ordered_at": "2024-01-01T12:30:00+09:00",
      "guest_identifier": "🐶",
      "items_summary": "醤油ラーメン×1, チャーシュー追加×1",
      "item_count": 2,
      "notes": "テイクアウトでお願いします"
    }
  ],
  "pagination": {
    "total": 25,
    "current_page": 1,
    "per_page": 50,
    "last_page": 1,
    "has_more": false
  }
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | array | ✓ | 注文一覧 |
| pagination | object | ✓ | ページネーション情報 |

**注文オブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 注文ID |
| order_number | string | ✓ | 注文番号 |
| session_id | integer | ✓ | セッションID |
| table_number | string | ✓ | テーブル番号 |
| status | string | ✓ | 注文ステータス |
| total_amount | integer | ✓ | 注文合計金額（税込） |
| order_source | string | ✓ | 注文元（"mobile_web", "handy_terminal"） |
| ordered_at | datetime | ✓ | 注文日時 |
| guest_identifier | string | ✓ | ゲスト識別子（絵文字） |
| items_summary | string | ✓ | 注文商品概要 |
| item_count | integer | ✓ | 商品アイテム数 |
| notes | string | - | 注文メモ |

**ページネーションオブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| total | integer | ✓ | 総件数 |
| current_page | integer | ✓ | 現在のページ |
| per_page | integer | ✓ | ページあたりの件数 |
| last_page | integer | ✓ | 最終ページ |
| has_more | boolean | ✓ | 次ページ有無フラグ |
```

### 4.2 注文ステータス更新

```http
PUT /api/v1/pos/orders/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末
- **いつ**: 調理開始時、調理完了時
- **目的**: 注文の進行状況をWebシステムに反映

**リクエスト**
```json
{
  "status": "preparing",
  "updated_by": "pos_system",
  "notes": "調理開始しました",
  "estimated_completion": "2024-01-01T12:50:00+09:00"
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| status | string | ✓ | 注文ステータス |
| updated_by | string | ✓ | 更新者識別子 |
| notes | string | - | 更新メモ |
| estimated_completion | datetime | - | 完成予定時刻 |

**ステータス値**
- `pending`: 受付中
- `preparing`: 調理中
- `completed`: 調理完了
- `cancelled`: キャンセル済み

**レスポンス**
```json
{
  "success": true,
  "order": {
    "id": 123,
    "order_number": "2024010112345",
    "status": "preparing",
    "previous_status": "pending",
    "updated_at": "2024-01-01T12:35:00+09:00",
    "estimated_completion": "2024-01-01T12:50:00+09:00",
    "status_history": [
      {
        "status": "pending",
        "updated_at": "2024-01-01T12:30:00+09:00"
      },
      {
        "status": "preparing",
        "updated_at": "2024-01-01T12:35:00+09:00"
      }
    ]
  },
  "message": "注文ステータスを更新しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| order | object | ✓ | 更新された注文情報 |
| order.id | integer | ✓ | 注文ID |
| order.order_number | string | ✓ | 注文番号 |
| order.status | string | ✓ | 新しいステータス |
| order.previous_status | string | ✓ | 以前のステータス |
| order.updated_at | datetime | ✓ | 更新日時 |
| order.estimated_completion | datetime | - | 完成予定時刻 |
| order.status_history | array | ✓ | ステータス履歴 |
| message | string | ✓ | 処理結果メッセージ |

### 4.3 注文キャンセル（ハンディ端末専用）

```http
POST /api/v1/pos/orders/{id}/cancel
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: ハンディ端末
- **いつ**: スタッフが注文キャンセルを実行時
- **目的**: 注文のキャンセル処理

**リクエスト**
- リクエストボディなし（URLパスの注文IDとPOS認証トークンで処理）

**パスパラメータ**

| パラメータ | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | キャンセル対象の注文ID |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "order_id": 123,
    "status": "cancelled",
    "cancelled_at": "2024-01-01T15:00:00+09:00",
    "refund_amount": 1500
  },
  "message": "注文をキャンセルしました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | キャンセル処理結果 |
| data.order_id | integer | ✓ | 注文ID |
| data.status | string | ✓ | 注文ステータス（"cancelled"固定） |
| data.cancelled_at | datetime | ✓ | キャンセル日時（ISO 8601形式） |
| data.refund_amount | integer | ✓ | 返金額（税込、円） |
| message | string | ✓ | 処理結果メッセージ |

---

## 5. データ同期API

### 5.1 変更履歴取得（ポーリング）

```http
GET /api/v1/pos/changes
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末
- **いつ**: 5秒毎の定期ポーリング
- **目的**: Webシステム→POSシステムへのデータ同期

**レスポンス**
```json
{
  "success": true,
  "changes": [
    {
      "id": 158,
      "entity_type": "order",
      "entity_id": 158,
      "action": "created",
      "changes": {
        "order_id": 158,
        "table_number": 8,
        "items": [
          {
            "product_id": 23,
            "product_name": "醤油ラーメン",
            "quantity": 2,
            "unit_price": 800,
            "options": [
              {"name": "麺の硬さ", "value": "普通"},
              {"name": "チャーシュー", "value": "追加"}
            ]
          }
        ],
        "total_amount": 1760,
        "ordered_at": "2024-12-13T12:45:00Z"
      },
      "created_at": "2024-12-13T12:45:01Z"
    }
  ],
  "has_more": false
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| changes | array | ✓ | 変更データ配列 |
| has_more | boolean | ✓ | 追加データ有無フラグ |

**変更データオブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 変更ログID |
| entity_type | string | ✓ | エンティティタイプ |
| entity_id | integer | ✓ | エンティティID |
| action | string | ✓ | アクション（created/updated/deleted） |
| changes | object | ✓ | 変更内容の詳細 |
| created_at | datetime | ✓ | 変更発生日時 |

**エンティティタイプ**
- `order`: 注文
- `product_availability`: 商品提供状況
- `session`: セッション情報
- `category`: カテゴリ
- `product`: 商品

**changesオブジェクト内容（注文の場合）**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| order_id | integer | ✓ | 注文ID |
| table_number | integer | ✓ | テーブル番号 |
| items | array | ✓ | 注文商品一覧 |
| total_amount | integer | ✓ | 注文合計金額 |
| ordered_at | datetime | ✓ | 注文日時 |

**itemsオブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| product_id | integer | ✓ | 商品ID |
| product_name | string | ✓ | 商品名 |
| quantity | integer | ✓ | 数量 |
| unit_price | integer | ✓ | 単価 |
| options | array | - | 選択されたオプション |

**optionsオブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| option_id | integer | ✓ | オプションID |
| option_name | string | ✓ | オプション名 |
| product_id | integer | ✓ | 選択肢商品ID |
| product_name | string | ✓ | 選択肢名 |
| price | integer | ✓ | オプション追加料金
```

### 5.2 同期完了通知

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
  "store_id": 1,
  "sync_result": "success",
  "failed_ids": []
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| change_log_ids | array | ✓ | 同期完了した変更ログID配列 |
| synced_at | datetime | ✓ | 同期完了日時 |
| store_id | integer | ✓ | 店舗ID |
| sync_result | string | ✓ | 同期結果（success/partial/failed） |
| failed_ids | array | - | 同期に失敗した変更ログID配列 |

**レスポンス**
```json
{
  "success": true,
  "synced_count": 3,
  "failed_count": 0,
  "next_sync_recommended": "2024-01-01T12:32:00+09:00",
  "message": "同期完了しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| synced_count | integer | ✓ | 同期成功件数 |
| failed_count | integer | ✓ | 同期失敗件数 |
| next_sync_recommended | datetime | - | 次回同期推奨時刻 |
| message | string | ✓ | 処理結果メッセージ |

---

## 6. 商品マスター管理API

### 6.1 商品一覧取得

```http
GET /api/v1/pos/products
Authorization: Bearer pos_system_token
```

**クエリパラメータ（オプション）**

| パラメータ | 型 | 説明 |
|-----------|---|------|
| category_id | integer | カテゴリIDでフィルタ |
| availability_status | string | 提供状況でフィルタ |
| limit | integer | 取得件数制限（デフォルト：100） |
| offset | integer | オフセット（ページネーション用） |

**レスポンス**
```json
{
  "success": true,
  "products": [
    {
      "id": 1,
      "code": "R001",
      "name": "醤油ラーメン",
      "description": "あっさりとした醤油ベースのラーメン",
      "price": 800,
      "tax_in_price": 880,
      "category_id": 1,
      "category_name": "ラーメン",
      "availability_status": "available",
      "sort_order": 1,
      "image_url": "https://example.com/images/shoyu-ramen.jpg",
      "created_at": "2024-01-01T00:00:00+09:00",
      "updated_at": "2024-01-01T00:00:00+09:00"
    }
  ],
  "pagination": {
    "total": 25,
    "current_page": 1,
    "per_page": 100,
    "last_page": 1,
    "has_more": false
  }
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| products | array | ✓ | 商品一覧 |
| pagination | object | ✓ | ページネーション情報 |

**商品オブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 商品ID |
| code | string | ✓ | 商品コード |
| name | string | ✓ | 商品名 |
| description | string | ✓ | 商品説明 |
| price | integer | ✓ | 商品価格（税抜、円） |
| tax_in_price | integer | ✓ | 商品価格（税込、円） |
| category_id | integer | ✓ | カテゴリID |
| category_name | string | ✓ | カテゴリ名 |
| availability_status | string | ✓ | 提供状況 |
| sort_order | integer | ✓ | 表示順序 |
| image_url | string | - | 商品画像URL |
| created_at | datetime | ✓ | 作成日時 |
| updated_at | datetime | ✓ | 更新日時 |

### 6.2 商品作成

```http
POST /api/v1/pos/products
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（商品マスター管理機能）
- **いつ**: 新商品登録時、メニュー追加時
- **目的**: 新しい商品をWebシステムに同期

**リクエスト**
```json
{
  "code": "R003",
  "name": "味噌ラーメン",
  "description": "コクのある味噌スープ",
  "price": 850,
  "category_id": 1,
  "image_url": "https://example.com/images/miso-ramen.jpg",
  "availability_status": "available",
  "sort_order": 3,
  "translations": {
    "en": {
      "name": "Miso Ramen",
      "description": "Rich miso-based soup ramen"
    },
    "zh-tw": {
      "name": "味噌拉麵",
      "description": "濃郁的味噌湯底拉麵"
    }
  }
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| code | string | ✓ | 商品コード（POS表示・番号入力用） |
| name | string | ✓ | 商品名 |
| description | string | ✓ | 商品説明 |
| price | integer | ✓ | 商品価格（税抜、円） |
| category_id | integer | ✓ | カテゴリID |
| image_url | string | - | 商品画像URL |
| availability_status | string | ✓ | 提供状態 |
| sort_order | integer | ✓ | 表示順序 |
| translations | object | - | 多言語翻訳データ |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 25,
    "code": "R003",
    "name": "味噌ラーメン",
    "description": "コクのある味噌スープ",
    "price": 850,
    "tax_in_price": 935,
    "category_id": 1,
    "availability_status": "available",
    "sort_order": 3,
    "created_at": "2024-01-01T16:00:00+09:00"
  },
  "message": "商品を作成しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 作成された商品情報 |
| data.id | integer | ✓ | 作成された商品ID |
| data.code | string | ✓ | 商品コード |
| data.name | string | ✓ | 商品名 |
| data.description | string | ✓ | 商品説明 |
| data.price | integer | ✓ | 商品価格（税抜、円） |
| data.tax_in_price | integer | ✓ | 商品価格（税込、円） |
| data.category_id | integer | ✓ | カテゴリID |
| data.availability_status | string | ✓ | 提供状態 |
| data.sort_order | integer | ✓ | 表示順序 |
| data.created_at | datetime | ✓ | 作成日時 |
| message | string | ✓ | 処理結果メッセージ |

### 6.3 商品更新

```http
PUT /api/v1/pos/products/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（商品マスター管理機能）
- **いつ**: 商品情報変更時
- **目的**: 商品情報の更新をWebシステムに同期

**パスパラメータ**

| パラメータ | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 更新対象の商品ID |

**リクエスト**
```json
{
  "code": "R001",
  "name": "醤油ラーメン",
  "description": "あっさりとした醤油ベースのラーメン",
  "price": 850,
  "category_id": 1,
  "image_url": "https://example.com/images/shoyu-ramen-new.jpg",
  "availability_status": "available",
  "sort_order": 1
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| code | string | - | 商品コード |
| name | string | - | 商品名 |
| description | string | - | 商品説明 |
| price | integer | - | 商品価格（税抜、円） |
| category_id | integer | - | カテゴリID |
| image_url | string | - | 商品画像URL |
| availability_status | string | - | 提供状態 |
| sort_order | integer | - | 表示順序 |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "code": "R001",
    "name": "醤油ラーメン",
    "description": "あっさりとした醤油ベースのラーメン",
    "price": 850,
    "tax_in_price": 935,
    "category_id": 1,
    "availability_status": "available",
    "sort_order": 1,
    "updated_at": "2024-01-01T16:00:00+09:00"
  },
  "message": "商品を更新しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 更新された商品情報 |
| data.id | integer | ✓ | 商品ID |
| data.code | string | ✓ | 商品コード |
| data.name | string | ✓ | 商品名 |
| data.description | string | ✓ | 商品説明 |
| data.price | integer | ✓ | 商品価格（税抜、円） |
| data.tax_in_price | integer | ✓ | 商品価格（税込、円） |
| data.category_id | integer | ✓ | カテゴリID |
| data.availability_status | string | ✓ | 提供状態 |
| data.sort_order | integer | ✓ | 表示順序 |
| data.updated_at | datetime | ✓ | 更新日時 |
| message | string | ✓ | 処理結果メッセージ |

### 6.4 商品削除

```http
DELETE /api/v1/pos/products/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（商品マスター管理機能）
- **いつ**: 商品削除時
- **目的**: 商品の削除をWebシステムに同期

**パスパラメータ**

| パラメータ | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 削除対象の商品ID |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "deleted_at": "2024-01-01T16:00:00+09:00"
  },
  "message": "商品を削除しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 削除結果 |
| data.id | integer | ✓ | 削除された商品ID |
| data.deleted_at | datetime | ✓ | 削除日時 |
| message | string | ✓ | 処理結果メッセージ |

### 6.5 商品提供状態更新

```http
PUT /api/v1/pos/products/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末
- **いつ**: 売り切れ発生時、商品復活時
- **目的**: リアルタイム在庫状況の反映

**パスパラメータ**

| パラメータ | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 更新対象の商品ID |

**リクエスト**
```json
{
  "availability_status": "sold_out",
  "updated_reason": "sold_out_notification",
  "updated_at": "2024-01-01T16:30:00+09:00"
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| availability_status | string | ✓ | 提供状態 |
| updated_reason | string | ✓ | 更新理由コード |
| updated_at | datetime | - | 更新日時 |

**availability_status値**
- `available`: 販売可能
- `sold_out`: 売り切れ
- `not_arrived`: 未入荷
- `preparing`: 準備中

**updated_reason値**
- `sold_out_notification`: 売り切れ通知
- `stock_recovery`: 在庫回復
- `kitchen_ready`: 厨房準備完了
- `supply_delay`: 仕入れ遅延

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "code": "R001",
    "name": "醤油ラーメン",
    "availability_status": "sold_out",
    "previous_status": "available",
    "updated_at": "2024-01-01T16:30:00+09:00",
    "updated_reason": "sold_out_notification"
  },
  "message": "商品提供状態を更新しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 更新された商品情報 |
| data.id | integer | ✓ | 商品ID |
| data.code | string | ✓ | 商品コード |
| data.name | string | ✓ | 商品名 |
| data.availability_status | string | ✓ | 新しい提供状態 |
| data.previous_status | string | ✓ | 以前の提供状態 |
| data.updated_at | datetime | ✓ | 更新日時 |
| data.updated_reason | string | ✓ | 更新理由 |
| message | string | ✓ | 処理結果メッセージ |

---

## 7. カテゴリマスター管理API

### 7.1 カテゴリ作成

```http
POST /api/v1/pos/categories
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（カテゴリマスター管理機能）
- **いつ**: 新カテゴリ作成時
- **目的**: 新しいカテゴリをWebシステムに同期

**リクエスト**
```json
{
  "name": "デザート",
  "sort_order": 5,
  "is_active": true,
  "translations": {
    "en": "Dessert",
    "zh-tw": "甜點",
    "zh-cn": "甜点",
    "ko": "디저트"
  }
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| name | string | ✓ | カテゴリ名 |
| sort_order | integer | ✓ | 表示順序 |
| is_active | boolean | ✓ | 有効フラグ |
| translations | object | - | 多言語翻訳データ |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "デザート",
    "sort_order": 5,
    "is_active": true,
    "created_at": "2024-01-01T16:05:00+09:00"
  },
  "message": "カテゴリを作成しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 作成されたカテゴリ情報 |
| data.id | integer | ✓ | 作成されたカテゴリID |
| data.name | string | ✓ | カテゴリ名 |
| data.sort_order | integer | ✓ | 表示順序 |
| data.is_active | boolean | ✓ | 有効フラグ |
| data.created_at | datetime | ✓ | 作成日時 |
| message | string | ✓ | 処理結果メッセージ |

### 7.2 カテゴリ更新

```http
PUT /api/v1/pos/categories/{id}
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（カテゴリマスター管理機能）
- **いつ**: カテゴリ名変更、表示順変更時
- **目的**: カテゴリ情報の更新をWebシステムに同期

**パスパラメータ**

| パラメータ | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| id | integer | ✓ | 更新対象のカテゴリID |

**リクエスト**
```json
{
  "name": "スイーツ",
  "sort_order": 4,
  "is_active": true,
  "translations": {
    "en": "Sweets",
    "zh-tw": "甜品"
  }
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| name | string | - | カテゴリ名 |
| sort_order | integer | - | 表示順序 |
| is_active | boolean | - | 有効フラグ |
| translations | object | - | 多言語翻訳データ |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "name": "スイーツ",
    "sort_order": 4,
    "is_active": true,
    "updated_at": "2024-01-01T16:10:00+09:00"
  },
  "message": "カテゴリを更新しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 更新されたカテゴリ情報 |
| data.id | integer | ✓ | カテゴリID |
| data.name | string | ✓ | 更新後のカテゴリ名 |
| data.sort_order | integer | ✓ | 更新後の表示順序 |
| data.is_active | boolean | ✓ | 更新後の有効フラグ |
| data.updated_at | datetime | ✓ | 更新日時 |
| message | string | ✓ | 処理結果メッセージ |

---

## 8. 多言語翻訳API

### 8.1 多言語翻訳同期（Dify連携）

```http
POST /api/v1/pos/translations/sync
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末（翻訳サービス連携機能）
- **いつ**: 新商品登録後、既存商品名変更後
- **目的**: Dify経由で多言語翻訳を取得し商品マスターを更新

**リクエスト**
```json
{
  "entity_type": "products",
  "entity_ids": [1, 2, 3],
  "source_language": "ja",
  "target_languages": ["en", "zh-tw", "zh-cn", "ko"]
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| entity_type | string | ✓ | 翻訳対象エンティティ |
| entity_ids | array | ✓ | 翻訳対象のID配列（最大10件） |
| source_language | string | ✓ | 翻訳元言語（"ja"固定） |
| target_languages | array | ✓ | 翻訳先言語の配列 |

**entity_type値**
- `products`: 商品
- `categories`: カテゴリ
- `options`: オプション

**target_languages値**
- `en`: 英語
- `zh-tw`: 中国語繁体字
- `zh-cn`: 中国語簡体字
- `ko`: 韓国語

**レスポンス**
```json
{
  "success": true,
  "data": {
    "total_entities": 3,
    "successful_translations": 2,
    "failed_translations": 1,
    "processing_time": 45.2,
    "results": [
      {
        "entity_id": 1,
        "entity_type": "products",
        "success": true,
        "translations": {
          "en": {
            "name": "Soy Sauce Ramen",
            "description": "Traditional soy sauce based ramen"
          },
          "zh-tw": {
            "name": "醬油拉麵",
            "description": "傳統醬油湯底拉麵"
          },
          "zh-cn": {
            "name": "酱油拉面",
            "description": "传统酱油汤底拉面"
          },
          "ko": {
            "name": "간장라멨",
            "description": "전통 간장 베이스 라멨"
          }
        }
      },
      {
        "entity_id": 2,
        "entity_type": "products",
        "success": true,
        "translations": {
          "en": {
            "name": "Miso Ramen",
            "description": "Rich miso based ramen"
          }
        }
      },
      {
        "entity_id": 3,
        "entity_type": "products",
        "success": false,
        "error_code": "TRANSLATION_TIMEOUT",
        "error_message": "Dify service timeout"
      }
    ]
  },
  "message": "翻訳同期が完了しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 翻訳処理結果 |
| data.total_entities | integer | ✓ | 処理対象エンティティ数 |
| data.successful_translations | integer | ✓ | 翻訳成功件数 |
| data.failed_translations | integer | ✓ | 翻訳失敗件数 |
| data.processing_time | float | ✓ | 処理時間（秒） |
| data.results | array | ✓ | 各エンティティの翻訳結果 |
| message | string | ✓ | 処理結果メッセージ |

**resultsオブジェクト仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| entity_id | integer | ✓ | エンティティID |
| entity_type | string | ✓ | エンティティタイプ |
| success | boolean | ✓ | 翻訳成功フラグ |
| translations | object | - | 翻訳結果（成功時のみ） |
| error_code | string | - | エラーコード（失敗時のみ） |
| error_message | string | - | エラーメッセージ（失敗時のみ） |

---

## 9. エラーハンドリング

### 共通エラーレスポンス
```json
{
  "success": false,
  "error": "error_code",
  "message": "エラーの詳細説明",
  "details": {
    "field": "具体的なエラー情報"
  },
  "timestamp": "2024-01-01T12:00:00+09:00"
}
```

### 主要エラーコード

| コード | HTTP Status | 説明 |
|--------|-------------|------|
| `invalid_token` | 401 | 認証トークンが無効 |
| `access_denied` | 403 | 認証権限によるアクセス拒否 |
| `resource_not_found` | 404 | 指定されたリソースが存在しない |
| `validation_failed` | 422 | バリデーションエラー |
| `session_expired` | 410 | セッションが有効期限切れ |
| `duplicate_session` | 409 | セッショントークンの重複 |
| `system_maintenance` | 503 | システムメンテナンス中 |

### リトライ処理推奨
- **5xx エラー**: 1分後に最大3回リトライ
- **429 Too Many Requests**: Rate Limitヘッダーに従ってリトライ
- **ネットワークエラー**: 30秒後に最大5回リトライ

---

## 10. 認証API

### 10.1 POSログイン認証

```http
POST /api/v1/pos/auth/login
```

**呼び出しタイミング**
- **誰が**: POS端末（システム起動時）
- **いつ**: POS端末起動時、トークン期限切れ時
- **目的**: 認証トークンの取得

**リクエスト**
```json
{
  "store_id": 1,
  "pos_terminal_id": "POS001",
  "credentials": {
    "username": "pos_system",
    "password": "secure_pos_password",
    "terminal_key": "TERMINAL_SECRET_KEY"
  }
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| store_id | integer | ✓ | 店舗ID |
| pos_terminal_id | string | ✓ | POS端末識別子 |
| credentials | object | ✓ | 認証情報 |
| credentials.username | string | ✓ | ユーザー名 |
| credentials.password | string | ✓ | パスワード |
| credentials.terminal_key | string | ✓ | 端末認証キー |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "access_token": "1|abc123def456...",
    "token_type": "Bearer",
    "expires_at": "2024-01-02T12:00:00+09:00",
    "expires_in": 86400,
    "store_id": 1,
    "pos_terminal_id": "POS001",
    "scopes": ["pos:read", "pos:write", "pos:sync"]
  },
  "message": "認証に成功しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 認証情報 |
| data.access_token | string | ✓ | アクセストークン |
| data.token_type | string | ✓ | トークンタイプ（Bearer） |
| data.expires_at | datetime | ✓ | 有効期限 |
| data.expires_in | integer | ✓ | 有効期限（秒） |
| data.store_id | integer | ✓ | 店舗ID |
| data.pos_terminal_id | string | ✓ | POS端末ID |
| data.scopes | array | ✓ | 権限スコープ |
| message | string | ✓ | 処理結果メッセージ |

### 10.2 POSトークン更新

```http
POST /api/v1/pos/auth/refresh
Authorization: Bearer pos_system_token
```

**呼び出しタイミング**
- **誰が**: POS端末
- **いつ**: トークン期限切れ前の自動更新時
- **目的**: 認証トークンの更新

**リクエスト**
```json
{
  "store_id": 1,
  "pos_terminal_id": "POS001"
}
```

**リクエストフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| store_id | integer | ✓ | 店舗ID |
| pos_terminal_id | string | ✓ | POS端末識別子 |

**レスポンス**
```json
{
  "success": true,
  "data": {
    "access_token": "2|def456ghi789...",
    "token_type": "Bearer",
    "expires_at": "2024-01-03T12:00:00+09:00",
    "expires_in": 86400,
    "refreshed_at": "2024-01-02T12:00:00+09:00"
  },
  "message": "トークンを更新しました"
}
```

**レスポンスフィールド仕様**

| フィールド | 型 | 必須 | 説明 |
|-----------|---|-----|------|
| success | boolean | ✓ | 処理成功フラグ |
| data | object | ✓ | 更新された認証情報 |
| data.access_token | string | ✓ | 新しいアクセストークン |
| data.token_type | string | ✓ | トークンタイプ（Bearer） |
| data.expires_at | datetime | ✓ | 新しい有効期限 |
| data.expires_in | integer | ✓ | 有効期限（秒） |
| data.refreshed_at | datetime | ✓ | 更新実行日時 |
| message | string | ✓ | 処理結果メッセージ |

---

このドキュメントは、POS端末側の開発者がWebシステムとの連携を実装する際の完全なリファレンスとして使用できます。各APIの詳細な仕様は、元のAPI設計書の該当セクションを参照してください。