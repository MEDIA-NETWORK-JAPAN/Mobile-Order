# モバイル注文システム - ClaudeCode引き継ぎドキュメント

## プロジェクト概要

### システム名
飲食店向けモバイル注文システム

### 技術スタック
- **バックエンド**: Laravel 10.48.29
- **フロントエンド**: Laravel Livewire 3.5+ / Alpine.js（バンドル済み）
- **UIフレームワーク**: MaryUI v2.x + TailwindCSS 4.0
- **データベース**: MySQL 8.0+
- **認証**: Laravel Breeze（管理者）+ Laravel Sanctum（API）
- **ビルドツール**: Vite 4.x
- **開発環境**: Laravel Sail（Docker）

## システム要件サマリー

### 主要機能
1. **QRコード読み取りによる注文開始**
   - POSレジ発行のQRコードをスマホで読み取り
   - 有効期限3時間（カスタマイズ可能）

2. **メニュー表示・注文**
   - 画像中心のグリッド表示
   - オプション選択機能（麺の硬さ、トッピング等）
   - カート機能（20品目/注文、10個/商品の上限）

3. **リアルタイム更新**
   - ポーリング方式（10-30秒間隔）
   - 売り切れ状態の自動更新

4. **多言語対応**
   - 日本語、英語、繁体字、簡体字、韓国語

5. **POS連携**
   - REST API（Laravel Sanctum認証）
   - 変更記録テーブル方式によるポーリング

## ディレクトリ構造（予定）

```
mobile-order-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── Pos/
│   │   │   │       ├── PosPollingController.php
│   │   │   │       ├── PosMenuController.php
│   │   │   │       └── PosOrderController.php
│   │   │   ├── Admin/
│   │   │   │   ├── AdminDashboardController.php
│   │   │   │   └── ApiTokenController.php
│   │   │   └── Customer/
│   │   │       ├── MenuController.php
│   │   │       ├── CartController.php
│   │   │       └── OrderController.php
│   │   ├── Middleware/
│   │   │   ├── EnsurePosApiAccess.php
│   │   │   ├── AdminMiddleware.php
│   │   │   └── SuperAdminMiddleware.php
│   │   └── Livewire/
│   │       ├── ProductGrid.php
│   │       ├── CartComponent.php
│   │       └── OrderStatus.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Product.php
│   │   ├── Session.php
│   │   └── ChangeLog.php
│   └── Traits/
│       └── LogsChanges.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_orders_table.php
│   │   ├── create_order_items_table.php
│   │   ├── create_products_table.php
│   │   ├── create_sessions_table.php
│   │   └── create_change_logs_table.php
│   └── seeders/
│       ├── RoleSeeder.php
│       ├── AdminUserSeeder.php
│       └── SampleMenuSeeder.php
├── resources/
│   └── views/
│       ├── customer/
│       │   ├── menu.blade.php
│       │   ├── cart.blade.php
│       │   └── order-history.blade.php
│       └── admin/
│           ├── dashboard.blade.php
│           └── api-tokens.blade.php
└── config/
    └── pos.php
```

## データベース設計

### 主要テーブル
**テーブル構成は後ほど修正が入るので、留意しておいてください**

#### users（認証用）
- id, name, email, password
- is_pos_system (boolean) - POS識別用
- 役割: admin, super_admin, staff, customer, pos_system

#### sessions（席管理）
- id, table_number, qr_code, expires_at
- status (active/expired)

#### orders（注文）
- id, session_id, order_number
- status (pending/cooking/completed)
- total_amount, created_at

#### order_items（注文明細）
- id, order_id, product_id
- quantity, price, options (JSON)

#### products（メニュー）
- id, pos_id, name, price
- category, image_url
- is_available (boolean)
- translations (JSON) - 多言語対応

#### change_logs（変更記録）
- id, entity_type, entity_id
- action (created/updated/deleted)
- changes (JSON), is_synced
- synced_at, synced_by

## 認証設計

### 3種類の認証方式

1. **Laravel Breeze（Web認証）**
   - 対象: 管理者、スーパーユーザー
   - 方式: Session/Cookie
   - 2要素認証対応

2. **Laravel Sanctum（モバイルAPI）**
   - 対象: お客様のスマートフォン
   - 方式: Bearer Token
   - 有効期限: 30日

3. **Laravel Sanctum（POS API）**
   - 対象: POSシステム
   - 方式: Bearer Token
   - 有効期限: 無期限
   - Bearer Token認証

## 実装優先順位

### Phase 1: 基盤構築
1. Laravel 10プロジェクト作成
2. 認証システム（Breeze + Sanctum）
3. データベース設計・マイグレーション
4. 基本的なルーティング

### Phase 2: 管理機能
1. 管理者ログイン（Breeze）
2. メニュー管理CRUD
3. 多言語翻訳管理
4. POSトークン管理

### Phase 3: お客様向け機能
1. QRコード読み取り・セッション開始
2. メニュー表示（Livewire）
3. カート機能
4. 注文送信・履歴表示

### Phase 4: POS連携
1. 変更記録テーブル実装
2. ポーリングAPI
3. メニュー同期
4. ステータス更新

### Phase 5: 最適化・本番準備
1. パフォーマンス最適化
2. エラーハンドリング強化
3. ログ・監視設定
4. デプロイメント準備

## 環境変数設定（.env）

```env
# アプリケーション
APP_NAME="Mobile Order System"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# データベース
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mobile_order
DB_USERNAME=sail
DB_PASSWORD=password

# セッション
SESSION_DRIVER=redis
SESSION_LIFETIME=60

# キャッシュ
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# メール（開発環境）
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

# POS連携
POS_ALLOWED_IPS=192.168.1.100,192.168.1.101
POS_IMAGE_SYNC_METHOD=url
```

## 開発開始コマンド

```bash
# プロジェクト作成
composer create-project laravel/laravel mobile-order-system "10.*"
cd mobile-order-system

# Sail環境構築
php artisan sail:install
./vendor/bin/sail up -d

# パッケージインストール
./vendor/bin/sail composer require laravel/breeze --dev
./vendor/bin/sail composer require livewire/livewire
./vendor/bin/sail composer require robsontenorio/mary
./vendor/bin/sail composer require simplesoftwareio/simple-qrcode
./vendor/bin/sail composer require intervention/image-laravel
./vendor/bin/sail composer require spatie/laravel-medialibrary
./vendor/bin/sail composer require spatie/laravel-translatable
./vendor/bin/sail composer require spatie/laravel-activitylog

# Breeze初期化
./vendor/bin/sail artisan breeze:install blade
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# MaryUI初期化
./vendor/bin/sail artisan mary:install
```

## 実装時の注意点

1. **文字コード**: UTF8MB4必須（多言語対応）
2. **タイムゾーン**: Asia/Tokyo
3. **画像形式**: WebP推奨（モバイル最適化）
4. **セッション**: Redis使用（パフォーマンス）
5. **ポーリング間隔**: 10-30秒（バッテリー考慮）

## 参照ドキュメント

### 必須参照資料

1. **.claude/Preparation/01_MO_concept_requirements.md**
   - システムの機能要件定義書
   - ステークホルダー定義、機能詳細、非機能要件を網羅
   - このドキュメントがすべての設計の基盤

2. **.claude/Preparation/architectList.md**
   - 現在のプロジェクト構造
   - 使用技術の一覧
   - ディレクトリ構成の説明

3. **.claude/Preparation/compass_artifact.md**
   - 各ライブラリの推奨バージョン
   - インストール方法と設定
   - 実装時の注意点とトラブルシューティング

4. **.claude/Preparation/02_Reference information.md**
   - AI駆動開発のガイドライン
   - 設計ドキュメントの作成方法
   - AIとの効果的な協働方法

### 参考リンク

- [Laravel 10.x 公式ドキュメント](https://laravel.com/docs/10.x)
- [Livewire 3.x 公式ドキュメント](https://livewire.laravel.com/docs)
- [MaryUI ドキュメント](https://mary-ui.com)
- [Laravel Sanctum ドキュメント](https://laravel.com/docs/10.x/sanctum)
- [TailwindCSS ドキュメント](https://tailwindcss.com/docs)

## 次のステップ（ClaudeCodeでの作業）

### Phase 0: プロジェクト初期化とドキュメント整備（推奨：2-3日）

1. **プロジェクト作成とセットアップ**
   ```bash
   # 上記の「開発開始コマンド」を実行
   composer create-project laravel/laravel mobile-order-system "10.*"
   cd mobile-order-system
   ```

2. **CLAUDE.md作成**
   - 最優先で作成
   - プロジェクトの全体像をAIに理解させる

3. **基盤設計書の作成**（以下の順序で）
   - `.claude/00_project/01_mo_concept_requirements.md`（既存をコピー）
   - `.claude/01_development_docs/01_architecture_design.md`
   - `.claude/01_development_docs/02_database_design.md`
   - `.claude/01_development_docs/13_auth_authorization_design.md`

### Phase 1: 基盤構築（推奨：3-4日）

4. **データベース実装**
   - マイグレーションファイル作成
   - モデル定義
   - リレーション設定

5. **認証システム構築**
   - Laravel Breeze設定（管理者用）
   - Sanctum設定（API用）
   - ミドルウェア実装

6. **基本ルーティング**
   - Web routes（管理画面）
   - API routes（モバイル、POS）

### Phase 2: 管理機能（推奨：2-3日）

7. **管理者ログイン実装**
   - Breezeカスタマイズ
   - ロール管理

8. **メニュー管理CRUD**
   - 管理画面UI
   - 多言語対応

### Phase 3: お客様向け機能（推奨：4-5日）

9. **QRコード機能**
   - セッション管理
   - QRコード生成

10. **メニュー表示**
    - Livewireコンポーネント
    - リアルタイム更新

11. **注文機能**
    - カート実装
    - 注文送信

### Phase 4: POS連携（推奨：3-4日）

12. **変更記録テーブル**
    - LogsChangesトレイト
    - 自動記録機能

13. **ポーリングAPI**
    - 2段階API実装
    - 同期管理

### Phase 5: 最適化・本番準備（推奨：2-3日）

14. **パフォーマンス最適化**
    - キャッシュ実装
    - クエリ最適化

15. **本番環境準備**
    - Docker設定
    - デプロイメント準備

## ClaudeCodeでの効果的な開発フロー

### 1. 毎日の開始時
```
# AIへの指示
本日は[Phase X]の[具体的なタスク]を実装します。
CLAUDE.mdと関連する設計書を確認し、実装方針を提案してください。
```

### 2. 機能実装時
```
# AIへの指示
[機能名]を実装します。
以下のドキュメントを参照してください：
- [関連ドキュメントのパス]
- [関連ドキュメントのパス]

実装前に、設計の理解が正しいか確認させてください。
```

### 3. コードレビュー時
```
# AIへの指示
実装した[機能名]のコードをレビューしてください。
特に以下の観点でチェックしてください：
- 設計書との整合性
- セキュリティ
- パフォーマンス
```

### 4. ドキュメント更新時
```
# AIへの指示
[機能名]の実装で発見した注意点を
[ドキュメントパス]に追記してください。
```

## 成功のためのTips

1. **段階的な実装**
   - 一度に多くを実装しようとしない
   - 各Phaseを確実に完了させてから次へ

2. **ドキュメントファースト**
   - 実装前に必ず設計書を作成/更新
   - AIと設計を共有してから実装

3. **定期的な検証**
   - 各Phase終了時に動作確認
   - 設計と実装の乖離をチェック

4. **AIとの対話**
   - 不明な点は積極的に質問
   - AIの提案に対してフィードバック

この手順に従うことで、約3週間で基本的なモバイル注文システムの構築が可能です。
