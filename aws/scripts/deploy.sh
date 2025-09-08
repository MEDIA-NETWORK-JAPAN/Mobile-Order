#!/bin/bash

# ================================================
# Mobile Order System - AWS Secure Deployment Script
# ベストプラクティスに従った完全自動化デプロイメント
# ================================================

set -euo pipefail

# Configuration
REGION="ap-northeast-1"
ACCOUNT_ID="670704545755"
PROJECT_NAME="mobile-order"
ENVIRONMENT="production"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warn() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

# Pre-flight checks
check_dependencies() {
    log "Performing pre-flight checks..."
    
    command -v aws >/dev/null 2>&1 || error "AWS CLI not found"
    command -v docker >/dev/null 2>&1 || error "Docker not found"
    
    # Check AWS credentials
    aws sts get-caller-identity >/dev/null 2>&1 || error "AWS credentials not configured"
    
    success "Pre-flight checks passed"
}

# Step 1: Create IAM roles and policies
create_iam_resources() {
    log "Creating IAM roles and policies..."
    
    # ECS Execution Role
    if ! aws iam get-role --role-name mobile-order-ecs-execution-role >/dev/null 2>&1; then
        aws iam create-role \
            --role-name mobile-order-ecs-execution-role \
            --assume-role-policy-document '{
                "Version": "2012-10-17",
                "Statement": [
                    {
                        "Effect": "Allow",
                        "Principal": {
                            "Service": "ecs-tasks.amazonaws.com"
                        },
                        "Action": "sts:AssumeRole"
                    }
                ]
            }' \
            --tags Key=Project,Value=mobile-order Key=Environment,Value=production
        
        success "Created ECS execution role"
    else
        warn "ECS execution role already exists"
    fi
    
    # Attach execution role policy
    aws iam put-role-policy \
        --role-name mobile-order-ecs-execution-role \
        --policy-name MobileOrderECSExecutionPolicy \
        --policy-document file://aws/security/iam/ecs-execution-role-policy.json
    
    # ECS Task Role
    if ! aws iam get-role --role-name mobile-order-ecs-task-role >/dev/null 2>&1; then
        aws iam create-role \
            --role-name mobile-order-ecs-task-role \
            --assume-role-policy-document '{
                "Version": "2012-10-17",
                "Statement": [
                    {
                        "Effect": "Allow",
                        "Principal": {
                            "Service": "ecs-tasks.amazonaws.com"
                        },
                        "Action": "sts:AssumeRole"
                    }
                ]
            }' \
            --tags Key=Project,Value=mobile-order Key=Environment,Value=production
        
        success "Created ECS task role"
    else
        warn "ECS task role already exists"
    fi
    
    # Attach task role policy
    aws iam put-role-policy \
        --role-name mobile-order-ecs-task-role \
        --policy-name MobileOrderECSTaskPolicy \
        --policy-document file://aws/security/iam/ecs-task-role-policy.json
    
    success "IAM resources configured"
}

# Step 2: Create Secrets Manager secrets
create_secrets() {
    log "Creating Secrets Manager secrets..."
    
    # Generate Laravel App Key
    APP_KEY=$(php artisan key:generate --show 2>/dev/null || echo "base64:$(openssl rand -base64 32)")
    DB_PASSWORD=$(openssl rand -base64 32)
    JWT_SECRET=$(openssl rand -base64 64)
    
    # Create unified secret
    SECRET_VALUE=$(jq -n \
        --arg app_key "$APP_KEY" \
        --arg db_password "$DB_PASSWORD" \
        --arg jwt_secret "$JWT_SECRET" \
        '{
            "APP_KEY": $app_key,
            "DB_HOST": "mobile-order-db.cpymygo4012s.ap-northeast-1.rds.amazonaws.com",
            "DB_DATABASE": "mobile_order",
            "DB_USERNAME": "mobile_order_user",
            "DB_PASSWORD": $db_password,
            "REDIS_HOST": "mobile-order-redis.ts4bse.0001.apne1.cache.amazonaws.com",
            "REDIS_PORT": "6379",
            "REDIS_PASSWORD": "",
            "JWT_SECRET": $jwt_secret
        }')
    
    if ! aws secretsmanager describe-secret --secret-id "mobile-order/app-secrets" >/dev/null 2>&1; then
        aws secretsmanager create-secret \
            --name "mobile-order/app-secrets" \
            --description "Mobile Order System - All application secrets" \
            --secret-string "$SECRET_VALUE" \
            --tags '[
                {"Key": "Project", "Value": "mobile-order"},
                {"Key": "Environment", "Value": "production"},
                {"Key": "Component", "Value": "application-secrets"}
            ]'
        
        success "Created application secrets"
    else
        warn "Application secrets already exist"
    fi
}

# Step 3: Create ECR repositories
create_ecr_repositories() {
    log "Creating ECR repositories..."
    
    for repo in "mobile-order/app" "mobile-order/nginx"; do
        if ! aws ecr describe-repositories --repository-names "$repo" >/dev/null 2>&1; then
            aws ecr create-repository \
                --repository-name "$repo" \
                --region "$REGION" \
                --tags '[
                    {"Key": "Project", "Value": "mobile-order"},
                    {"Key": "Environment", "Value": "production"}
                ]'
            
            success "Created ECR repository: $repo"
        else
            warn "ECR repository already exists: $repo"
        fi
    done
}

# Step 4: Build and push Docker images
build_and_push_images() {
    log "Building and pushing Docker images..."
    
    # ECR login
    aws ecr get-login-password --region "$REGION" | \
        docker login --username AWS --password-stdin "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com"
    
    # Build and push app image
    log "Building application image..."
    docker build -f aws/application/containers/app.Dockerfile \
        -t mobile-order-app:latest \
        -t "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/app:latest" \
        -t "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/app:$(date +%Y%m%d-%H%M%S)" \
        .
    
    docker push "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/app:latest"
    docker push "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/app:$(date +%Y%m%d-%H%M%S)"
    
    success "Pushed application image"
    
    # Build and push nginx image
    log "Building nginx image..."
    docker build -f aws/application/containers/nginx.Dockerfile \
        -t mobile-order-nginx:latest \
        -t "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/nginx:latest" \
        -t "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/nginx:$(date +%Y%m%d-%H%M%S)" \
        .
    
    docker push "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/nginx:latest"
    docker push "${ACCOUNT_ID}.dkr.ecr.${REGION}.amazonaws.com/mobile-order/nginx:$(date +%Y%m%d-%H%M%S)"
    
    success "Pushed nginx image"
}

# Step 5: Create ECS resources
create_ecs_resources() {
    log "Creating ECS cluster and services..."
    
    # Create ECS cluster
    if ! aws ecs describe-clusters --clusters mobile-order-cluster >/dev/null 2>&1; then
        aws ecs create-cluster \
            --cluster-name mobile-order-cluster \
            --capacity-providers FARGATE \
            --default-capacity-provider-strategy capacityProvider=FARGATE,weight=1 \
            --tags key=Project,value=mobile-order key=Environment,value=production
        
        success "Created ECS cluster"
    else
        warn "ECS cluster already exists"
    fi
    
    # Register task definition
    aws ecs register-task-definition \
        --cli-input-json file://aws/application/services/task-definition.json
    
    success "Registered task definition"
}

# Step 6: Create ElastiCache Redis
create_elasticache() {
    log "Creating ElastiCache Redis cluster..."
    
    # Create subnet group if not exists
    if ! aws elasticache describe-cache-subnet-groups --cache-subnet-group-name mobile-order-redis-subnet-group >/dev/null 2>&1; then
        aws elasticache create-cache-subnet-group \
            --cache-subnet-group-name mobile-order-redis-subnet-group \
            --cache-subnet-group-description "Mobile Order Redis subnet group" \
            --subnet-ids subnet-0f7bfa77c611ae4c4 subnet-0d61fdd3479dfc3dd
    fi
    
    # Create Redis cluster
    if ! aws elasticache describe-cache-clusters --cache-cluster-id mobile-order-redis >/dev/null 2>&1; then
        aws elasticache create-cache-cluster \
            --cache-cluster-id mobile-order-redis \
            --engine redis \
            --cache-node-type cache.t4g.micro \
            --num-cache-nodes 1 \
            --cache-subnet-group-name mobile-order-redis-subnet-group \
            --security-group-ids sg-0e8d5c7080ea81a0c \
            --tags Key=Project,Value=mobile-order Key=Environment,Value=production
        
        success "Created ElastiCache Redis cluster"
    else
        warn "ElastiCache Redis cluster already exists"
    fi
}

# Step 7: Create Application Load Balancer
create_alb() {
    log "Creating Application Load Balancer..."
    
    # Create ALB
    if ! aws elbv2 describe-load-balancers --names mobile-order-alb >/dev/null 2>&1; then
        ALB_ARN=$(aws elbv2 create-load-balancer \
            --name mobile-order-alb \
            --subnets subnet-0f7bfa77c611ae4c4 subnet-0d61fdd3479dfc3dd \
            --security-groups sg-02d611335aaca752d \
            --scheme internet-facing \
            --type application \
            --ip-address-type ipv4 \
            --tags Key=Project,Value=mobile-order Key=Environment,Value=production \
            --query 'LoadBalancers[0].LoadBalancerArn' --output text)
        
        success "Created Application Load Balancer"
    else
        ALB_ARN=$(aws elbv2 describe-load-balancers --names mobile-order-alb \
            --query 'LoadBalancers[0].LoadBalancerArn' --output text)
        warn "ALB already exists"
    fi
    
    # Create target group
    if ! aws elbv2 describe-target-groups --names mobile-order-web-tg >/dev/null 2>&1; then
        TG_ARN=$(aws elbv2 create-target-group \
            --name mobile-order-web-tg \
            --protocol HTTP \
            --port 8080 \
            --vpc-id vpc-0123456789abcdef0 \
            --health-check-path /healthcheck.php?simple=1 \
            --health-check-interval-seconds 30 \
            --health-check-timeout-seconds 5 \
            --healthy-threshold-count 2 \
            --unhealthy-threshold-count 3 \
            --target-type ip \
            --tags Key=Project,Value=mobile-order Key=Environment,Value=production \
            --query 'TargetGroups[0].TargetGroupArn' --output text)
        
        success "Created target group"
    else
        TG_ARN=$(aws elbv2 describe-target-groups --names mobile-order-web-tg \
            --query 'TargetGroups[0].TargetGroupArn' --output text)
        warn "Target group already exists"
    fi
    
    # Create listener
    if ! aws elbv2 describe-listeners --load-balancer-arn "$ALB_ARN" >/dev/null 2>&1; then
        aws elbv2 create-listener \
            --load-balancer-arn "$ALB_ARN" \
            --protocol HTTP \
            --port 80 \
            --default-actions Type=forward,TargetGroupArn="$TG_ARN"
        
        success "Created ALB listener"
    else
        warn "ALB listener already exists"
    fi
}

# Step 8: Create ECS Service
create_ecs_service() {
    log "Creating ECS service..."
    
    if ! aws ecs describe-services --cluster mobile-order-cluster --services mobile-order-web-service >/dev/null 2>&1; then
        aws ecs create-service \
            --cluster mobile-order-cluster \
            --service-name mobile-order-web-service \
            --task-definition mobile-order-web-secure:1 \
            --desired-count 2 \
            --launch-type FARGATE \
            --network-configuration "awsvpcConfiguration={subnets=[subnet-0f7bfa77c611ae4c4,subnet-0d61fdd3479dfc3dd],securityGroups=[sg-07144d06e5a816f3b],assignPublicIp=DISABLED}" \
            --load-balancers targetGroupArn="$TG_ARN",containerName=nginx,containerPort=8080 \
            --health-check-grace-period-seconds 300 \
            --tags key=Project,value=mobile-order key=Environment,value=production
        
        success "Created ECS service"
    else
        warn "ECS service already exists"
    fi
}

# Main execution
main() {
    log "Starting Mobile Order System secure deployment..."
    
    check_dependencies
    create_iam_resources
    create_secrets
    create_ecr_repositories
    build_and_push_images
    create_elasticache
    create_ecs_resources
    create_alb
    create_ecs_service
    
    success "Deployment completed successfully!"
    log "ALB Endpoint: $(aws elbv2 describe-load-balancers --names mobile-order-alb --query 'LoadBalancers[0].DNSName' --output text)"
    log "Monitor deployment: aws ecs describe-services --cluster mobile-order-cluster --services mobile-order-web-service"
}

# Check if script is run with --dry-run
if [[ "${1:-}" == "--dry-run" ]]; then
    log "DRY RUN MODE - No resources will be created"
    exit 0
fi

# Run main function
main "$@"