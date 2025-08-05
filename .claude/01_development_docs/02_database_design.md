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

### 1.3 監査方針
- **タイムスタンプ**: 全テーブルに`created_at`, `updated_at`
- **ソフトデリート**: 履歴保持が必要なテーブルは`deleted_at`
- **変更ログ**: 重要なデータ変更は`change_logs`テーブルで追跡

## 2. テーブル一覧

### 2.1 認証・ユーザー管理
- `users` - ユーザー（管理者、スタッフ、お客様、POSシステム）
- `personal_access_tokens` - Sanctum APIトークン
- `password_reset_tokens` - パスワードリセットトークン

### 2.2 店舗・セッション管理
- `stores` - 店舗情報
- `sessions` - 席管理・QRコードセッション
- `session_customers` - セッション参加者

### 2.3 メニュー管理
- `menu_categories` - メニューカテゴリ
- `menu_items` - メニュー商品
- `menu_item_options` - 商品オプション（トッピング等）
- `menu_item_option_values` - オプション選択肢

### 2.4 注文管理
- `orders` - 注文ヘッダー
- `order_items` - 注文明細
- `order_item_options` - 注文商品のオプション選択

### 2.5 システム管理
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
    role ENUM('super_admin', 'admin', 'staff', 'customer', 'pos_system') NOT NULL DEFAULT 'customer' COMMENT 'ユーザー役割',
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
    slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL用スラッグ',
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
    UNIQUE KEY uk_stores_slug (slug),
    INDEX idx_stores_is_active (is_active)
) ENGINE=InnoDB COMMENT='店舗';
```

### 3.3 sessions（セッション・席管理）
```sql
CREATE TABLE sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    qr_code VARCHAR(100) NOT NULL UNIQUE COMMENT 'QRコード',
    table_number VARCHAR(50) NULL COMMENT 'テーブル番号',
    customer_count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '利用人数',
    status ENUM('active', 'expired', 'completed') NOT NULL DEFAULT 'active' COMMENT 'ステータス',
    expires_at TIMESTAMP NOT NULL COMMENT '有効期限',
    started_at TIMESTAMP NULL COMMENT '開始日時',
    completed_at TIMESTAMP NULL COMMENT '完了日時',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_sessions_qr_code (qr_code),
    INDEX idx_sessions_store_id (store_id),
    INDEX idx_sessions_status (status),
    INDEX idx_sessions_expires_at (expires_at),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='セッション・席管理';
```

### 3.4 menu_categories（メニューカテゴリ）
```sql
CREATE TABLE menu_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    name VARCHAR(255) NOT NULL COMMENT 'カテゴリ名',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    description TEXT NULL COMMENT '説明',
    image_url VARCHAR(500) NULL COMMENT 'カテゴリ画像URL',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_menu_categories_store_id (store_id),
    INDEX idx_menu_categories_sort_order (sort_order),
    INDEX idx_menu_categories_is_active (is_active),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='メニューカテゴリ';
```

### 3.5 menu_items（メニュー商品）
```sql
CREATE TABLE menu_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    category_id BIGINT UNSIGNED NOT NULL COMMENT 'カテゴリID',
    pos_id VARCHAR(100) NULL COMMENT 'POS商品ID',
    name VARCHAR(255) NOT NULL COMMENT '商品名',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    description TEXT NULL COMMENT '商品説明',
    price DECIMAL(10,2) NOT NULL COMMENT '価格',
    image_url VARCHAR(500) NULL COMMENT '商品画像URL',
    allergen_info JSON NULL COMMENT 'アレルギー情報（JSON）',
    nutritional_info JSON NULL COMMENT '栄養情報（JSON）',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
    is_available BOOLEAN NOT NULL DEFAULT TRUE COMMENT '提供可能フラグ',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_menu_items_store_id (store_id),
    INDEX idx_menu_items_category_id (category_id),
    INDEX idx_menu_items_pos_id (pos_id),
    INDEX idx_menu_items_is_available (is_available),
    INDEX idx_menu_items_is_active (is_active),
    INDEX idx_menu_items_sort_order (sort_order),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='メニュー商品';
```

### 3.6 menu_item_options（商品オプション）
```sql
CREATE TABLE menu_item_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    menu_item_id BIGINT UNSIGNED NOT NULL COMMENT 'メニュー商品ID',
    name VARCHAR(255) NOT NULL COMMENT 'オプション名',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    type ENUM('single', 'multiple') NOT NULL DEFAULT 'single' COMMENT '選択タイプ',
    is_required BOOLEAN NOT NULL DEFAULT FALSE COMMENT '必須フラグ',
    min_selections INT UNSIGNED NULL COMMENT '最小選択数',
    max_selections INT UNSIGNED NULL COMMENT '最大選択数',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_menu_item_options_menu_item_id (menu_item_id),
    INDEX idx_menu_item_options_sort_order (sort_order),
    INDEX idx_menu_item_options_is_active (is_active),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='商品オプション';
```

### 3.7 menu_item_option_values（オプション選択肢）
```sql
CREATE TABLE menu_item_option_values (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    option_id BIGINT UNSIGNED NOT NULL COMMENT 'オプションID',
    name VARCHAR(255) NOT NULL COMMENT '選択肢名',
    translations JSON NULL COMMENT '多言語翻訳（JSON）',
    price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '価格調整額',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_menu_item_option_values_option_id (option_id),
    INDEX idx_menu_item_option_values_sort_order (sort_order),
    INDEX idx_menu_item_option_values_is_active (is_active),
    FOREIGN KEY (option_id) REFERENCES menu_item_options(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='オプション選択肢';
```

### 3.8 orders（注文）
```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id BIGINT UNSIGNED NOT NULL COMMENT '店舗ID',
    session_id BIGINT UNSIGNED NOT NULL COMMENT 'セッションID',
    customer_id BIGINT UNSIGNED NULL COMMENT '顧客ID',
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
    INDEX idx_orders_customer_id (customer_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_ordered_at (ordered_at),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE RESTRICT,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE RESTRICT,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='注文';
```

### 3.9 order_items（注文明細）
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL COMMENT '注文ID',
    menu_item_id BIGINT UNSIGNED NOT NULL COMMENT 'メニュー商品ID',
    quantity INT UNSIGNED NOT NULL COMMENT '数量',
    unit_price DECIMAL(10,2) NOT NULL COMMENT '単価',
    total_price DECIMAL(10,2) NOT NULL COMMENT '小計',
    notes TEXT NULL COMMENT '備考',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_menu_item_id (menu_item_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='注文明細';
```

### 3.10 order_item_options（注文商品オプション）
```sql
CREATE TABLE order_item_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_item_id BIGINT UNSIGNED NOT NULL COMMENT '注文明細ID',
    option_id BIGINT UNSIGNED NOT NULL COMMENT 'オプションID',
    option_value_id BIGINT UNSIGNED NOT NULL COMMENT 'オプション選択肢ID',
    option_name VARCHAR(255) NOT NULL COMMENT 'オプション名（スナップショット）',
    option_value_name VARCHAR(255) NOT NULL COMMENT '選択肢名（スナップショット）',
    price_modifier DECIMAL(10,2) NOT NULL COMMENT '価格調整額（スナップショット）',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX idx_order_item_options_order_item_id (order_item_id),
    INDEX idx_order_item_options_option_id (option_id),
    INDEX idx_order_item_options_option_value_id (option_value_id),
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES menu_item_options(id) ON DELETE RESTRICT,
    FOREIGN KEY (option_value_id) REFERENCES menu_item_option_values(id) ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='注文商品オプション';
```

### 3.11 change_logs（変更履歴）
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

### 3.12 system_settings（システム設定）
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

## 4. インデックス戦略

### 4.1 主要検索パターン
- **ユーザー検索**: email, role, store_id
- **メニュー検索**: store_id, category_id, is_available, is_active
- **注文検索**: store_id, session_id, status, ordered_at
- **セッション検索**: qr_code, store_id, status, expires_at

### 4.2 複合インデックス
```sql
-- 注文検索用
CREATE INDEX idx_orders_store_status_date ON orders(store_id, status, ordered_at);

-- メニュー検索用
CREATE INDEX idx_menu_items_store_category_active ON menu_items(store_id, category_id, is_active);

-- 変更ログ同期用
CREATE INDEX idx_change_logs_sync ON change_logs(is_synced, created_at);
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
- **orders**: 1年経過後にアーカイブテーブルに移動
- **sessions**: 期限切れ後1週間でクリーンアップ

## 6. データ整合性

### 6.1 外部キー制約
- **CASCADE**: 親データ削除時に子データも削除（order_items等）
- **RESTRICT**: 子データが存在する場合は親データ削除不可（stores等）
- **SET NULL**: 親データ削除時にNULLに設定（customer_id等）

### 6.2 CHECK制約
```sql
-- 価格は0以上
ALTER TABLE menu_items ADD CONSTRAINT chk_menu_items_price CHECK (price >= 0);

-- 数量は1以上
ALTER TABLE order_items ADD CONSTRAINT chk_order_items_quantity CHECK (quantity >= 1);

-- セッション有効期限は未来日時
ALTER TABLE sessions ADD CONSTRAINT chk_sessions_expires_at CHECK (expires_at > created_at);
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
- `menu_categories.translations`: カテゴリ名、説明
- `menu_items.translations`: 商品名、説明
- `menu_item_options.translations`: オプション名
- `menu_item_option_values.translations`: 選択肢名

## 8. セキュリティ考慮事項

### 8.1 個人情報保護
- **暗号化**: クレジットカード情報等の機密データ
- **マスキング**: ログ出力時の個人情報マスキング
- **保持期間**: 不要になった個人情報の自動削除

### 8.2 監査証跡
- **change_logs**: 全てのデータ変更を記録
- **IPアドレス**: アクセス元の記録
- **ユーザーエージェント**: アクセス元デバイス情報

---

**注意**: このデータベース設計はテーブル構成の修正が予定されているため、実装時に最新の要件に合わせて調整してください。