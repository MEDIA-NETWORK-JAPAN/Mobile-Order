# Mobile Order System - AWS Secure Infrastructure

## 📋 概要

Mobile Order SystemのAWS本番環境を**セキュリティベストプラクティス**に従って構築するためのInfrastructure as Codeです。

## 🏗️ アーキテクチャ

```
Internet
    ↓
Application Load Balancer (ALB)
    ↓
ECS Fargate (Multi-AZ)
├── Nginx Container (Port 8080)
├── PHP-FPM Container (Laravel)
└── Worker Container (Queue)
    ↓
├── ElastiCache Redis (セッション・キャッシュ)
├── RDS MySQL (データベース)
└── Secrets Manager (機密情報)
```

## 🔒 セキュリティ強化点

### 1. **コンテナセキュリティ**
- ✅ 非root実行 (UID/GID: 1000)
- ✅ Multi-stage Docker build
- ✅ 最小限のイメージサイズ
- ✅ セキュリティヘッダー実装

### 2. **機密情報管理**  
- ✅ Secrets Manager統合管理
- ✅ 環境変数の平文削除
- ✅ IAM最小権限原則

### 3. **ネットワークセキュリティ**
- ✅ プライベートサブネット配置
- ✅ セキュリティグループ最適化
- ✅ WAF対応準備

## 🚀 デプロイメント

### クイックスタート
```bash
# 1. 自動デプロイメント実行
./aws/scripts/deploy.sh

# 2. デプロイ状況確認
aws ecs describe-services --cluster mobile-order-cluster --services mobile-order-web-service
```

### 手動デプロイ
```bash
# 1. IAMロール作成
aws iam create-role --cli-input-json file://aws/security/iam/execution-role.json

# 2. シークレット作成  
aws secretsmanager create-secret --cli-input-json file://aws/security/secrets/app-secrets.json

# 3. ECRリポジトリ作成
aws ecr create-repository --repository-name mobile-order/app

# 4. イメージビルド・プッシュ
docker build -f aws/application/containers/app.Dockerfile -t mobile-order-app .
aws ecr get-login-password --region ap-northeast-1 | docker login --username AWS --password-stdin 670704545755.dkr.ecr.ap-northeast-1.amazonaws.com
docker tag mobile-order-app 670704545755.dkr.ecr.ap-northeast-1.amazonaws.com/mobile-order/app:latest
docker push 670704545755.dkr.ecr.ap-northeast-1.amazonaws.com/mobile-order/app:latest

# 5. ECSタスク定義登録
aws ecs register-task-definition --cli-input-json file://aws/application/services/task-definition.json

# 6. ECSサービス作成
aws ecs create-service --cluster mobile-order-cluster --service-name mobile-order-web-service
```

## 📁 ディレクトリ構造

```
aws/
├── infrastructure/          # インフラストラクチャ定義
│   ├── terraform/          # Terraform設定 (将来対応)
│   └── cloudformation/     # CloudFormation templates (将来対応)
├── application/            # アプリケーション設定
│   ├── containers/         # Docker設定
│   │   ├── app.Dockerfile
│   │   ├── nginx.Dockerfile
│   │   └── config/        # 設定ファイル
│   └── services/          # ECS設定
│       └── task-definition.json
├── security/              # セキュリティ設定
│   ├── iam/               # IAMロール・ポリシー
│   └── secrets/           # Secrets Manager
├── scripts/               # 自動化スクリプト
│   └── deploy.sh
└── README.md             # このファイル
```

## 🔧 設定のカスタマイズ

### 1. **リソース設定**
```json
// aws/application/services/task-definition.json
{
  "cpu": "1024",      // CPU: 1 vCPU
  "memory": "2048"    // Memory: 2GB
}
```

### 2. **セキュリティヘッダー**
```nginx
# aws/application/containers/config/security-headers.conf
add_header Content-Security-Policy "default-src 'self'";
add_header X-Frame-Options "DENY";
```

### 3. **PHP最適化**
```ini
; aws/application/containers/config/php.ini
memory_limit = 256M
opcache.enable = 1
```

## 📊 監視・ログ

### CloudWatch Logs
- `/aws/ecs/mobile-order/web` - アプリケーションログ
- アクセスログ、エラーログ、PHP-FPMログを統合

### Health Check エンドポイント
- **Basic**: `/health` (nginx)  
- **Detailed**: `/healthcheck.php` (Laravel)
- **ALB**: `/healthcheck.php?simple=1`

### メトリクス
```bash
# CPU・メモリ使用率確認
aws cloudwatch get-metric-statistics --namespace AWS/ECS \
  --metric-name CPUUtilization \
  --dimensions Name=ServiceName,Value=mobile-order-web-service

# エラー率確認  
aws logs filter-log-events --log-group-name /aws/ecs/mobile-order/web \
  --filter-pattern "ERROR"
```

## 🔄 CI/CD統合

### GitHub Actions例
```yaml
name: Deploy to AWS
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to ECS
        run: ./aws/scripts/deploy.sh
        env:
          AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
          AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
```

## 🆘 トラブルシューティング

### よくある問題

**1. タスク起動失敗**
```bash
# タスク定義確認
aws ecs describe-task-definition --task-definition mobile-order-web-secure

# タスクログ確認  
aws logs get-log-events --log-group-name /aws/ecs/mobile-order/web
```

**2. Health Check失敗**
```bash
# コンテナ内でhealth check実行
docker exec -it mobile-order-app php /var/www/html/public/healthcheck.php
```

**3. Secrets Manager接続エラー**
```bash
# IAMロール権限確認
aws iam get-role-policy --role-name mobile-order-ecs-execution-role --policy-name MobileOrderECSExecutionPolicy
```

## 📈 スケーリング

### Auto Scaling設定
```bash
# Auto Scaling有効化
aws application-autoscaling register-scalable-target \
  --service-namespace ecs \
  --scalable-dimension ecs:service:DesiredCount \
  --resource-id service/mobile-order-cluster/mobile-order-web-service \
  --min-capacity 2 \
  --max-capacity 10
```

## 💰 コスト最適化

- **Fargate Spot**: 開発環境でコスト削減
- **Reserved Capacity**: 本番環境で長期割引
- **CloudWatch Insights**: ログ分析でパフォーマンス最適化

## 🛡️ セキュリティチェックリスト

- [ ] IAM権限最小化
- [ ] Secrets Manager統合  
- [ ] セキュリティヘッダー実装
- [ ] 非rootコンテナ実行
- [ ] VPCプライベートサブネット
- [ ] WAF設定 (推奨)
- [ ] SSL/TLS有効化 (推奨)

## 📞 サポート

問題が発生した場合は、以下を含めてレポートしてください：

1. **エラーメッセージ**
2. **CloudWatch Logs**
3. **ECSタスク状態**
4. **実行したコマンド**

---

**🚀 Production Ready - セキュアで高可用性なMobile Order System**