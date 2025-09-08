# AWS ECS Fargate構成設計書
Mobile Order System - コンテナベースアーキテクチャ

## 📋 構成概要

### システムアーキテクチャ
```
[Internet]
    ↓
[Route 53] - DNS
    ↓
[ALB] - Application Load Balancer
    ├─→ [ECS Service: Web] - Nginx + PHP-FPM
    └─→ [ECS Service: API] - Laravel API
    
[ECS Task: Queue Worker] - Queue処理
[ECS Scheduled Task: Batch] - 定期バッチ

[RDS MySQL] - データベース
[ElastiCache Redis] - セッション/キャッシュ
[S3] - 静的ファイル/バックアップ
[ECR] - Dockerイメージ管理
```

## 🐳 ECS Fargate構成詳細

### 1. ECSクラスター
```yaml
クラスター名: mobile-order-cluster
キャパシティプロバイダー: FARGATE
Container Insights: 有効
```

### 2. タスク定義

#### Web/APIタスク
```yaml
タスク定義: mobile-order-web
CPU: 512 (0.5 vCPU)
メモリ: 1024 (1GB)
ネットワークモード: awsvpc

コンテナ:
  - nginx:
      イメージ: {ECR_URI}/mobile-order-nginx:latest
      CPU: 128
      メモリ: 256
      ポート: 80
      ヘルスチェック: /health
      
  - php-fpm:
      イメージ: {ECR_URI}/mobile-order-app:latest
      CPU: 384
      メモリ: 768
      ポート: 9000
      環境変数:
        - APP_ENV=production
        - DB_HOST={RDS_ENDPOINT}
        - REDIS_HOST={ELASTICACHE_ENDPOINT}
      Secrets:
        - DB_PASSWORD (Secrets Manager)
        - APP_KEY (Secrets Manager)
```

#### Queue Workerタスク
```yaml
タスク定義: mobile-order-worker
CPU: 256 (0.25 vCPU)
メモリ: 512 (0.5GB)
ネットワークモード: awsvpc

コンテナ:
  - worker:
      イメージ: {ECR_URI}/mobile-order-app:latest
      CPU: 256
      メモリ: 512
      コマンド: ["php", "artisan", "queue:work", "--tries=3"]
      環境変数: (Webタスクと同様)
```

#### POS同期タスク（追加）
```yaml
タスク定義: mobile-order-pos-sync
CPU: 256 (0.25 vCPU)
メモリ: 512 (0.5GB)
ネットワークモード: awsvpc

コンテナ:
  - pos-sync:
      イメージ: {ECR_URI}/mobile-order-app:latest
      CPU: 256
      メモリ: 512
      コマンド: ["php", "artisan", "pos:poll-changes"]
      環境変数: (Webタスクと同様)
      
# Scheduled Taskとして設定
# EventBridge Rule: rate(5 seconds)
```

### 3. ECSサービス

#### Webサービス
```yaml
サービス名: mobile-order-web-service
起動タイプ: FARGATE
タスク数: 2 (最小: 1, 最大: 4)
デプロイメント:
  - タイプ: ローリングアップデート
  - 最小ヘルシー率: 100%
  - 最大率: 200%
Auto Scaling:
  - ターゲット追跡: CPU使用率 70%
  - スケールアウト: 1分後
  - スケールイン: 5分後
```

#### Workerサービス
```yaml
サービス名: mobile-order-worker-service
起動タイプ: FARGATE
タスク数: 1
Auto Scaling:
  - メトリクス: SQSキュー長
  - スケールアウト: キュー > 10
```

### 4. ロードバランサー設定
```yaml
ALB:
  - ターゲットグループ:
      - プロトコル: HTTP
      - ポート: 80
      - ヘルスチェックパス: /health
      - 間隔: 30秒
      - タイムアウト: 5秒
  - リスナー:
      - HTTP:80 → HTTPS:443リダイレクト
      - HTTPS:443 → ターゲットグループ
  - SSL証明書: ACM (AWS Certificate Manager)
```

## 📦 Dockerイメージ構成

### Nginx用Dockerfile
```dockerfile
FROM nginx:alpine

# Nginx設定
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# ヘルスチェック用
RUN echo "OK" > /usr/share/nginx/html/health

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### PHP-FPM用Dockerfile
```dockerfile
FROM php:8.1-fpm-alpine

# 必要な拡張機能インストール
RUN apk add --no-cache \
    mysql-client \
    redis \
    && docker-php-ext-install pdo pdo_mysql opcache pcntl

# Composerインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# アプリケーションコピー
COPY . .

# 本番用最適化
RUN composer install --no-dev --optimize-autoloader \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# 権限設定
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
```

## 💾 データストア構成

### RDS MySQL
```yaml
インスタンスクラス: db.t3.micro
エンジン: MySQL 8.0
ストレージ: 20GB gp3
マルチAZ: なし
自動バックアップ: 7日間
暗号化: 有効
サブネットグループ: プライベートサブネット
```

### ElastiCache Redis
```yaml
ノードタイプ: cache.t4g.micro
エンジン: Redis 7.0
パラメーターグループ: カスタム (maxmemory-policy: allkeys-lru)
サブネットグループ: プライベートサブネット
暗号化: 転送時暗号化有効
```

### S3バケット
```yaml
バケット:
  - mobile-order-assets: 静的ファイル (CloudFront経由)
  - mobile-order-backups: バックアップ
  - mobile-order-logs: ログアーカイブ
```

## 🔒 セキュリティ設定

### VPC構成
```yaml
VPC CIDR: 10.0.0.0/16

パブリックサブネット:
  - 10.0.1.0/24 (AZ-1a) - ALB用
  - 10.0.2.0/24 (AZ-1c) - ALB用

プライベートサブネット:
  - 10.0.11.0/24 (AZ-1a) - ECS Tasks
  - 10.0.12.0/24 (AZ-1c) - ECS Tasks
  - 10.0.21.0/24 (AZ-1a) - RDS/ElastiCache
  - 10.0.22.0/24 (AZ-1c) - RDS/ElastiCache
```

### セキュリティグループ
```yaml
ALB-SG:
  - Inbound: 80, 443 from 0.0.0.0/0
  - Outbound: All

ECS-SG:
  - Inbound: 80 from ALB-SG
  - Outbound: All

RDS-SG:
  - Inbound: 3306 from ECS-SG
  - Outbound: None

Redis-SG:
  - Inbound: 6379 from ECS-SG
  - Outbound: None
```

### IAMロール
```yaml
ECSTaskExecutionRole:
  - ECRからイメージ取得
  - CloudWatch Logsへの書き込み
  - Secrets Manager読み取り

ECSTaskRole:
  - S3アクセス
  - SQS操作
  - Parameter Store読み取り
```

## 📊 監視・ログ

### CloudWatch設定
```yaml
Container Insights: 有効

メトリクス:
  - ECSサービスメトリクス
  - カスタムメトリクス (Laravel)
  
アラーム:
  - CPU使用率 > 80%
  - メモリ使用率 > 80%
  - タスク数 < 1
  - ALB UnHealthyHostCount > 0
  - RDS CPU > 80%
  
ログ:
  - /ecs/mobile-order/web
  - /ecs/mobile-order/worker
  - /aws/rds/mobile-order
```

### X-Ray (オプション)
```yaml
トレーシング: 有効
サンプリングレート: 10%
```

## 🔧 詳細設定要件（設計書準拠）

### 環境変数設定（完全版）
```env
# Laravel基本設定
APP_ENV=production
APP_DEBUG=false
APP_KEY={32文字キー}
APP_URL=https://your-domain.com

# データベース設定
DB_CONNECTION=mysql
DB_HOST={RDS_ENDPOINT}
DB_PORT=3306
DB_DATABASE=mobile_order
DB_USERNAME={RDS_USERNAME}
DB_PASSWORD={RDS_PASSWORD}

# セッション管理（ハイブリッド方式）
SESSION_DRIVER=redis           # Laravelセッション状態管理
SESSION_LIFETIME=          # 無制限（席利用時間）
SESSION_ENCRYPT=true
SESSION_CONNECTION=session

# Redis設定（3つの専用用途）
REDIS_HOST={ELASTICACHE_ENDPOINT}
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_SESSION_DB=0           # Laravelセッション用
REDIS_CACHE_DB=1            # メニューキャッシュ用
REDIS_CART_DB=2             # カートデータ用

# キャッシュ設定
CACHE_DRIVER=redis
CACHE_PREFIX=mobile_order
CACHE_CONNECTION=cache

# POS連携設定（設計書準拠）
POS_TOKEN_TTL=86400          # 24時間
POS_POLLING_INTERVAL=5       # 5秒間隔
POS_POLLING_ENABLED=true
POS_SYNC_BATCH_SIZE=10       # バッチ処理サイズ
POS_HEALTH_CHECK_WARNING=30  # 30秒でwarning
POS_HEALTH_CHECK_ERROR=90    # 90秒でerror
POS_RATE_LIMIT=120          # 120回/分

# QRコード運用設定
QR_MODE=temporary           # fixed/temporary
QR_SESSION_DEFAULT_TTL=10800 # 3時間（秒）

# 多言語設定
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
SUPPORTED_LOCALES=ja,en,zh-TW,zh-CN,ko

# カート設定
CART_TTL=1800              # 30分（秒）
CART_AUTO_EXTEND=true      # 自動延長

# ゲストセッション設定
GUEST_SESSION_TTL=1800     # 30分
GUEST_SESSION_AUTO_EXTEND=true

# ファイルストレージ
FILESYSTEM_DISK=s3
AWS_BUCKET=mobile-order-assets
AWS_REGION=ap-northeast-1

# メール設定
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Mobile Order System"

# ログ設定
LOG_CHANNEL=stack
LOG_STACK=daily,cloudwatch
LOG_LEVEL=info

# 監視設定
APP_MONITORING=true
METRICS_ENABLED=true
```

### Redis接続設定詳細
```php
// config/database.php - Redis接続設定
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    // Laravelセッション用
    'session' => [
        'host' => env('REDIS_HOST'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_SESSION_DB', 0),
        'prefix' => 'laravel_session:',
    ],
    
    // キャッシュ用（メニューデータ等）
    'cache' => [
        'host' => env('REDIS_HOST'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
        'prefix' => 'cache:',
    ],
    
    // カート専用
    'cart' => [
        'host' => env('REDIS_HOST'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CART_DB', 2),
        'prefix' => 'guest_cart:',
    ],
],
```

## 💰 月額コスト試算

| サービス | 構成 | 月額 |
|---------|------|------|
| ECS Fargate (Web) | 0.5vCPU×1GB×2タスク | 8,000円 |
| ECS Fargate (Worker) | 0.25vCPU×0.5GB×1タスク | 2,000円 |
| ECS Fargate (POS Sync) | 0.25vCPU×0.5GB×1タスク | 2,000円 |
| ALB | 1台 | 3,125円 |
| RDS MySQL | t3.micro | 3,750円 |
| ElastiCache Redis | t4g.micro | 1,500円 |
| ECR | 10GB | 125円 |
| S3 | 50GB | 250円 |
| データ転送 | 100GB | 1,250円 |
| CloudWatch | ログ+メトリクス | 1,000円 |
| Secrets Manager | 8シークレット | 800円 |
| **合計** | | **約23,800円** |

※ Mobile Order Systemの特性上、リアルタイム性・高可用性が重要なためFargate Spot使用せず

## 🚀 デプロイメントフロー

### 初期構築
1. ECRリポジトリ作成
2. Dockerイメージビルド&プッシュ
3. タスク定義作成
4. ECSサービス起動

### 継続的デプロイ (CI/CD)
```yaml
GitHub Actions:
  1. コードプッシュ
  2. Dockerイメージビルド
  3. ECRへプッシュ
  4. ECSサービス更新 (新しいタスク定義)
  5. ローリングデプロイ
```

## 📝 運用上の考慮事項

### スケーリング戦略
- **平常時**: Web×2タスク、Worker×1タスク
- **ピーク時**: Auto Scalingで自動拡張
- **夜間**: Scheduled Scalingで縮小

### コスト最適化
1. **Savings Plans**: 1年契約で20%削減（推奨）
2. **Reserved Instance**: 長期利用での割引活用
3. **ECRライフサイクル**: 古いイメージ自動削除
4. **S3ライフサイクル**: ログをGlacierへ移動
5. **リソース監視**: CPU/メモリ使用率の定期見直し

### 障害対応
- **ECSタスク障害**: 自動再起動
- **AZ障害**: 別AZで自動起動
- **RDS障害**: スナップショットから復元

## 次のステップ

1. AWSアカウント準備
2. VPCとネットワーク構築
3. ECRリポジトリ作成
4. Dockerイメージ作成
5. ECSクラスター構築
6. RDS/ElastiCache構築
7. ECSサービスデプロイ
8. 監視設定
9. CI/CDパイプライン構築
