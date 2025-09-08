#!/bin/bash

# Mobile Order System - ECR Build and Push Script
# ECS Fargateへのデプロイ用Docker イメージビルド・プッシュ

set -e

# 設定
AWS_REGION="ap-northeast-1"
AWS_ACCOUNT_ID="670704545755"
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
APP_REPO="mobile-order/app"
NGINX_REPO="mobile-order/nginx"

# タグ生成（Git commit hashまたは日時）
if command -v git &> /dev/null && git rev-parse --git-dir > /dev/null 2>&1; then
    TAG=$(git rev-parse --short HEAD)
else
    TAG=$(date +%Y%m%d-%H%M%S)
fi

echo "🚀 Mobile Order System - Docker Build & Push"
echo "Registry: ${ECR_REGISTRY}"
echo "Tag: ${TAG}"
echo "----------------------------------------"

# ECRログイン
echo "📋 ECR Login..."
aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${ECR_REGISTRY}

# アプリケーションイメージビルド
echo "🏗️  Building Application Image..."
docker build \
    -t ${ECR_REGISTRY}/${APP_REPO}:${TAG} \
    -t ${ECR_REGISTRY}/${APP_REPO}:latest \
    -f docker/app/Dockerfile \
    .

# Nginxイメージビルド
echo "🏗️  Building Nginx Image..."
docker build \
    -t ${ECR_REGISTRY}/${NGINX_REPO}:${TAG} \
    -t ${ECR_REGISTRY}/${NGINX_REPO}:latest \
    -f docker/nginx/Dockerfile \
    .

# イメージプッシュ
echo "📦 Pushing Application Image..."
docker push ${ECR_REGISTRY}/${APP_REPO}:${TAG}
docker push ${ECR_REGISTRY}/${APP_REPO}:latest

echo "📦 Pushing Nginx Image..."
docker push ${ECR_REGISTRY}/${NGINX_REPO}:${TAG}
docker push ${ECR_REGISTRY}/${NGINX_REPO}:latest

# 成功メッセージ
echo "✅ Build and Push Completed!"
echo "----------------------------------------"
echo "Application Image: ${ECR_REGISTRY}/${APP_REPO}:${TAG}"
echo "Nginx Image: ${ECR_REGISTRY}/${NGINX_REPO}:${TAG}"
echo "Ready for ECS Deployment!"

# 後片付け（ローカルイメージ削除、オプション）
if [ "${CLEANUP_LOCAL_IMAGES}" = "true" ]; then
    echo "🧹 Cleaning up local images..."
    docker rmi ${ECR_REGISTRY}/${APP_REPO}:${TAG} || true
    docker rmi ${ECR_REGISTRY}/${NGINX_REPO}:${TAG} || true
    echo "✅ Cleanup completed!"
fi