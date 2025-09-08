# Mobile Order System - ECS Fargate アーキテクチャ詳細解説

## システムアーキテクチャ概要

Mobile Order SystemはAWS ECS Fargateをベースとしたコンテナ化されたマイクロサービスアーキテクチャです。本文書では、各コンポーネントの役割と相互連携について詳しく解説します。

## アーキテクチャの全体像

```mermaid
graph TB
    subgraph "外部ネットワーク"
        User[👤 エンドユーザー]
        POS[🖥️ POS端末<br/>オンプレミス]
    end
    
    subgraph "AWS Route 53"
        DNS[DNS<br/>mobile-order.example.com]
    end
    
    subgraph "AWS VPC (10.0.0.0/16)"
        subgraph "Internet Gateway"
            IGW[🌐 インターネットゲートウェイ]
        end
        
        subgraph "Public Subnets"
            subgraph "Availability Zone A"
                PubSubA[📡 Public Subnet A<br/>10.0.1.0/24]
                NATGWA[NAT Gateway A]
            end
            subgraph "Availability Zone C"  
                PubSubC[📡 Public Subnet C<br/>10.0.2.0/24]
                NATGWC[NAT Gateway C]
            end
        end
        
        subgraph "Application Load Balancer"
            ALB[⚖️ Mobile Order ALB<br/>Internet-facing]
            TG[🎯 Target Group<br/>mobile-order-web-tg]
        end
        
        subgraph "Private Subnets"
            subgraph "AZ-A Private"
                PrivSubA[🔒 Private Subnet A<br/>10.0.3.0/24]
                subgraph "ECS Tasks AZ-A"
                    TaskA1[📦 Task A1<br/>Nginx + PHP-FPM]
                    TaskA2[📦 Task A2<br/>Nginx + PHP-FPM]
                end
            end
            
            subgraph "AZ-C Private"
                PrivSubC[🔒 Private Subnet C<br/>10.0.4.0/24]
                subgraph "ECS Tasks AZ-C"
                    TaskC1[📦 Task C1<br/>Nginx + PHP-FPM]
                    TaskC2[📦 Task C2<br/>Nginx + PHP-FPM]
                end
            end
            
            subgraph "データ層"
                Redis[🔴 ElastiCache Redis<br/>Cluster Mode無効<br/>cache.t3.micro]
                RDS[🗄️ RDS MySQL<br/>Multi-AZ<br/>db.t3.medium]
            end
        end
    end
    
    subgraph "AWS マネージドサービス"
        subgraph "コンテナレジストリ"
            ECRApp[📦 ECR App<br/>mobile-order/app]
            ECRNginx[📦 ECR Nginx<br/>mobile-order/nginx]
        end
        
        subgraph "セキュリティ"
            SecretsManager[🔐 Secrets Manager]
            SecretDB[(DB認証情報)]
            SecretRedis[(Redis設定)]
            SecretApp[(Laravel Key)]
        end
        
        subgraph "監視・ログ"
            CloudWatch[📊 CloudWatch Logs]
            LogGroup[📝 /ecs/mobile-order/web]
        end
        
        subgraph "ECS管理"
            ECSCluster[🐳 ECS Cluster<br/>mobile-order-cluster]
            ECSService[⚙️ ECS Service<br/>mobile-order-web-service]
        end
    end

    %% 接続関係
    User --> DNS
    DNS --> IGW
    POS --> IGW
    IGW --> ALB
    ALB --> TG
    TG --> TaskA1
    TG --> TaskA2
    TG --> TaskC1
    TG --> TaskC2
    
    TaskA1 --> Redis
    TaskA1 --> RDS
    TaskA2 --> Redis
    TaskA2 --> RDS
    TaskC1 --> Redis
    TaskC1 --> RDS
    TaskC2 --> Redis
    TaskC2 --> RDS
    
    TaskA1 -.-> SecretsManager
    TaskA2 -.-> SecretsManager
    TaskC1 -.-> SecretsManager
    TaskC2 -.-> SecretsManager
    
    SecretsManager --> SecretDB
    SecretsManager --> SecretRedis
    SecretsManager --> SecretApp
    
    TaskA1 --> CloudWatch
    TaskA2 --> CloudWatch
    TaskC1 --> CloudWatch
    TaskC2 --> CloudWatch
    
    NATGWA --> IGW
    NATGWC --> IGW
    
    PrivSubA --> NATGWA
    PrivSubC --> NATGWC
    
    ECSService --> TaskA1
    ECSService --> TaskA2
    ECSService --> TaskC1
    ECSService --> TaskC2

    %% スタイリング
    classDef userClass fill:#e3f2fd
    classDef awsService fill:#fff3e0
    classDef container fill:#f3e5f5
    classDef data fill:#e8f5e8
    classDef security fill:#fff8e1
    classDef network fill:#fce4ec
    
    class User,POS userClass
    class ALB,DNS,CloudWatch,SecretsManager,ECSCluster,ECSService awsService
    class TaskA1,TaskA2,TaskC1,TaskC2,ECRApp,ECRNginx container
    class Redis,RDS,SecretDB,SecretRedis,SecretApp data
    class IGW,PubSubA,PubSubC,PrivSubA,PrivSubC,NATGWA,NATGWC network
```

## コンポーネント詳細解説

### 1. ネットワーク層の設計思想

#### 1.1 VPC設計
```
VPC CIDR: 10.0.0.0/16 (65,536 IP addresses)
├── Public Subnet A:  10.0.1.0/24 (256 addresses) 
├── Public Subnet C:  10.0.2.0/24 (256 addresses)
├── Private Subnet A: 10.0.3.0/24 (256 addresses)
└── Private Subnet C: 10.0.4.0/24 (256 addresses)
```

**設計意図:**
- **Multi-AZ構成**: 高可用性を実現するため、ap-northeast-1aとap-northeast-1cの2つのAZに分散配置
- **パブリック/プライベート分離**: セキュリティ強化のため、インターネット向けリソース（ALB）とアプリケーション層を分離
- **スケーラビリティ**: 各サブネットに十分なIP空間を確保（将来的な拡張を考慮）

#### 1.2 セキュリティグループ設計

```mermaid
graph LR
    subgraph "セキュリティグループ構成"
        ALB_SG[ALB Security Group<br/>sg-02d611335aaca752d]
        ECS_SG[ECS Security Group<br/>sg-07144d06e5a816f3b]
        Redis_SG[Redis Security Group<br/>sg-redis-xxxxxx]
        RDS_SG[RDS Security Group<br/>sg-rds-xxxxxx]
    end
    
    Internet[🌐 インターネット] --> ALB_SG
    ALB_SG --> ECS_SG
    ECS_SG --> Redis_SG
    ECS_SG --> RDS_SG
    ECS_SG --> DNS[🌐 DNS<br/>UDP/TCP 53]
    
    style ALB_SG fill:#ffcdd2
    style ECS_SG fill:#f8bbd9  
    style Redis_SG fill:#e1bee7
    style RDS_SG fill:#d1c4e9
```

**セキュリティルール:**

1. **ALB Security Group**
   - Inbound: HTTP/HTTPS (80/443) from 0.0.0.0/0
   - Outbound: HTTP (80) to ECS Security Group

2. **ECS Security Group**
   - Inbound: HTTP (80) from ALB Security Group
   - **重要:** DNS解決用 UDP/TCP 53 from VPC CIDR (10.0.0.0/16)
   - Outbound: All traffic (443 for Secrets Manager, 3306 for MySQL, 6379 for Redis)

3. **Redis Security Group**  
   - Inbound: Redis (6379) from ECS Security Group

4. **RDS Security Group**
   - Inbound: MySQL (3306) from ECS Security Group

**重要な設計ポイント:**
- DNS解決ルール（53番ポート）がRedis接続に必須
- 最小権限原則に基づくポート開放
- セキュリティグループ間の参照による動的な許可設定

### 2. コンテナオーケストレーション層

#### 2.1 ECS Fargateアーキテクチャ

```mermaid
graph TB
    subgraph "ECS Fargate Task"
        subgraph "Task Definition"
            TD[📋 mobile-order-web:5<br/>CPU: 512, Memory: 1024MB]
        end
        
        subgraph "コンテナ構成"
            subgraph "Nginx Container"
                NginxImg[🌐 Nginx 1.25-alpine]
                NginxConf[📄 default.conf]
                NginxPort[🔌 Port 80]
            end
            
            subgraph "PHP-FPM Container"  
                PHPImg[🐘 PHP 8.2-FPM-alpine]
                LaravelApp[⚙️ Laravel Application]
                PHPPort[🔌 Port 9000]
            end
        end
        
        subgraph "ネットワーク"
            TaskNetworking[🔗 ENI<br/>プライベートIP自動割り当て]
        end
        
        subgraph "ストレージ"
            TaskStorage[💾 Ephemeral Storage<br/>20GB]
        end
    end
    
    subgraph "外部リソース"
        ALB_TG[⚖️ ALB Target Group]
        ECR[📦 ECR Repository]
        Secrets[🔐 Secrets Manager]
        Logs[📊 CloudWatch Logs]
    end
    
    ALB_TG --> NginxPort
    NginxPort --> PHPPort
    ECR --> NginxImg
    ECR --> PHPImg
    Secrets --> LaravelApp
    LaravelApp --> Logs
    NginxImg --> Logs
```

**Task Definition構成:**

```json
{
  "family": "mobile-order-web",
  "revision": 5,
  "networkMode": "awsvpc",
  "requiresCompatibilities": ["FARGATE"],
  "cpu": "512",
  "memory": "1024",
  "executionRoleArn": "arn:aws:iam::account:role/ecsExecutionRole",
  "containerDefinitions": [
    {
      "name": "nginx",
      "image": "670704545755.dkr.ecr.ap-northeast-1.amazonaws.com/mobile-order/nginx:latest",
      "portMappings": [{"containerPort": 80, "protocol": "tcp"}],
      "essential": true,
      "dependsOn": [{"containerName": "app", "condition": "START"}]
    },
    {
      "name": "app", 
      "image": "670704545755.dkr.ecr.ap-northeast-1.amazonaws.com/mobile-order/app:latest",
      "essential": true,
      "secrets": [
        {
          "name": "APP_KEY",
          "valueFrom": "arn:aws:secretsmanager:region:account:secret:mobile-order/laravel-app-key-L2wuWR:APP_KEY::"
        }
      ]
    }
  ]
}
```

**重要な設計ポイント:**

1. **Multi-Container Task**: NginxとPHP-FPMを分離してロール分担
2. **Container Dependencies**: NginxはPHP-FPMの起動を待つ
3. **Secrets Integration**: 環境変数として機密情報を注入
4. **Logging**: 各コンテナのログをCloudWatch Logsに集約

#### 2.2 ECSサービス設計

```mermaid
graph TB
    subgraph "ECS Service Configuration"
        Service[⚙️ mobile-order-web-service]
        ServiceConfig[📋 Service Configuration<br/>Desired Count: 2<br/>Min Healthy: 100%<br/>Max Percent: 200%]
        
        subgraph "デプロイメント戦略"
            Rolling[🔄 Rolling Deployment<br/>Zero Downtime]
        end
        
        subgraph "ヘルスチェック"
            ALBHealth[🩺 ALB Health Check<br/>/healthcheck.php<br/>Interval: 30s<br/>Timeout: 5s<br/>Healthy: 2<br/>Unhealthy: 2]
        end
        
        subgraph "Auto Scaling"
            ASG[📈 Application Auto Scaling<br/>Min: 2, Max: 10<br/>Target CPU: 70%<br/>Target Memory: 80%]
        end
    end
    
    Service --> ServiceConfig
    ServiceConfig --> Rolling
    Rolling --> ALBHealth
    ALBHealth --> ASG
```

### 3. データ層アーキテクチャ

#### 3.1 ElastiCache Redis設計

```mermaid
graph TB
    subgraph "ElastiCache Redis Cluster"
        RedisCluster[🔴 mobile-order-redis<br/>Engine: Redis 7.x<br/>Node: cache.t3.micro<br/>Cluster Mode: Disabled]
        
        subgraph "接続情報"
            Endpoint[📍 Primary Endpoint<br/>mobile-order-redis.ts4bse.0001.apne1.cache.amazonaws.com:6379]
        end
        
        subgraph "用途"
            SessionStore[🔐 セッションストア<br/>Laravel Sessions]
            Cache[⚡ アプリケーションキャッシュ<br/>Query Results, Views]
            Queue[📤 ジョブキュー<br/>Background Tasks]
        end
        
        subgraph "セキュリティ"
            VPCOnly[🔒 VPCアクセスのみ<br/>Internet Gateway経由不可]
            EncryptTransit[🔐 転送時暗号化<br/>TLS 1.2]
        end
    end
    
    RedisCluster --> Endpoint
    Endpoint --> SessionStore
    Endpoint --> Cache
    Endpoint --> Queue
    VPCOnly --> EncryptTransit
```

**Redis使用用途詳細:**

1. **セッション管理**: 
   - Laravel標準のセッションストレージ
   - 複数タスク間でのセッション共有
   - TTL: 30分（自動延長機能）

2. **アプリケーションキャッシュ**:
   - データベースクエリ結果のキャッシュ
   - Bladeテンプレートのコンパイル済みキャッシュ
   - APIレスポンスキャッシュ

3. **背景タスクキュー**:
   - 注文処理の非同期実行
   - メール送信キュー
   - データ同期処理

#### 3.2 RDS MySQL設計

```mermaid
graph TB
    subgraph "RDS MySQL Configuration"
        RDS[🗄️ mobile-order-mysql<br/>Engine: MySQL 8.0<br/>Class: db.t3.medium<br/>Storage: 100GB GP2]
        
        subgraph "高可用性設計"
            MultiAZ[🔄 Multi-AZ Deployment<br/>Primary: AZ-A<br/>Standby: AZ-C<br/>Automatic Failover]
        end
        
        subgraph "バックアップ戦略"
            AutoBackup[💾 自動バックアップ<br/>保持期間: 7日<br/>バックアップウィンドウ: 3:00-4:00 JST]
            Snapshot[📸 手動スナップショット<br/>重要更新前に取得]
        end
        
        subgraph "セキュリティ"
            Encryption[🔒 保存時暗号化<br/>AWS KMS]
            VPCPrivate[🔒 プライベートサブネットのみ]
            SecretRotation[🔄 認証情報ローテーション<br/>90日自動]
        end
    end
    
    RDS --> MultiAZ
    MultiAZ --> AutoBackup
    AutoBackup --> Snapshot
    Snapshot --> Encryption
    Encryption --> VPCPrivate
    VPCPrivate --> SecretRotation
```

### 4. セキュリティ・機密管理層

#### 4.1 AWS Secrets Manager統合

```mermaid
graph TB
    subgraph "Secrets Manager"
        subgraph "機密情報種別"
            AppKey[🔑 Laravel App Key<br/>mobile-order/laravel-app-key]
            DBCreds[🗄️ Database Credentials<br/>mobile-order/database]
            RedisCreds[🔴 Redis Configuration<br/>mobile-order/redis]
        end
        
        subgraph "セキュリティ機能"
            Encryption[🔐 KMS暗号化<br/>AWS-managed key]
            Rotation[🔄 自動ローテーション<br/>Database: 90日<br/>App Key: 年1回]
            Audit[📝 アクセスログ<br/>CloudTrail連携]
        end
        
        subgraph "アクセス制御"
            IAMPolicy[👤 IAM Policy<br/>ECS実行ロールのみアクセス]
            VPCEndpoint[🔗 VPC Endpoint<br/>プライベート通信]
        end
    end
    
    subgraph "ECS Task"
        EnvVars[🌐 Environment Variables<br/>実行時注入]
    end
    
    AppKey --> EnvVars
    DBCreds --> EnvVars  
    RedisCreds --> EnvVars
    Encryption --> Rotation
    Rotation --> Audit
    Audit --> IAMPolicy
    IAMPolicy --> VPCEndpoint
```

**機密情報の注入フロー:**

1. **ECSタスク起動時**:
   - ECS実行ロールがSecrets Managerにアクセス
   - VPC Endpoint経由でプライベート通信
   - 取得した値を環境変数として設定

2. **Laravel起動時**:
   - `config:clear`により設定キャッシュを無効化
   - 実行時に環境変数から設定値を読み込み
   - Redis/MySQLへの接続に使用

### 5. 監視・ログ・運用

#### 5.1 CloudWatch Logs統合

```mermaid
graph TB
    subgraph "ログ集約システム"
        subgraph "ログソース"
            NginxLogs[🌐 Nginx Access/Error Logs]
            PHPLogs[🐘 PHP-FPM Logs] 
            LaravelLogs[⚙️ Laravel Application Logs]
        end
        
        subgraph "CloudWatch Logs"
            LogGroup[📊 /ecs/mobile-order/web]
            subgraph "Log Streams"
                NginxStream[📝 nginx/nginx/[task-id]]
                PHPStream[📝 php-fpm/php-fpm/[task-id]]
            end
        end
        
        subgraph "ログ分析"
            Insights[🔍 CloudWatch Insights<br/>SQL-like Queries]
            Alarms[🚨 CloudWatch Alarms<br/>Error Rate > 5%]
            Dashboard[📈 CloudWatch Dashboard<br/>Real-time Metrics]
        end
    end
    
    NginxLogs --> NginxStream
    PHPLogs --> PHPStream
    LaravelLogs --> PHPStream
    LogGroup --> Insights
    Insights --> Alarms
    Alarms --> Dashboard
```

#### 5.2 監視メトリクス

**アプリケーションレベル:**
- レスポンス時間 (p50, p95, p99)
- エラー率 (HTTP 5xx)
- スループット (RPS)
- アクティブセッション数

**インフラレベル:**
- CPU使用率 (ECS Tasks)
- メモリ使用率 (ECS Tasks)  
- Redis接続数/応答時間
- MySQL接続数/スロークエリ

**ビジネスメトリクス:**
- 注文完了率
- カート放棄率
- 平均注文金額

## トラフィックフローの詳細

### 1. 通常のWebリクエスト

```mermaid
sequenceDiagram
    participant User as 👤 ユーザー
    participant DNS as 🌐 Route 53
    participant ALB as ⚖️ Application Load Balancer
    participant Nginx as 🌐 Nginx Container
    participant PHP as 🐘 PHP-FPM Container
    participant Redis as 🔴 Redis
    participant MySQL as 🗄️ MySQL
    participant Secrets as 🔐 Secrets Manager

    User->>DNS: mobile-order.example.com
    DNS->>User: ALB IP Address
    User->>ALB: HTTPS Request
    
    ALB->>Nginx: HTTP Request (Port 80)
    Nginx->>PHP: FastCGI Request (Port 9000)
    
    Note over PHP: Laravel Application Processing
    
    PHP->>Secrets: Get DB/Redis Credentials
    Secrets->>PHP: Return Encrypted Values
    
    PHP->>Redis: Session/Cache Check
    Redis->>PHP: Cached Data (if exists)
    
    alt Cache Miss
        PHP->>MySQL: Database Query
        MySQL->>PHP: Query Results
        PHP->>Redis: Cache Results
    end
    
    PHP->>Nginx: FastCGI Response
    Nginx->>ALB: HTTP Response
    ALB->>User: HTTPS Response
```

### 2. POS連携APIリクエスト

```mermaid
sequenceDiagram
    participant POS as 🖥️ POS端末
    participant ALB as ⚖️ ALB
    participant PHP as 🐘 Laravel API
    participant MySQL as 🗄️ MySQL
    participant Redis as 🔴 Redis

    POS->>ALB: POST /api/pos/orders (Bearer Token)
    ALB->>PHP: Forward Request
    
    Note over PHP: Laravel Sanctum Authentication
    
    PHP->>MySQL: Validate API Token
    MySQL->>PHP: Token Valid
    
    PHP->>MySQL: Insert Order Data
    MySQL->>PHP: Order Created
    
    PHP->>Redis: Update Change Log
    Redis->>PHP: Log Updated
    
    PHP->>ALB: JSON Response (Order ID)
    ALB->>POS: API Response
```

## デプロイメント戦略

### 1. Blue-Green デプロイメント

```mermaid
graph TB
    subgraph "Blue-Green Deployment Process"
        subgraph "Current (Blue)"
            BlueTask1[📦 Blue Task 1<br/>Version: v1.0]
            BlueTask2[📦 Blue Task 2<br/>Version: v1.0]
        end
        
        subgraph "New (Green)"
            GreenTask1[📦 Green Task 1<br/>Version: v1.1]
            GreenTask2[📦 Green Task 2<br/>Version: v1.1]
        end
        
        ALB_BG[⚖️ ALB Target Group]
        
        subgraph "切り替えプロセス"
            Health[🩺 Health Check<br/>Green Tasks]
            Switch[🔄 Traffic Switch<br/>100% to Green]
            Terminate[🗑️ Terminate<br/>Blue Tasks]
        end
    end
    
    ALB_BG --> BlueTask1
    ALB_BG --> BlueTask2
    Health --> GreenTask1
    Health --> GreenTask2
    Switch --> ALB_BG
    Switch --> GreenTask1
    Switch --> GreenTask2
    Terminate --> BlueTask1
    Terminate --> BlueTask2
```

### 2. ローリングデプロイメント設定

```yaml
Service Configuration:
  DeploymentConfiguration:
    MaximumPercent: 200        # 最大タスク数を2倍まで許可
    MinimumHealthyPercent: 100 # 常に100%のタスクを健全に保つ
  
  HealthCheckGracePeriod: 60   # ヘルスチェック猶予時間
  
  DeploymentCircuitBreaker:    # 失敗時の自動ロールバック
    Enable: true
    Rollback: true
```

## 災害復旧・事業継続性

### 1. データバックアップ戦略

```mermaid
graph TB
    subgraph "バックアップ戦略"
        subgraph "データベース"
            AutoBackup[🔄 自動バックアップ<br/>毎日 3:00 JST<br/>保持期間: 7日]
            ManualSnapshot[📸 手動スナップショット<br/>重要更新前]
            CrossRegion[🌏 クロスリージョン<br/>us-west-2にコピー]
        end
        
        subgraph "アプリケーションコード"
            Git[📚 Git Repository<br/>GitHub/GitLab]
            ECR_Backup[📦 ECR Images<br/>複数タグ保持]
        end
        
        subgraph "設定情報"
            Secrets_Backup[🔐 Secrets Manager<br/>自動レプリケーション]
            IaC[🏗️ Infrastructure as Code<br/>CloudFormation/Terraform]
        end
    end
    
    AutoBackup --> CrossRegion
    ManualSnapshot --> CrossRegion
    Git --> ECR_Backup
    Secrets_Backup --> IaC
```

### 2. 障害時の復旧手順

1. **サービス監視**:
   - CloudWatch Alarms
   - ALB ヘルスチェック失敗
   - 応答時間劣化

2. **自動復旧**:
   - ECSサービスによる不健全タスクの自動再起動
   - Auto Scalingによる負荷分散
   - Multi-AZ RDSの自動フェイルオーバー

3. **手動介入**:
   - ログ分析による根本原因調査
   - 必要に応じてロールバック実行
   - データベーススナップショットからの復旧

## パフォーマンス最適化

### 1. アプリケーション層最適化

```mermaid
graph TB
    subgraph "パフォーマンス最適化"
        subgraph "PHP最適化"
            OPcache[⚡ OPcache<br/>Memory: 128MB<br/>Max Files: 10000]
            PHPConfig[⚙️ PHP設定<br/>memory_limit: 256M<br/>max_execution_time: 30s]
        end
        
        subgraph "Laravel最適化"
            ConfigCache[📄 Config Cache<br/>本番環境で有効化]
            RouteCache[🛣️ Route Cache<br/>高速ルーティング]
            ViewCache[👁️ View Cache<br/>Blade テンプレート]
        end
        
        subgraph "データベース最適化"
            QueryCache[💾 Query Result Cache<br/>Redis 30分TTL]
            IndexOptimize[🔍 Index最適化<br/>Slow Query解析]
        end
        
        subgraph "CDN・キャッシュ"
            CloudFront[🌐 CloudFront<br/>静的アセット配信]
            EdgeCache[⚡ Edge Cache<br/>画像・CSS・JS]
        end
    end
    
    OPcache --> PHPConfig
    ConfigCache --> RouteCache
    RouteCache --> ViewCache
    QueryCache --> IndexOptimize
    CloudFront --> EdgeCache
```

このアーキテクチャにより、Mobile Order Systemは高い可用性、セキュリティ、パフォーマンスを実現し、将来的なスケール需要にも対応可能な設計となっています。