#!/bin/bash

echo "🧹 環境構築の一時ファイルを削除します"

# 重要: mobile-order-key.pemのバックアップ確認
if [ -f "mobile-order-key.pem" ]; then
    echo "⚠️  重要: mobile-order-key.pem をバックアップしましたか？ [y/N]"
    read -r response
    if [ "$response" != "y" ]; then
        echo "❌ バックアップ後に再実行してください"
        exit 1
    fi
fi

# 一時ファイルの削除
echo "🗑️ 一時ファイルを削除中..."

# AWS関連ファイル
rm -f cleanup-aws-mobile-order.sh
rm -f dns-validation-record.json
rm -f domain-dns-record.json
rm -f ec2-user-data.base64
rm -f ec2-user-data.sh
rm -f mobile-order-key.pem
rm -f build.log
rm -f task-definition-updated.json

# Docker関連（オプション）
echo "Docker関連ファイルも削除しますか？ [y/N]"
read -r docker_response
if [ "$docker_response" = "y" ]; then
    rm -f Dockerfile.app.fixed
    rm -f docker-compose.production.yml
    echo "✅ Docker関連ファイルを削除しました"
fi

echo "✅ クリーンアップ完了！"

# 残っているファイルの確認
echo ""
echo "📋 削除後の状態:"
ls -la | grep -E "\.sh$|\.json$|\.base64$|\.pem$|\.log$" || echo "一時ファイルなし"