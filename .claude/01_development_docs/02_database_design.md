# データベース設計書

## 📚 目次

- [1. 設計方針](#1-設計方針)
  - [1.1 基本方針](#11-基本方針)
  - [1.2 制約方針](#12-制約方針)
  - [1.3 監査方針と同期管理](#13-監査方針と同期管理)
- [2. テーブル一覧](#2-テーブル一覧)
  - [2.1 認証・ユーザー管理](#21-認証ユーザー管理)
  - [2.2 店舗・セッション管理](#22-店舗セッション管理)
  - [2.3 商品・メニュー管理](#23-商品メニュー管理)
  - [2.4 注文管理](#24-注文管理)
  - [2.5 カート管理](#25-カート管理)
  - [2.6 システム管理](#26-システム管理)
- [3. テーブル詳細設計](#3-テーブル詳細設計)
  - [3.1 users（ユーザー）](#31-usersユーザー)
  - [3.2 stores（店舗）](#32-stores店舗)
  - [3.3 sessions（セッション管理）](#33-sessionsセッション管理)
  - [3.4 products（商品マスター）](#34-products商品マスター)
  - [3.5 categories（商品カテゴリマスター）](#35-categories商品カテゴリマスター)
  - [3.6 category_product（商品カテゴリ紐付け）](#36-category_product商品カテゴリ紐付け)
  - [3.7 options（商品オプションマスター）](#37-options商品オプションマスター)
  - [3.8 product_to_options（商品とオプションの紐付け）](#38-product_to_options商品とオプションの紐付け)
  - [3.9 option_detail（商品オプション詳細）](#39-option_detail商品オプション詳細)
  - [3.10 images（商品画像マスター）](#310-images商品画像マスター)
  - [3.11 tax_rates（税率マスター）](#311-tax_rates税率マスター)
  - [3.12 orders（注文）](#312-orders注文)
  - [3.13 order_items（注文明細）](#313-order_items注文明細)
  - [3.14 order_item_options（注文商品オプション）](#314-order_item_options注文商品オプション)
  - [3.15 carts（カート状態管理）](#315-cartsカート状態管理)
  - [3.16 guest_sessions（ゲストセッション管理）](#316-guest_sessionsゲストセッション管理)
  - [3.17 cart_logs（カート操作ログ）](#317-cart_logsカート操作ログ)
  - [3.18 change_logs（変更履歴）](#318-change_logs変更履歴)
  - [3.19 system_settings（システム設定）](#319-system_settingsシステム設定)
  - [3.20 store_admin_urls（店舗管理画面URL履歴）](#320-store_admin_urls店舗管理画面url履歴)
  - [3.21 guest_identifiers（ゲスト識別情報）](#321-guest_identifiersゲスト識別情報)
- [4. インデックス戦略と障害復旧](#4-インデックス戦略と障害復旧)
  - [4.1 主要検索パターン（更新版）](#41-主要検索パターン更新版)
  - [4.2 複合インデックス（更新版）](#42-複合インデックス更新版)
- [5. パフォーマンス考慮事項](#5-パフォーマンス考慮事項)
  - [5.1 パーティショニング](#51-パーティショニング)
  - [5.2 アーカイブ戦略（日次締め運用前提）](#52-アーカイブ戦略日次締め運用前提)
- [6. データ整合性と障害復旧](#6-データ整合性と障害復旧)
  - [6.0 障害復旧のデータ管理](#60-障害復旧のデータ管理)
  - [6.1 外部キー制約](#61-外部キー制約)
  - [6.2 CHECK制約](#62-check制約)
- [7. 多言語対応](#7-多言語対応)
  - [7.1 翻訳データ構造](#71-翻訳データ構造)
  - [7.2 翻訳対象フィールド](#72-翻訳対象フィールド)
  - [7.3 翻訳JSON構造例](#73-翻訳json構造例)
  - [7.4 翻訳システム連携](#74-翻訳システム連携)
- [8. セキュリティ考慮事項](#8-セキュリティ考慮事項)
  - [8.1 個人情報保護](#81-個人情報保護)
  - [8.2 監査証跡](#82-監査証跡)
- [9. TTL管理とクリーンアップ戦略](#9-ttl管理とクリーンアップ戦略)
  - [9.1 期限切れデータの自動削除](#91-期限切れデータの自動削除)
  - [9.2 Laravel Scheduled Tasks](#92-laravel-scheduled-tasks)
  - [9.3 パフォーマンス最適化](#93-パフォーマンス最適化)

---

## 1. 設計方針

### 1.1 基本方針
- **正規化**: 第3正規形を基本とし、パフォーマンスを考慮して適度に非正規化
- **命名規則**: snake_case、テーブル名は複数形、カラム名は単数形
- **文字コード**: UTF8MB4（絵文字対応）
- **照合順序**: utf8mb4_unicode_ci
- **タイムゾーン**: Asia/Tokyo

### 1.2 制約方針
- **NOT NULL**: 必須項目は必ずNOT NULL制約
- **外部キー**: 参照整合性を保証（ON DELETE RESTRICT/CASCADE）
- **インデックス**: 検索・結合に使用するカラムにインデックス作成
- **デフォルト値**: 適切なデフォルト値を設定

### 1.3 監査方針と同期管理
- **タイムスタンプ**: 全テーブルに`created_at`, `updated_at`
- **ソフトデリート**: 履歴保持が必要なテーブルは`deleted_at`
- **変更ログ**: Webサーバー主導の変更を`change_logs`テーブルでPOS同期用に記録
- **障害復旧**: `cloud_synced`フラグでシンプル管理
- **セッション管理**: 全ての席セッションIDはPOS端末でのみ生成（SESSION_POS_xxx形式）
- **QRコード運用**: 固定モード（無期限）/都度発行モード（3時間TTL）のハイブリッド対応

## 2. テーブル一覧

### 2.1 認証・ユーザー管理
- `users` - ユーザー（管理者、スタッフ、POSシステム）
- `personal_access_tokens` - Sanctum APIトークン
- `password_reset_tokens` - パスワードリセットトークン

### 2.2 店舗・セッション管理
- `stores` - 店舗情報（QRコード運用モード設定含む）
- `sessions` - 席管理・セッション管理（全てPOS端末で生成、固定/都度発行モード対応）
- **ゲストセッション** - DBで管理（guest_sessionsテーブル、TTL自動削除）
- **障害復旧** - FireBird側の`cloud_synced`フラグで管理

### 2.3 商品・メニュー管理
- `products` - 商品マスター（メイン商品・オプション商品を統一管理）
- `categories` - 商品カテゴリマスター
- `category_product` - 商品カテゴリ紐付け（多対多）
- `options` - 商品オプションマスター（麺の量、トッピング等）
- `product_to_options` - 商品とオプションの紐付け（多対多）
- `option_detail` - 商品オプション詳細（選択肢として商品を割り当て）
- `images` - 商品画像マスター
- `tax_rates` - 税率マスター

### 2.4 注文管理
- `orders` - 注文ヘッダー
- `order_items` - 注文明細
- `order_item_options` - 注文商品のオプション選択

### 2.5 カート管理
- `carts` - カート状態管理（リアルタイムカート情報）
- `guest_sessions` - ゲストセッション管理（デバイス識別・同意状態）
- `cart_logs` - カート操作ログ（監査・分析用）

### 2.6 システム管理
- `change_logs` - Webサーバー主導の変更履歴（POS同期用）
- `system_settings` - システム設定
- `pos_health_checks` - POSヘルスチェック管理
- `failed_jobs` - 失敗したジョブ

## 3. テーブル詳細設計

### 3.1 users（ユーザー）
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'ユーザー名',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'メールアドレス',
    email_verified_at TIMESTAMP NULL COMMENT 'メール認証日時',
    password VARCHAR(255) NOT NULL COMMENT 'パスワード（ハッシュ化）',
    role ENUM('super_admin', 'admin', 'staff', 'pos_system') NOT NULL DEFAULT 'staff' COMMENT 'ユーザー役割',
    store_id BIGINT UNSIGNED NULL COMMENT '所属店舗ID',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    last_login_at TIMESTAMP NULL COMMENT '最終ログイン日時',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_store_id (store_id)
) ENGINE=InnoDB COMMENT='ユーザー';
```

### 3.2 stores（店舗）
```sql
CREATE TABLE stores (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT '店舗コード（URL識別用）',
    name VARCHAR(255) NOT NULL COMMENT '店舗名',
    description TEXT NULL COMMENT '店舗説明',
    phone VARCHAR(20) NULL COMMENT '電話番号',
    email VARCHAR(255) NULL COMMENT 'メールアドレス',
    address TEXT NULL COMMENT '住所',
    business_hours JSON NULL COMMENT '営業時間（JSON）',
    qr_mode ENUM('fixed', 'temporary') NOT NULL DEFAULT 'temporary' COMMENT 'QRコード運用モード',
    settings JSON NULL COMMENT '店舗設定（JSON）',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_stores_code (code),
    INDEX idx_stores_is_active (is_active)
) ENGINE=InnoDB COMMENT='店舗';
```

### 3.3 sessions（セッション管理）
```sql
CREATE TABLE sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'セッションID (SESSION_POS_xxx形式、POS端末のみ生成)',
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    table_number VARCHAR(50) NOT NULL COMMENT 'テーブル番号',
    customer_count INT UNSIGNED NULL COMMENT '利用人数（未設定時はNULL）',
    status ENUM('active', 'expired', 'completed') NOT NULL DEFAULT 'active' COMMENT 'ステータス',
    expires_at TIMESTAMP NULL COMMENT '有効期限（固定QRモード時はNULL）',
    started_at TIMESTAMP NULL COMMENT '開始日時',
    completed_at TIMESTAMP NULL COMMENT '完了日時',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sessions_session_id (session_id),
    INDEX idx_sessions_store_id (store_id),
    INDEX idx_sessions_table_number (table_number),
    INDEX idx_sessions_status (status),
    INDEX idx_sessions_expires_at (expires_at),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='セッション管理（全てPOS端末で生成）';
```

### 3.4 products（商品マスター）
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    code VARCHAR(45) NOT NULL COMMENT 'POS商品ID',
    name VARCHAR(255) NOT NULL COMMENT '商品名',
    description TEXT NULL COMMENT '商品説明',
    price INT NOT NULL COMMENT '価格（税抜、円）',
    tax_in_price INT NOT NULL COMMENT '税込価格（円）',
    cost INT NULL COMMENT '原価（円）',
    tax_type ENUM('standard', 'reduced', 'exempt', 'non_taxable') NOT NULL COMMENT '税区分',
    availability_status ENUM('available', 'sold_out', 'not_arrived', 'preparing') NOT NULL DEFAULT 'available' COMMENT '提供状態',
    availability_message VARCHAR(255) NULL COMMENT '提供状態メッセージ',
    expected_available_time TIME NULL COMMENT '提供可能予定時刻',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    image_url VARCHAR(500) NULL COMMENT 'メイン画像URL',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'ソート順',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_products_store_code (store_id, code),
    INDEX idx_products_store_id (store_id),
    INDEX idx_products_availability_status (availability_status),
    INDEX idx_products_is_active (is_active),
    INDEX idx_products_sort_order (sort_order),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='商品マスター';
```

### 3.5 categories（商品カテゴリマスター）
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    name VARCHAR(255) NOT NULL COMMENT 'カテゴリ名',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'ソート順',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_categories_store_id (store_id),
    INDEX idx_categories_sort_order (sort_order),
    INDEX idx_categories_is_active (is_active),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='商品カテゴリマスター';
```

### 3.6 category_product（商品カテゴリ紐付け）
```sql
CREATE TABLE category_product (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    category_id BIGINT UNSIGNED NOT NULL COMMENT 'カテゴリID',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'ソート順',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_category_product_product_id (product_id),
    INDEX idx_category_product_category_id (category_id),
    INDEX idx_category_product_sort_order (sort_order),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='商品カテゴリ紐付け';
```

### 3.7 options（商品オプションマスター）
```sql
CREATE TABLE options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    title VARCHAR(45) NOT NULL COMMENT 'オプションタイトル',
    required BOOLEAN NOT NULL DEFAULT FALSE COMMENT '必須フラグ',
    selection_type ENUM('single', 'multiple') NOT NULL DEFAULT 'single' COMMENT '選択タイプ',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_options_store_id (store_id),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='商品オプションマスター';
```

### 3.8 product_to_options（商品とオプションの紐付け）
```sql
CREATE TABLE product_to_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    option_id BIGINT UNSIGNED NOT NULL COMMENT 'オプションID',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'ソート順',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_product_to_options_product_id (product_id),
    INDEX idx_product_to_options_option_id (option_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='商品とオプションの紐付け';
```

### 3.9 option_detail（商品オプション詳細）
```sql
CREATE TABLE option_detail (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    option_id BIGINT UNSIGNED NOT NULL COMMENT 'オプションID',
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    default_selected BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'デフォルトフラグ（画面表示時に選択される）',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'ソート順',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_option_detail_option_id (option_id),
    INDEX idx_option_detail_product_id (product_id),
    FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='商品オプション詳細';
```

### 3.10 images（商品画像マスター）
```sql
CREATE TABLE images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    filename VARCHAR(45) NOT NULL COMMENT '画像ファイル名',
    sort_order INT NOT NULL DEFAULT 0 COMMENT 'ソート順',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_images_product_id (product_id),
    INDEX idx_images_sort_order (sort_order),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='商品画像マスター';
```

### 3.11 tax_rates（税率マスター）
```sql
CREATE TABLE tax_rates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tax_type ENUM('standard', 'reduced', 'exempt', 'non_taxable') NOT NULL COMMENT '税区分',
    rate DECIMAL(5,2) NOT NULL COMMENT '税率（%）',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_tax_rates_tax_type (tax_type)
) ENGINE=InnoDB COMMENT='税率マスター';
```

### 3.12 orders（注文）

#### 2層認証による注文管理
本システムでは注文管理において2層の認証・識別を行います：
1. **席セッション（session_id）**: 同席者間での注文履歴共有
2. **ゲストセッション（guest_token + device_fingerprint）**: 個人識別・不正アクセス防止

#### ハンディ端末の擬似トークン対応
ハンディ端末からの注文では、個人識別は不要ですがDB整合性のため擬似トークンを使用：
- **擬似ゲストトークン**: `handy_proxy_table{N}_{increment}` 形式
- **擬似フィンガープリント**: `handy_device_fingerprint` 固定値
- **用途**: DB NOT NULL制約対応のみ（認証機能なし）
- **API認証**: POS認証トークンを使用（擬似トークンは無関係）

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'セッションID（席での注文履歴共有用）',
    guest_token VARCHAR(255) NOT NULL COMMENT 'ゲストトークン（個人識別・不正防止用）',
    device_fingerprint VARCHAR(255) NOT NULL COMMENT 'デバイス識別（不正アクセス排除用）',
    order_number VARCHAR(50) NOT NULL UNIQUE COMMENT '注文番号',
    status ENUM('pending', 'preparing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '注文ステータス',
    total_amount INT NOT NULL COMMENT '合計金額（円）',
    memo TEXT NULL COMMENT '備考メモ',
    ordered_at TIMESTAMP NOT NULL COMMENT '注文日時',
    confirmed_at TIMESTAMP NULL COMMENT '確認日時',
    completed_at TIMESTAMP NULL COMMENT '完了日時',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_orders_order_number (order_number),
    INDEX idx_orders_store_id (store_id),
    INDEX idx_orders_session_id (session_id),
    INDEX idx_orders_guest_token (guest_token),
    INDEX idx_orders_status (status),
    INDEX idx_orders_ordered_at (ordered_at),
    INDEX idx_orders_device_fingerprint (device_fingerprint),
    INDEX idx_orders_session_guest (session_id, guest_token),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='注文（2層認証による管理）';
```

### 3.13 order_items（注文明細）
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL COMMENT '注文ID',
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    quantity INT UNSIGNED NOT NULL COMMENT '数量',
    unit_price INT NOT NULL COMMENT '単価（円）',
    total_price INT NOT NULL COMMENT '小計（円）',
    memo TEXT NULL COMMENT '備考メモ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='注文明細';
```

### 3.14 order_item_options（注文商品オプション）

#### スナップショットの活用目的
1. **価格変更耐性**: 注文後にマスター価格が変更されても注文時の価格を保持
2. **名称変更対応**: 商品名やオプション名が変更されても履歴の正確性を維持
3. **削除商品の記録**: マスターから削除された商品でも注文履歴に完全な情報を保持
4. **請求書再発行**: 数年後でも当時の正確な内容で請求書を再発行可能

```sql
CREATE TABLE order_item_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_item_id BIGINT UNSIGNED NOT NULL COMMENT '注文明細ID',
    option_id BIGINT UNSIGNED NOT NULL COMMENT 'オプションID',
    product_id BIGINT UNSIGNED NOT NULL COMMENT 'オプション商品ID',
    quantity INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    option_name VARCHAR(255) NOT NULL COMMENT 'オプション名（スナップショット）',
    product_name VARCHAR(255) NOT NULL COMMENT '選択肢名（スナップショット）',
    unit_price INT NOT NULL COMMENT '単価（スナップショット、円）',
    total_price INT NOT NULL COMMENT '小計（数量×単価、円）',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_order_item_options_order_item_id (order_item_id),
    INDEX idx_order_item_options_option_id (option_id),
    INDEX idx_order_item_options_product_id (product_id),
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='注文商品オプション';
```

### 3.15 carts（カート状態管理）
```sql
CREATE TABLE carts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_token VARCHAR(255) NOT NULL COMMENT 'ゲストトークン',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'セッションID', 
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    quantity INT UNSIGNED NOT NULL COMMENT '数量',
    unit_price INT NOT NULL COMMENT '単価（円）',
    options JSON NULL COMMENT '選択オプション',
    options_hash VARCHAR(32) GENERATED ALWAYS AS (MD5(IFNULL(options, ''))) STORED COMMENT 'オプション識別用ハッシュ',
    expires_at TIMESTAMP NOT NULL COMMENT '有効期限（TTL管理）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_carts_item (guest_token, product_id, options_hash),
    INDEX idx_carts_token (guest_token),
    INDEX idx_carts_expires (expires_at),
    INDEX idx_carts_session (session_id),
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='カート状態管理（Redis代替）';
```

### 3.16 guest_sessions（ゲストセッション管理）
```sql
CREATE TABLE guest_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    token VARCHAR(255) UNIQUE NOT NULL COMMENT 'ゲストトークン',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'DB sessionsテーブルID',
    device_fingerprint VARCHAR(255) NOT NULL COMMENT 'デバイス識別',
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    language CHAR(2) DEFAULT 'ja' COMMENT '言語設定',
    agreed_policy BOOLEAN DEFAULT FALSE COMMENT 'ポリシー同意状態',
    expires_at TIMESTAMP NOT NULL COMMENT '有効期限（30分TTL）',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '最終アクセス時刻',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY uk_guest_sessions_token (token),
    INDEX idx_guest_sessions_expires (expires_at),
    INDEX idx_guest_sessions_device (device_fingerprint),
    INDEX idx_guest_sessions_session (session_id),
    
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='ゲストセッション管理（Redis代替）';
```

### 3.17 cart_logs（カート操作ログ）
```sql
CREATE TABLE cart_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_token VARCHAR(255) NOT NULL COMMENT 'ゲストトークン',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'セッションID',
    action ENUM('add', 'remove', 'update', 'clear') NOT NULL COMMENT 'カート操作',
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    quantity INT UNSIGNED NOT NULL COMMENT '数量（削除時は削除した数を記録）',
    unit_price INT NOT NULL COMMENT '操作時の単価（円）',
    options JSON NULL COMMENT '選択オプション（シンプルな配列）',
    cart_total INT NOT NULL COMMENT '操作後のカート合計金額（円）',
    is_success BOOLEAN NOT NULL DEFAULT TRUE COMMENT '操作成功フラグ',
    error_code VARCHAR(50) NULL COMMENT 'エラーコード（失敗時のみ）',
    error_message VARCHAR(255) NULL COMMENT 'エラーメッセージ（失敗時のみ）',
    device_fingerprint VARCHAR(255) NOT NULL COMMENT 'デバイスフィンガープリント',
    ip_address VARCHAR(45) NULL COMMENT 'IPアドレス',
    user_agent TEXT NULL COMMENT 'ユーザーエージェント',
    created_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_cart_logs_guest_token (guest_token),
    INDEX idx_cart_logs_session_id (session_id),
    INDEX idx_cart_logs_product_id (product_id),
    INDEX idx_cart_logs_is_success (is_success),
    INDEX idx_cart_logs_created_at (created_at),
    INDEX idx_cart_logs_action (action),
    INDEX idx_cart_logs_device_fingerprint (device_fingerprint),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='カート操作ログ（監査・調査用）';
```

### 3.18 change_logs（変更履歴）
```sql
CREATE TABLE change_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entity_type VARCHAR(100) NOT NULL COMMENT 'エンティティタイプ',
    entity_id BIGINT UNSIGNED NOT NULL COMMENT 'エンティティID',
    action ENUM('created', 'updated', 'deleted') NOT NULL COMMENT 'アクション',
    changes JSON NULL COMMENT '変更内容（JSON）',
    user_id BIGINT UNSIGNED NULL COMMENT '変更ユーザーID',
    user_type VARCHAR(50) NULL COMMENT 'ユーザータイプ',
    ip_address VARCHAR(45) NULL COMMENT 'IPアドレス',
    user_agent TEXT NULL COMMENT 'ユーザーエージェント',
    is_synced BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'POS同期済みフラグ',
    synced_at TIMESTAMP NULL COMMENT 'POS同期日時',
    synced_by BIGINT UNSIGNED NULL COMMENT 'POS同期ユーザーID',
    created_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_change_logs_entity (entity_type, entity_id),
    INDEX idx_change_logs_action (action),
    INDEX idx_change_logs_is_synced (is_synced),
    INDEX idx_change_logs_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (synced_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Webサーバー主導の変更履歴（POS同期用）';
```

### 3.19 system_settings（システム設定）
```sql
CREATE TABLE system_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NULL COMMENT '店舗ID（NULL=全体設定）',
    key VARCHAR(100) NOT NULL COMMENT '設定キー',
    value JSON NULL COMMENT '設定値（JSON）',
    description TEXT NULL COMMENT '設定説明',
    is_public BOOLEAN NOT NULL DEFAULT FALSE COMMENT '公開設定フラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_system_settings_store_key (store_id, key),
    INDEX idx_system_settings_key (key),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='システム設定';
```

### 3.20 pos_health_checks（POSヘルスチェック）
```sql
CREATE TABLE pos_health_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    last_check_at TIMESTAMP NOT NULL COMMENT '最終チェック日時',
    status ENUM('online', 'warning', 'error', 'syncing') NOT NULL DEFAULT 'online' COMMENT 'POSステータス',
    consecutive_success INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '連続成功回数',
    timeout_threshold_seconds INT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'タイムアウト闾値（秒）',
    error_threshold_seconds INT UNSIGNED NOT NULL DEFAULT 90 COMMENT 'エラー闾値（秒）',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_pos_health_checks_store (store_id),
    INDEX idx_pos_health_checks_status (status),
    INDEX idx_pos_health_checks_last_check (last_check_at),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='POSヘルスチェック管理';
```

### 3.20 store_admin_urls（店舗管理画面URL履歴）
```sql
CREATE TABLE store_admin_urls (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    url_prefix VARCHAR(100) NOT NULL COMMENT '管理画面URLプレフィックス',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    activated_at TIMESTAMP NOT NULL COMMENT '有効化日時',
    deactivated_at TIMESTAMP NULL COMMENT '無効化日時',
    created_by BIGINT UNSIGNED NULL COMMENT '作成者ID',
    change_reason VARCHAR(255) NULL COMMENT '変更理由',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_store_admin_urls_prefix (url_prefix),
    INDEX idx_store_admin_urls_store_active (store_id, is_active),
    INDEX idx_store_admin_urls_activated (activated_at),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='店舗管理画面URL履歴（パスベース方式）';
```

### 3.21 guest_identifiers（ゲスト識別情報）
```sql
CREATE TABLE guest_identifiers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_token VARCHAR(255) NOT NULL UNIQUE COMMENT 'ゲストトークン',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'セッションID',
    identifier_icon VARCHAR(10) NOT NULL COMMENT '識別アイコン（絵文字）',
    identifier_color VARCHAR(7) NOT NULL COMMENT '識別カラー（HEXコード）',
    assigned_at TIMESTAMP NOT NULL COMMENT 'アサイン日時',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_guest_identifiers_token (guest_token),
    INDEX idx_guest_identifiers_session (session_id),
    INDEX idx_guest_identifiers_assigned (assigned_at),
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='ゲスト識別情報（動物アイコン・カラー管理）';
```

## 4. インデックス戦略と障害復旧

### 4.1 主要検索パターン（更新版）
- **ユーザー検索**: email, role, store_id
- **商品検索**: store_id, availability_status, is_active
- **カテゴリ検索**: store_id, is_active
- **注文検索**: store_id, session_id, status, ordered_at
- **セッション検索**: session_id, store_id, table_number, status
- **障害復旧検索**: cloud_syncedフラグ（FireBird側）
- **カートログ検索**: guest_token, product_id, action, created_at

### 4.2 複合インデックス（更新版）
```sql
-- 注文検索用
CREATE INDEX idx_orders_store_status_date ON orders(store_id, status, ordered_at);

-- 商品検索用
CREATE INDEX idx_products_store_status_active ON products(store_id, availability_status, is_active);

-- セッション検索用
CREATE INDEX idx_sessions_session_id_status ON sessions(session_id, status);
CREATE INDEX idx_sessions_table_store ON sessions(table_number, store_id);

-- 変更ログ同期用
CREATE INDEX idx_change_logs_sync ON change_logs(is_synced, created_at);

-- カート状態管理用
CREATE INDEX idx_carts_token_expires ON carts(guest_token, expires_at);
CREATE INDEX idx_guest_sessions_token_expires ON guest_sessions(token, expires_at);

-- カートログ分析用
CREATE INDEX idx_cart_logs_token_date ON cart_logs(guest_token, created_at);

-- 障害復旧用（FireBird側）
-- CREATE INDEX idx_order_management_cloud_synced ON order_management(cloud_synced);
```

## 5. パフォーマンス考慮事項

### 5.1 パーティショニング
```sql
-- 変更ログテーブルの月次パーティション（大量データ対応）
ALTER TABLE change_logs PARTITION BY RANGE (YEAR(created_at)*100 + MONTH(created_at))
(
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    -- ...継続的に追加
);
```

### 5.2 アーカイブ戦略（日次締め運用前提）
- **change_logs**: 1ヶ月経過後にアーカイブテーブルに移動（POSに完全データあり）
- **carts**: 期限切れ後に自動削除（リアルタイム管理）
- **guest_sessions**: 期限切れ後に自動削除（30分TTL）
- **cart_logs**: 1ヶ月経過後にアーカイブテーブルに移動（分析完了後）
- **images**: 商品削除時に連動して整理
- **orders**: 1年経過後にアーカイブテーブルに移動（法的保管期間）
- **sessions**: 期限切れ後1日でクリーンアップ（軽量化優先）

※重要: POSシステムが全マスターデータを保持する前提

## 6. データ整合性と障害復旧

### 6.0 障害復旧のデータ管理
```sql
-- FireBird側（POS端末）の同期管理
-- order_managementテーブルに追加
cloud_synced BOOLEAN DEFAULT TRUE -- TRUE:同期済み FALSE:未同期

-- 障害時の動作:
-- 1. スマホ注文: 完全不可
-- 2. ハンディ注文: cloud_synced=FALSEで記録
-- 3. 復旧時: cloud_synced=FALSEのデータを一括同期
```

## 6. データ整合性

### 6.1 外部キー制約
- **CASCADE**: 親データ削除時に子データも削除（order_items等）
- **RESTRICT**: 子データが存在する場合は親データ削除不可（stores等）
- **SET NULL**: 親データ削除時にNULLに設定（nullable項目のみ）

### 6.2 CHECK制約
```sql
-- 価格の妥当性チェック（マイナス値許可、極端な値のみ制限）
ALTER TABLE products ADD CONSTRAINT chk_products_price CHECK (price >= -999999.99 AND price <= 999999.99);
ALTER TABLE products ADD CONSTRAINT chk_products_tax_in_price CHECK (tax_in_price >= -999999.99 AND tax_in_price <= 999999.99);

-- 数量は1以上
ALTER TABLE order_items ADD CONSTRAINT chk_order_items_quantity CHECK (quantity >= 1);

-- セッション有効期限は未来日時（固定QRモード時はNULL許可）
ALTER TABLE sessions ADD CONSTRAINT chk_sessions_expires_at CHECK (expires_at IS NULL OR expires_at > created_at);

-- 提供状態の妥当性
ALTER TABLE products ADD CONSTRAINT chk_products_availability 
CHECK (availability_status IN ('available', 'sold_out', 'not_arrived', 'preparing'));
```

## 7. 多言語対応

### 7.1 翻訳データ構造
```json
{
  "ja": "ハンバーガー",
  "en": "Hamburger", 
  "zh-TW": "漢堡",
  "zh-CN": "汉堡",
  "ko": "햄버거"
}
```

### 7.2 翻訳対象フィールド
- `categories.translations`: カテゴリ名（name）
- `products.translations`: 商品名（name）、商品説明（description）
- `options.translations`: オプションタイトル（title）

### 7.3 翻訳JSON構造例
```json
{
  "en": {
    "name": "Ramen",
    "description": "Delicious noodle soup with rich broth"
  },
  "zh-TW": {
    "name": "拉麵",
    "description": "美味的湯麵配濃郁湯頭"
  },
  "zh-CN": {
    "name": "拉面",
    "description": "美味的汤面配浓郁汤头"
  },
  "ko": {
    "name": "라멘",
    "description": "진한 국물의 맛있는 국수"
  }
}
```

### 7.4 翻訳システム連携
- **外部サービス**: Dify経由で多言語翻訳
- **更新方式**: POS翻訳API経由でリアルタイム同期
- **処理方式**: 全成功 or 全失敗（フォールバック付き）
- **対応言語**: 英語、中国語繁体字、中国語簡体字、韓国語

## 8. セキュリティ考慮事項

### 8.1 個人情報保護
- **暗号化**: クレジットカード情報等の機密データ
- **マスキング**: ログ出力時の個人情報マスキング
- **保持期間**: 不要になった個人情報の自動削除

### 8.2 監査証跡
- **change_logs**: Webサーバー主導のデータ変更を記録（POS同期用）
- **IPアドレス**: アクセス元の記録
- **ユーザーエージェント**: アクセス元デバイス情報

## 9. TTL管理とクリーンアップ戦略

### 9.1 期限切れデータの自動削除

#### ゲストセッション管理
- **TTL管理**: `guest_sessions.expires_at`カラムで管理
- **自動延長**: APIアクセス時に`last_activity`更新、`expires_at`っ30分延長
- **期限切れ削除**: Laravel Scheduled Taskで毎分実行

#### カート状態管理
- **TTL管理**: `carts.expires_at`カラムで管理
- **自動延長**: カート操作時に30分延長
- **期限切れ削除**: ゲストセッションと連動して自動削除

### 9.2 Laravel Scheduled Tasks

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // 期限切れゲストセッション削除（毎分）
    $schedule->command('guest:cleanup-expired')->everyMinute();
    
    // 期限切れカート削除（5分毎）
    $schedule->command('cart:cleanup-expired')->everyFiveMinutes();
    
    // 長期間未使用データのアーカイブ（日次）
    $schedule->command('archive:old-data')->daily();
}
```

```php
// app/Console/Commands/GuestCleanupExpired.php
class GuestCleanupExpired extends Command
{
    public function handle()
    {
        // 期限切れゲストセッション削除
        $deletedSessions = GuestSession::where('expires_at', '<', now())->delete();
        
        // 連動してカートも削除
        $deletedCarts = Cart::where('expires_at', '<', now())->delete();
        
        $this->info("Deleted {$deletedSessions} expired guest sessions");
        $this->info("Deleted {$deletedCarts} expired carts");
    }
}
```

### 9.3 パフォーマンス最適化

#### DBインデックス最適化
```sql
-- TTL管理用インデックス
CREATE INDEX idx_guest_sessions_expires_cleanup ON guest_sessions(expires_at) WHERE expires_at < NOW();
CREATE INDEX idx_carts_expires_cleanup ON carts(expires_at) WHERE expires_at < NOW();

-- 高速検索用インデックス
CREATE INDEX idx_guest_sessions_token_active ON guest_sessions(token) WHERE expires_at > NOW();
CREATE INDEX idx_carts_token_active ON carts(guest_token) WHERE expires_at > NOW();
```

#### カート操作最適化
- **Upsert操作**: `ON DUPLICATE KEY UPDATE`で高速更新
- **バッチ処理**: 複数アイテムの一括更新
- **結果キャッシュ**: Laravel ModelのEager Loading活用

---

**注意**: このデータベース設計はDB中心のシンプルな構成で、Redis障害リスクを完全に排除しています。POSシステムが全マスターデータを保持する前提で、ゲストセッションとカート管理もDBで統一しています。
