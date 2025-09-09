#!/bin/bash

# AWS Mobile Order環境完全削除スクリプト
echo "🧹 AWS Mobile Order環境の完全削除を開始..."

# 1. RDS削除完了を待機
echo "⏳ RDS削除完了を待機中..."
aws rds wait db-instance-deleted --db-instance-identifier mobile-order-db
echo "✅ RDS削除完了"

# 2. DB関連リソース削除
echo "📦 DB関連リソース削除中..."
aws rds delete-db-parameter-group --db-parameter-group-name mobile-order-mysql8-params 2>/dev/null || echo "DB parameter group already deleted"
aws rds delete-db-subnet-group --db-subnet-group-name mobile-order-db-subnet-group 2>/dev/null || echo "DB subnet group already deleted"

# 3. セキュリティグループ削除
echo "🔒 セキュリティグループ削除中..."
for sg in sg-01bb63f955669d039 sg-02d611335aaca752d sg-07144d06e5a816f3b; do
    aws ec2 delete-security-group --group-id $sg 2>/dev/null || echo "Security group $sg already deleted or has dependencies"
done

# 4. サブネット削除
echo "🌐 サブネット削除中..."
for subnet in subnet-09ab708206bf52fe9 subnet-0a724d64e40b5d268; do
    aws ec2 delete-subnet --subnet-id $subnet 2>/dev/null || echo "Subnet $subnet already deleted"
done

# 5. インターネットゲートウェイデタッチ・削除
echo "🌍 インターネットゲートウェイ処理中..."
IGW_ID=$(aws ec2 describe-internet-gateways --filters "Name=attachment.vpc-id,Values=vpc-097d456b6d91e90e7" --query 'InternetGateways[0].InternetGatewayId' --output text)
if [ "$IGW_ID" != "None" ] && [ "$IGW_ID" != "" ]; then
    aws ec2 detach-internet-gateway --internet-gateway-id $IGW_ID --vpc-id vpc-097d456b6d91e90e7
    aws ec2 delete-internet-gateway --internet-gateway-id $IGW_ID
    echo "✅ インターネットゲートウェイ削除完了"
fi

# 6. ルートテーブル削除（デフォルト以外）
echo "🛣️ ルートテーブル削除中..."
ROUTE_TABLES=$(aws ec2 describe-route-tables --filters "Name=vpc-id,Values=vpc-097d456b6d91e90e7" --query 'RouteTables[?Associations[0].Main!=`true`].RouteTableId' --output text)
for rt in $ROUTE_TABLES; do
    aws ec2 delete-route-table --route-table-id $rt 2>/dev/null || echo "Route table $rt already deleted"
done

# 7. VPC削除
echo "🏗️ VPC削除中..."
aws ec2 delete-vpc --vpc-id vpc-097d456b6d91e90e7
echo "✅ VPC削除完了"

echo "🎉 AWS Mobile Order環境の完全削除が完了しました！"

# 削除確認
echo "📋 削除結果確認:"
echo "RDS Instances:"
aws rds describe-db-instances --query 'DBInstances[?contains(DBInstanceIdentifier, `mobile-order`)].DBInstanceIdentifier' --output text || echo "No RDS instances found"
echo "VPCs:"
aws ec2 describe-vpcs --filters "Name=tag:Name,Values=*mobile-order*" --query 'Vpcs[].VpcId' --output text || echo "No VPCs found"
echo "IAM Roles:"
aws iam list-roles --query 'Roles[?contains(RoleName, `mobile-order`)].RoleName' --output text || echo "No IAM roles found"