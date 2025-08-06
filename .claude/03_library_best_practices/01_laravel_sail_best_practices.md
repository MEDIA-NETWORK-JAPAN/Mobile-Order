# Laravel Sailベストプラクティス文書

## 1. Laravel Sail 概要

### 1.1 基本構成
Laravel Sailは、Laravel開発環境をDockerで構築するためのコマンドラインインターフェースです。モバイルオーダーシステムでの開発効率と環境統一を実現します。

### 1.2 技術スタック
- **Docker Desktop**: コンテナ仮想化プラットフォーム
- **Laravel 11.x**: PHPウェブアプリケーションフレームワーク
- **MySQL 8.0**: データベース管理システム
- **Redis**: キャッシュとセッションストレージ
- **Mailpit**: 開発用メールサーバー
- **Node.js**: フロントエンドビルドツール

### 1.3 プロジェクト環境要件
- **Docker Desktop**: 最新安定版
- **Git**: バージョン管理
- **最小システム要件**: RAM 8GB以上、ストレージ 10GB以上

## 2. セットアップベストプラクティス

### 2.1 初期セットアップ手順

#### 新規プロジェクト作成
```bash
# Laravel Sailを含むプロジェクト作成
curl -s "https://laravel.build/mobile-order?with=mysql,redis,mailpit" | bash

# プロジェクトディレクトリに移動
cd mobile-order

# Sailエイリアス設定（推奨）
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'

# 初回起動
sail up -d
```

#### 既存プロジェクトへのSail追加
```bash
# composer.jsonにSailを追加
composer require laravel/sail --dev

# Sail設定ファイル生成
php artisan sail:install

# docker-compose.ymlの生成と起動
sail up -d
```

### 2.2 環境設定最適化

#### `.env`設定例
```env
APP_NAME="Mobile Order System"
APP_ENV=local
APP_KEY=base64:GENERATED_KEY
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# データベース設定
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mobile_order
DB_USERNAME=sail
DB_PASSWORD=password

# Broadcast設定
BROADCAST_DRIVER=log

# キャッシュ設定
CACHE_DRIVER=redis
FILESYSTEM_DISK=local

# キュー設定
QUEUE_CONNECTION=redis

# セッション設定
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Redis設定
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# メール設定（開発用）
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@mobile-order.local"
MAIL_FROM_NAME="${APP_NAME}"

# Vite設定
VITE_APP_NAME="${APP_NAME}"
```

#### `docker-compose.yml`カスタマイズ
```yaml
# docker-compose.yml
version: '3'
services:
    laravel.test:
        build:
            context: './vendor/laravel/sail/runtimes/8.3'
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: sail-8.3/app
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        ports:
            - '${APP_PORT:-80}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
        environment:
            WWWUSER: '${WWWUSER}'
            LARAVEL_SAIL: 1
            XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
            XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
            IGNITION_LOCAL_SITES_PATH: '${PWD}'
        volumes:
            - '.:/var/www/html'
        networks:
            - sail
        depends_on:
            - mysql
            - redis
            - mailpit
    mysql:
        image: 'mysql/mysql-server:8.0'
        ports:
            - '${FORWARD_DB_PORT:-3306}:3306'
        environment:
            MYSQL_ROOT_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ROOT_HOST: '%'
            MYSQL_DATABASE: '${DB_DATABASE}'
            MYSQL_USER: '${DB_USERNAME}'
            MYSQL_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ALLOW_EMPTY_PASSWORD: 1
        volumes:
            - 'sail-mysql:/var/lib/mysql'
            - './vendor/laravel/sail/database/mysql/create-testing-database.sql:/docker-entrypoint-initdb.d/10-create-testing-database.sql'
        networks:
            - sail
        healthcheck:
            test:
                - CMD
                - mysqladmin
                - ping
                - '-p${DB_PASSWORD}'
            retries: 3
            timeout: 5s
    redis:
        image: 'redis:alpine'
        ports:
            - '${FORWARD_REDIS_PORT:-6379}:6379'
        volumes:
            - 'sail-redis:/data'
        networks:
            - sail
        healthcheck:
            test:
                - CMD
                - redis-cli
                - ping
            retries: 3
            timeout: 5s
    mailpit:
        image: 'axllent/mailpit:latest'
        ports:
            - '${FORWARD_MAILPIT_PORT:-1025}:1025'
            - '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}:8025'
        networks:
            - sail
networks:
    sail:
        driver: bridge
volumes:
    sail-mysql:
        driver: local
    sail-redis:
        driver: local
```

### 2.3 エイリアス設定

#### Bashエイリアス設定
```bash
# ~/.bashrc または ~/.zshrc に追加
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'

# Sailコマンドの短縮形
alias sup='sail up -d'
alias sdown='sail down'
alias sps='sail ps'
alias sart='sail artisan'
alias scomposer='sail composer'
alias snpm='sail npm'
alias stest='sail test'

# 設定を反映
source ~/.bashrc  # または source ~/.zshrc
```

#### PowerShell（Windows）エイリアス
```powershell
# PowerShellプロファイルに追加
function sail { 
    if (Test-Path "sail") { 
        .\sail $args 
    } else { 
        .\vendor\bin\sail $args 
    } 
}

# 短縮エイリアス
function sup { sail up -d }
function sdown { sail down }
function sart { sail artisan $args }
function scomposer { sail composer $args }
function snpm { sail npm $args }
```

## 3. 開発ワークフローベストプラクティス

### 3.1 日常的な開発フロー

#### 開発開始時
```bash
# 1. Sailコンテナ起動
sail up -d

# 2. ログ確認（オプション）
sail logs

# 3. データベース状態確認
sail artisan migrate:status

# 4. キャッシュクリア（必要に応じて）
sail artisan config:cache
sail artisan route:cache
sail artisan view:cache

# 5. フロントエンド開発サーバー起動
sail npm run dev
```

#### 開発終了時
```bash
# 1. フロントエンド開発サーバー停止（Ctrl+C）

# 2. Sailコンテナ停止
sail down
```

### 3.2 データベース管理

#### マイグレーション管理
```bash
# マイグレーション実行
sail artisan migrate

# マイグレーション状態確認
sail artisan migrate:status

# マイグレーションロールバック
sail artisan migrate:rollback

# フレッシュマイグレーション（開発時のみ）
sail artisan migrate:fresh --seed

# マイグレーション作成
sail artisan make:migration create_products_table
```

#### シーダー管理
```bash
# シーダー実行
sail artisan db:seed

# 特定のシーダー実行
sail artisan db:seed --class=ProductSeeder

# シーダー作成
sail artisan make:seeder ProductSeeder
```

#### データベースバックアップ
```bash
# データベースダンプ作成
sail exec mysql mysqldump -u sail -p mobile_order > backup.sql

# バックアップからリストア
sail exec mysql mysql -u sail -p mobile_order < backup.sql
```

### 3.3 テスト実行

#### PHPUnitテスト
```bash
# 全テスト実行
sail test

# 特定のテストファイル実行
sail test tests/Feature/OrderTest.php

# 特定のテストメソッド実行
sail test --filter testCanCreateOrder

# カバレッジ付きテスト実行
sail test --coverage

# 並列テスト実行
sail test --parallel
```

#### Pestテスト（使用している場合）
```bash
# Pestテスト実行
sail pest

# 並列実行
sail pest --parallel

# カバレッジ付き実行
sail pest --coverage
```

### 3.4 パッケージ管理

#### Composer操作
```bash
# パッケージインストール
sail composer install

# パッケージ追加
sail composer require package/name

# 開発依存パッケージ追加
sail composer require --dev package/name

# パッケージ削除
sail composer remove package/name

# オートローダー更新
sail composer dump-autoload
```

#### NPM操作
```bash
# 依存関係インストール
sail npm install

# パッケージ追加
sail npm install package-name

# 開発依存パッケージ追加
sail npm install --save-dev package-name

# パッケージ削除
sail npm uninstall package-name

# ビルド実行
sail npm run build

# 開発サーバー起動
sail npm run dev
```

## 4. パフォーマンス最適化

### 4.1 コンテナリソース最適化

#### Docker設定調整
```yaml
# docker-compose.yml内のメモリ制限設定例
services:
    laravel.test:
        # その他の設定...
        deploy:
            resources:
                limits:
                    memory: 2G
                    cpus: '2.0'
                reservations:
                    memory: 1G
                    cpus: '1.0'
```

#### MySQL設定最適化
```yaml
mysql:
    image: 'mysql/mysql-server:8.0'
    command: --default-authentication-plugin=mysql_native_password --innodb-buffer-pool-size=1G
    environment:
        # 環境変数設定...
```

### 4.2 開発時のキャッシュ戦略

#### Artisanキャッシュコマンド
```bash
# 設定キャッシュ
sail artisan config:cache

# ルートキャッシュ
sail artisan route:cache

# ビューキャッシュ
sail artisan view:cache

# イベントキャッシュ
sail artisan event:cache

# 全キャッシュクリア
sail artisan optimize:clear
```

#### Redis活用
```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],

// Redisを使ったキャッシュ例
Cache::remember('products', 3600, function () {
    return Product::with('category')->get();
});
```

### 4.3 ファイルウォッチングの最適化

#### `.dockerignore`設定
```
node_modules
.git
.env
storage/logs
storage/framework/cache
storage/framework/sessions
storage/framework/views
```

#### Vite設定最適化
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost'
        },
        watch: {
            usePolling: true,
        }
    }
});
```

## 5. デバッグとトラブルシューティング

### 5.1 Xdebugセットアップ

#### Xdebug有効化
```bash
# Xdebugを有効にしてSail起動
SAIL_XDEBUG_MODE=develop,debug sail up -d

# 設定確認
sail exec laravel.test php -m | grep xdebug
```

#### VS Code設定（`.vscode/launch.json`）
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

### 5.2 ログ管理

#### ログレベル設定
```env
# .env
LOG_LEVEL=debug  # debug, info, notice, warning, error, critical, alert, emergency
```

#### ログ確認コマンド
```bash
# アプリケーションログ確認
sail logs

# 特定のサービスログ確認
sail logs mysql
sail logs redis

# リアルタイムログ監視
sail logs -f

# Laravelログ確認
sail exec laravel.test tail -f storage/logs/laravel.log
```

### 5.3 よくある問題と解決方法

#### ポート競合エラー
```bash
# 使用中のポート確認
lsof -i :80
lsof -i :3306

# .envでポート変更
APP_PORT=8080
FORWARD_DB_PORT=3307
```

#### パーミッションエラー
```bash
# ストレージディレクトリのパーミッション修正
sail exec laravel.test chmod -R 775 storage
sail exec laravel.test chmod -R 775 bootstrap/cache

# 所有者変更
sail exec laravel.test chown -R sail:sail storage
sail exec laravel.test chown -R sail:sail bootstrap/cache
```

#### データベース接続エラー
```bash
# MySQL接続テスト
sail exec mysql mysql -u sail -p

# データベース再作成
sail artisan migrate:fresh

# MySQLサービス再起動
sail restart mysql
```

#### メモリ不足エラー
```bash
# PHPメモリ制限確認
sail exec laravel.test php -ini | grep memory_limit

# Composerメモリ制限無効化
sail composer install --no-memory-limit
```

## 6. 本番環境への移行準備

### 6.1 環境変数管理

#### 本番用環境変数例
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=your-production-host
DB_PORT=3306
DB_DATABASE=your_production_db
DB_USERNAME=your_production_user
DB_PASSWORD=your_secure_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
```

### 6.2 最適化コマンド
```bash
# 本番用最適化
sail artisan optimize
sail artisan config:cache
sail artisan route:cache
sail artisan view:cache
sail artisan event:cache

# フロントエンドビルド
sail npm run build
```

### 6.3 セキュリティチェックリスト

#### 設定確認項目
- [ ] `APP_DEBUG=false`に設定
- [ ] `APP_KEY`が設定済み
- [ ] データベース認証情報が安全
- [ ] Redis認証情報が設定済み
- [ ] HTTPS設定が完了
- [ ] 不要なパッケージが削除済み
- [ ] ログレベルが適切に設定

## 7. チーム開発ベストプラクティス

### 7.1 Docker設定の共有

#### バージョン管理に含める必要があるファイル
```
docker-compose.yml
.env.example
sail
```

#### バージョン管理から除外するファイル（`.gitignore`）
```
.env
.env.backup
docker-compose.override.yml
```

### 7.2 開発環境統一

#### READMEでのセットアップ手順文書化
```markdown
## 開発環境セットアップ

1. リポジトリクローン
```bash
git clone https://github.com/your-org/mobile-order.git
cd mobile-order
```

2. 環境設定
```bash
cp .env.example .env
```

3. Sail起動
```bash
./vendor/bin/sail up -d
```

4. 依存関係インストール
```bash
./vendor/bin/sail composer install
./vendor/bin/sail npm install
```

5. データベースセットアップ
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
```
```

### 7.3 CI/CD統合

#### GitHub Actions設定例
```yaml
# .github/workflows/test.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:alpine
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.3
        extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv
        coverage: none
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress --no-suggest
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Directory Permissions
      run: chmod -R 755 storage bootstrap/cache
    
    - name: Run tests
      run: php artisan test
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: testing
        DB_USERNAME: root
        DB_PASSWORD: password
```

---

このLaravel Sailベストプラクティス文書により、効率的で一貫した開発環境を構築・維持できます。チーム全体で統一された環境を使用し、本番環境への移行もスムーズに行えます。