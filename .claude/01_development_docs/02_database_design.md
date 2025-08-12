# データベース設計書

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
- **変更ログ**: 重要なデータ変更は`change_logs`テーブルで追跡
- **障害復旧**: `cloud_synced`フラグでシンプル管理
- **POS中心**: 全ての席セッションIDはPOS生成

## 2. テーブル一覧

### 2.1 認証・ユーザー管理
- `users` - ユーザー（管理者、スタッフ、POSシステム）
- `personal_access_tokens` - Sanctum APIトークン
- `password_reset_tokens` - パスワードリセットトークン

### 2.2 店舗・セッション管理
- `stores` - 店舗情報
- `sessions` - 席管理・POS生成セッションID管理
- **ゲストセッション** - Redisで管理（guest_session:{token}形式）
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
- `cart_logs` - カート操作ログ（監査・分析用）

### 2.6 システム管理
- `change_logs` - データ変更履歴（POS連携用）
- `system_settings` - システム設定
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
    name VARCHAR(255) NOT NULL COMMENT '店舗名',
    description TEXT NULL COMMENT '店舗説明',
    phone VARCHAR(20) NULL COMMENT '電話番号',
    email VARCHAR(255) NULL COMMENT 'メールアドレス',
    address TEXT NULL COMMENT '住所',
    business_hours JSON NULL COMMENT '営業時間（JSON）',
    settings JSON NULL COMMENT '店舗設定（JSON）',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_stores_is_active (is_active)
) ENGINE=InnoDB COMMENT='店舗';
```

### 3.3 sessions（POS生成セッション管理）
```sql
CREATE TABLE sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'POS生成セッションID (SESSION_POS_xxx)',
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    table_number VARCHAR(50) NOT NULL COMMENT 'テーブル番号',
    customer_count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '利用人数',
    status ENUM('active', 'expired', 'completed') NOT NULL DEFAULT 'active' COMMENT 'ステータス',
    expires_at TIMESTAMP NOT NULL COMMENT '有効期限',
    started_at TIMESTAMP NULL COMMENT '開始日時',
    completed_at TIMESTAMP NULL COMMENT '完了日時',
    pos_generated BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'POS生成フラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sessions_session_id (session_id),
    INDEX idx_sessions_store_id (store_id),
    INDEX idx_sessions_table_number (table_number),
    INDEX idx_sessions_status (status),
    INDEX idx_sessions_expires_at (expires_at),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='POS生成セッション管理';
```

### 3.4 products（商品マスター）
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    code VARCHAR(45) NOT NULL COMMENT 'POS商品ID',
    name VARCHAR(255) NOT NULL COMMENT '商品名',
    description TEXT NOT NULL COMMENT '商品説明',
    price DECIMAL(10,2) NOT NULL COMMENT '価格（税抜）',
    tax_in_price DECIMAL(10,2) NOT NULL COMMENT '税込価格',
    cost DECIMAL(10,2) NULL COMMENT '原価',
    tax_type ENUM('standard', 'reduced', 'exempt', 'non_taxable') NOT NULL COMMENT '税区分',
    availability_status ENUM('available', 'sold_out', 'not_arrived', 'preparing') NOT NULL DEFAULT 'available' COMMENT '提供状態',
    availability_message VARCHAR(255) NULL COMMENT '提供状態メッセージ',
    expected_available_time TIME NULL COMMENT '提供可能予定時刻',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    image_url VARCHAR(500) NULL COMMENT 'メイン画像URL',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_products_store_code (store_id, code),
    INDEX idx_products_store_id (store_id),
    INDEX idx_products_availability_status (availability_status),
    INDEX idx_products_is_active (is_active),
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
    sort_order INT NOT NULL COMMENT 'ソート順',
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
    sort_order INT NOT NULL COMMENT 'ソート順',
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
    sort_order INT NOT NULL COMMENT 'ソート順',
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
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '注文ステータス',
    total_amount DECIMAL(10,2) NOT NULL COMMENT '合計金額',
    notes TEXT NULL COMMENT '備考',
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
    unit_price DECIMAL(10,2) NOT NULL COMMENT '単価',
    total_price DECIMAL(10,2) NOT NULL COMMENT '小計',
    notes TEXT NULL COMMENT '備考',
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
```sql
CREATE TABLE order_item_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_item_id BIGINT UNSIGNED NOT NULL COMMENT '注文明細ID',
    option_id BIGINT UNSIGNED NOT NULL COMMENT 'オプションID',
    product_id BIGINT UNSIGNED NOT NULL COMMENT 'オプション商品ID',
    quantity INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
    option_name VARCHAR(255) NOT NULL COMMENT 'オプション名（スナップショット）',
    product_name VARCHAR(255) NOT NULL COMMENT '選択肢名（スナップショット）',
    unit_price DECIMAL(10,2) NOT NULL COMMENT '単価（スナップショット）',
    total_price DECIMAL(10,2) NOT NULL COMMENT '小計（数量×単価）',
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

### 3.15 cart_logs（カート操作ログ）
```sql
CREATE TABLE cart_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_token VARCHAR(255) NOT NULL COMMENT 'ゲストトークン',
    session_id BIGINT UNSIGNED NULL COMMENT 'セッションID（あれば）',
    action ENUM('add', 'remove', 'update', 'clear') NOT NULL COMMENT 'カート操作',
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    quantity INT UNSIGNED NULL COMMENT '数量（削除時はNULL）',
    unit_price DECIMAL(10,2) NULL COMMENT '操作時の単価',
    options JSON NULL COMMENT '選択オプション（シンプルな配列）',
    cart_total DECIMAL(10,2) NULL COMMENT '操作後のカート合計金額',
    is_success BOOLEAN NOT NULL DEFAULT TRUE COMMENT '操作成功フラグ',
    error_code VARCHAR(50) NULL COMMENT 'エラーコード（失敗時のみ）',
    error_message VARCHAR(255) NULL COMMENT 'エラーメッセージ（失敗時のみ）',
    device_fingerprint VARCHAR(255) NULL COMMENT 'デバイスフィンガープリント',
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
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='カート操作ログ（監査・調査用）';
```

### 3.16 change_logs（変更履歴）
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
) ENGINE=InnoDB COMMENT='変更履歴（POS連携用）';
```

### 3.17 system_settings（システム設定）
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

-- POS生成セッション検索用
CREATE INDEX idx_sessions_session_id_status ON sessions(session_id, status);
CREATE INDEX idx_sessions_table_store ON sessions(table_number, store_id);

-- 変更ログ同期用
CREATE INDEX idx_change_logs_sync ON change_logs(is_synced, created_at);

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

### 5.2 アーカイブ戦略
- **change_logs**: 6ヶ月経過後にアーカイブテーブルに移動
- **cart_logs**: 3ヶ月経過後にアーカイブテーブルに移動（分析データとして保持）
- **images**: 商品削除時に連動して整理
- **orders**: 1年経過後にアーカイブテーブルに移動
- **sessions**: 期限切れ後1週間でクリーンアップ

## 6. データ整合性と障害復旧

### 6.0 障害復旧のデータ管理
```sql
-- FireBird側（POS端末）の同期管理
-- order_managementテーブルに追加
cloud_synced CHAR(1) DEFAULT 'Y' -- 'Y':同期済み 'N':未同期

-- 障害時の動作:
-- 1. スマホ注文: 完全不可
-- 2. ハンディ注文: cloud_synced='N'で記録
-- 3. 復旧時: cloud_synced='N'のデータを一括同期
```

## 6. データ整合性

### 6.1 外部キー制約
- **CASCADE**: 親データ削除時に子データも削除（order_items等）
- **RESTRICT**: 子データが存在する場合は親データ削除不可（stores等）
- **SET NULL**: 親データ削除時にNULLに設定（guest_token等）

### 6.2 CHECK制約
```sql
-- 価格の妥当性チェック（マイナス値許可、極端な値のみ制限）
ALTER TABLE products ADD CONSTRAINT chk_products_price CHECK (price >= -999999.99 AND price <= 999999.99);
ALTER TABLE products ADD CONSTRAINT chk_products_tax_in_price CHECK (tax_in_price >= -999999.99 AND tax_in_price <= 999999.99);

-- 数量は1以上
ALTER TABLE order_items ADD CONSTRAINT chk_order_items_quantity CHECK (quantity >= 1);

-- セッション有効期限は未来日時
ALTER TABLE sessions ADD CONSTRAINT chk_sessions_expires_at CHECK (expires_at > created_at);

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
- **change_logs**: 全てのデータ変更を記録
- **IPアドレス**: アクセス元の記録
- **ユーザーエージェント**: アクセス元デバイス情報

## 9. Redisスキーマ設計（ゲストセッション）

### 9.1 データ構造

#### ゲストセッション
```redis
# キー形式
guest_session:{token}

# データ構造（Hash）
{
    "token": "guest_abc123def456",
    "device_fingerprint": "browser_chrome_win10_hash123",
    "store_id": "1",
    "session_id": "123",  # DBのsessionsテーブルID（あれば）
    "created_at": "2024-01-01T12:00:00+09:00",
    "last_access": "2024-01-01T12:30:00+09:00",
    "language": "ja"
}

# TTL: 30分（1800秒）、アクティビティごとに自動延長
```

#### カートデータ（シンプル化）
```redis
# キー形式
guest_cart:{token}

# データ構造（Hash）
{
    "items": "[{\"product_id\":101,\"quantity\":2,\"options\":[{\"option_id\":10,\"product_id\":201}]}]",
    "updated_at": "2024-01-01T12:30:00+09:00"
}

# TTL: 30分（1800秒）、アクティビティごとに自動延長
```

#### デバイス識別情報
```redis
# キー形式
device:{device_fingerprint}

# データ構造（String）
"guest_abc123def456"

# TTL: 24時間（86400秒）
```

### 9.2 Redis操作例

```php
// ゲストセッション作成
Redis::hmset("guest_session:{$token}", [
    'token' => $token,
    'device_fingerprint' => $fingerprint,
    'store_id' => $storeId,
    'session_id' => $sessionId,  // あれば
    'created_at' => now()->toISOString(),
    'last_access' => now()->toISOString(),
    'language' => 'ja'
]);
Redis::expire("guest_session:{$token}", 1800); // 30分

// カートデータ保存（シンプル化）
Redis::hmset("guest_cart:{$token}", [
    'items' => json_encode($cartItems),
    'updated_at' => now()->toISOString()
]);
Redis::expire("guest_cart:{$token}", 1800); // 30分

// アクティビティ時にTTL延長
Redis::expire("guest_session:{$token}", 1800);
Redis::expire("guest_cart:{$token}", 1800);

// デバイス識別情報設定
Redis::setex("device:{$fingerprint}", 86400, $token); // 24時間
```

### 9.3 クリーンアップ戦略

#### 期限切れセッション自動削除
- **TTL利用**: Redisの自動期限切れ機能
- **バックグラウンドクリーンアップ**: Laravel Schedulerで定期実行

#### 長時間未使用セッション削除
```bash
# 1時間未アクセスのセッションを削除
php artisan session:cleanup --type=guest --inactive=3600
```

---

**注意**: このデータベース設計はテーブル構成の修正が予定されているため、実装時に最新の要件に合わせて調整してください。