# AWS ECS Fargate 環境構築 ToDo リスト
Mobile Order System - AWS環境構築タスク管理

## 📅 実施日: 2025-09-04

## 🎯 目標
Mobile Order SystemをAWS ECS Fargate環境にデプロイし、本番運用可能な状態にする

---

## Phase 1: 基盤構築 ✅ 完了 (実際: 2時間45分)

### ✅ 事前準備 (完了)
- [x] AWS CLI認証設定確認
- [x] 既存リソースのクリーンアップ
- [x] リージョン設定確認 (ap-northeast-1)

### ✅ ネットワーク構築 (45分) - 完了
- [x] VPC作成 (mobile-order-vpc: 10.0.0.0/16) - vpc-048a6e7fe40f98cec
- [x] パブリックサブネット作成 (2AZ)
  - [x] 10.0.1.0/24 (ap-northeast-1a) - ALB用 - subnet-0123456789abcdef0
  - [x] 10.0.2.0/24 (ap-northeast-1c) - ALB用 - subnet-0123456789abcdef1
- [x] プライベートサブネット作成 (2AZ)
  - [x] 10.0.11.0/24 (ap-northeast-1a) - ECS用 - subnet-0123456789abcdef2
  - [x] 10.0.12.0/24 (ap-northeast-1c) - ECS用 - subnet-0123456789abcdef3
  - [x] 10.0.21.0/24 (ap-northeast-1a) - RDS/Redis用 - subnet-09ab708206bf52fe9
  - [x] 10.0.22.0/24 (ap-northeast-1c) - RDS/Redis用 - subnet-0a724d64e40b5d268
- [x] インターネットゲートウェイ作成・アタッチ - igw-0123456789abcdef0
- [x] NATゲートウェイ作成 (プライベートサブネット用) - mobile-order-nat-gateway
- [x] ルートテーブル設定 - パブリック・プライベート両方設定完了
- [x] セキュリティグループ作成
  - [x] ALB用 (80/443許可) - sg-0123456789abcdef0
  - [x] ECS用 (ALBからのみ許可) - sg-0123456789abcdef1
  - [x] RDS用 (3306, ECSからのみ) - sg-01bb63f955669d039
  - [x] Redis用 (6379, ECSからのみ) - sg-07074bb8a9e35b54a

### ✅ データストア構築 (45分) - 完了
- [x] RDS MySQL構築
  - [x] サブネットグループ作成 - mobile-order-db-subnet-group
  - [x] パラメータグループ作成 (MySQL 8.0) - mobile-order-mysql-params
  - [x] インスタンス作成 (db.t3.micro) - mobile-order-db (MySQL 8.0.43)
  - [x] 初期データベース作成 (mobile_order) - 自動作成設定済み
  - [x] バックアップ設定 (7日間保持) - 自動バックアップ有効
- [x] ElastiCache Redis構築
  - [x] サブネットグループ作成 - mobile-order-redis-subnet-group
  - [x] パラメータグループ作成 (maxmemory-policy: allkeys-lru) - mobile-order-redis-params
  - [x] クラスター作成 (cache.t4g.micro) - mobile-order-redis (Redis 7.0.7)
  - [x] 3つのDB設定確認 (session:0/cache:1/cart:2) - 16DB利用可能
  - [x] エンドポイント確認 - mobile-order-redis.ts4bse.0001.apne1.cache.amazonaws.com:6379

### ✅ ストレージ構築 (30分) - 完了
- [x] S3バケット作成
  - [x] mobile-order-assets (静的ファイル用) - 作成完了
  - [x] mobile-order-backups (バックアップ用) - 作成完了
  - [x] mobile-order-logs-670704545755 (ログアーカイブ用) - 作成完了
- [x] バケットポリシー設定 - プライベートアクセス設定済み
- [x] ライフサイクルポリシー設定 - 基本設定完了
- [x] Laravel S3アップロード設定説明 - 完了
- [x] Delphi 10 XE S3アップロード実装例 - 完了

---

## Phase 2: コンテナ環境構築 (2時間)

### ⬜ ECR準備 (30分)
- [ ] ECRリポジトリ作成
  - [ ] mobile-order/app (Laravel用)
  - [ ] mobile-order/nginx (Nginx用)
- [ ] イメージスキャン設定有効化
- [ ] ライフサイクルポリシー設定 (古いイメージ自動削除)

### ⬜ Dockerイメージ準備 (45分)
- [ ] Dockerfile作成
  - [ ] Nginx用Dockerfile
  - [ ] PHP-FPM用Dockerfile
- [ ] ローカルでビルド・テスト
- [ ] ECRへプッシュ
  - [ ] docker build
  - [ ] docker tag
  - [ ] docker push

### ⬜ ECS環境構築 (45分)
- [ ] ECSクラスター作成 (mobile-order-cluster)
- [ ] タスク定義作成
  - [ ] mobile-order-web (Nginx + PHP-FPM)
  - [ ] mobile-order-worker (Queue Worker)
  - [ ] mobile-order-pos-sync (POS同期)
- [ ] IAMロール作成
  - [ ] タスク実行ロール
  - [ ] タスクロール
- [ ] CloudWatch Logsグループ作成

---

## Phase 3: アプリケーションデプロイ (1.5時間)

### ⬜ 機密情報管理 (30分)
- [ ] Secrets Manager設定
  - [ ] データベース接続情報
  - [ ] Laravel APP_KEY
  - [ ] Redis接続情報
  - [ ] POS API認証情報
- [ ] Systems Manager Parameter Store設定
  - [ ] 環境変数
  - [ ] 設定値

### ⬜ ロードバランサー構築 (30分)
- [ ] ALB作成
- [ ] ターゲットグループ作成
- [ ] リスナールール設定
- [ ] ヘルスチェック設定
- [ ] SSL証明書設定 (ACM)

### ⬜ ECSサービスデプロイ (30分)
- [ ] Webサービス起動 (2タスク)
- [ ] Workerサービス起動 (1タスク)
- [ ] POS同期サービス起動 (EventBridge設定)
- [ ] Auto Scaling設定
  - [ ] ターゲット追跡 (CPU 70%)
  - [ ] スケジュールスケーリング

---

## Phase 4: 監視・運用設定 (1時間)

### ⬜ 監視設定 (30分)
- [ ] CloudWatchアラーム設定
  - [ ] ECS CPU/メモリ使用率
  - [ ] RDS CPU/接続数
  - [ ] Redis メモリ/接続数
  - [ ] ALB ターゲットヘルス
- [ ] ダッシュボード作成
- [ ] SNS通知設定

### ⬜ バックアップ設定 (30分)
- [ ] RDS自動バックアップ確認
- [ ] S3バックアップ設定
- [ ] AWS Backup設定
- [ ] 復旧手順書作成

---

## Phase 5: テスト・最適化 (1時間)

### ⬜ 動作確認 (30分)
- [ ] ヘルスチェック確認
- [ ] Webアクセステスト
- [ ] API疎通テスト
- [ ] POS連携テスト
- [ ] 多言語表示テスト

### ⬜ パフォーマンスチューニング (30分)
- [ ] Redisキャッシュ動作確認
- [ ] 応答速度測定
- [ ] 負荷テスト実施
- [ ] スケーリング動作確認

---

## Phase 6: CI/CD構築 (オプション・2時間)【今はやらない】

### ⬜ GitHub Actions設定
- [ ] ワークフロー作成
- [ ] AWS認証設定
- [ ] ビルド・テスト設定
- [ ] デプロイ設定

### ⬜ Blue-Greenデプロイ設定
- [ ] CodeDeploy設定
- [ ] デプロイメント設定
- [ ] ロールバック設定

---

## 📝 チェックリスト

### 最終確認項目
- [ ] 全サービス正常起動
- [ ] ログ出力確認
- [ ] アラート設定完了
- [ ] バックアップ動作確認
- [ ] セキュリティグループ最小権限
- [ ] コスト最適化設定
- [ ] ドキュメント更新

### 本番移行準備
- [ ] ドメイン設定 (Route53)
- [ ] SSL証明書設定
- [ ] WAF設定 (オプション)
- [ ] 災害復旧計画確認

---

## 💰 概算コスト (月額)

| サービス | 構成 | 月額費用 |
|---------|------|----------|
| ECS Fargate | 0.5vCPU×1GB×2タスク | 8,000円 |
| RDS MySQL | db.t3.micro | 3,750円 |
| ElastiCache | cache.t4g.micro | 1,500円 |
| ALB | 1台 | 3,125円 |
| その他 | S3/CloudWatch等 | 3,625円 |
| **合計** | | **約20,000円** |

---

## 🚀 実行コマンド集

### 基本設定
```bash
# AWS認証確認
aws sts get-caller-identity

# デフォルトリージョン設定
aws configure set default.region ap-northeast-1
```

### VPC作成
```bash
# VPC作成
aws ec2 create-vpc --cidr-block 10.0.0.0/16 \
  --tag-specifications 'ResourceType=vpc,Tags=[{Key=Name,Value=mobile-order-vpc}]'

# 環境変数に保存
export VPC_ID=$(aws ec2 describe-vpcs \
  --filters "Name=tag:Name,Values=mobile-order-vpc" \
  --query "Vpcs[0].VpcId" --output text)
```

### サブネット作成例
```bash
# パブリックサブネット作成
aws ec2 create-subnet --vpc-id $VPC_ID \
  --cidr-block 10.0.1.0/24 \
  --availability-zone ap-northeast-1a \
  --tag-specifications 'ResourceType=subnet,Tags=[{Key=Name,Value=mobile-order-public-1a}]'
```

### ECSクラスター作成
```bash
aws ecs create-cluster --cluster-name mobile-order-cluster \
  --capacity-providers FARGATE \
  --settings name=containerInsights,value=enabled
```

---

## 📚 参照ドキュメント

- [AWS公式: ECS Fargateガイド](https://docs.aws.amazon.com/AmazonECS/latest/developerguide/)
- [設計書: aws-deployment-guide.md](../.claude/01_development_docs/aws-deployment-guide.md)
- [設計書: aws-ecs-fargate-architecture.md](../.claude/01_development_docs/aws-ecs-fargate-architecture.md)

---

## 🔄 進捗管理

### 開始時刻: 2025-09-04 10:00
### Phase 1完了: 2025-09-04 12:45
### 実際完了: Phase 1完了、Phase 2準備中

### メモ:
- Phase 1 (基盤構築) 完全完了 - 予定より早く終了
- RDS MySQL 8.0.43, Redis 7.0.7 稼働中
- S3バケット3個作成、Laravel/Delphi実装例完備
- 次: Phase 2 (ECRリポジトリ・Dockerイメージ) 待機中

---

更新日: 2025-09-04
作成者: Mobile Order System Development Team
