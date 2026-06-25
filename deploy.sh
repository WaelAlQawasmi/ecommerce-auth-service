#!/bin/bash

set -e

# Override via environment: AWS_REGION, AWS_ACCOUNT_ID, ENV_FILE, NGINX_PORT
AWS_REGION="${AWS_REGION:-us-east-1}"
AWS_ACCOUNT_ID="${AWS_ACCOUNT_ID:-975049961565}"
ECR_REGISTRY="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
AUTH_IMAGE="${ECR_REGISTRY}/microservices/auth:latest"
NGINX_IMAGE="${ECR_REGISTRY}/microservices/nginx:latest"
ENV_FILE="${ENV_FILE:-/opt/.env}"
NGINX_PORT="${NGINX_PORT:-8080}"

echo "Starting deployment..."

# =========================
# 1. Login to ECR and pull latest images
# =========================
echo "Logging in to ECR (${ECR_REGISTRY})..."
aws ecr get-login-password --region "${AWS_REGION}" | \
  docker login --username AWS --password-stdin "${ECR_REGISTRY}"

echo "Pulling latest images..."
docker pull "${AUTH_IMAGE}"
docker pull "${NGINX_IMAGE}"

# =========================
# 2. Create Docker network (ignore error if exists)
# =========================
echo "Creating network auth-net..."
docker network create auth-net 2>/dev/null || true

# =========================
# 3. Remove old containers
# =========================
echo "Removing old containers..."
docker rm -f app 2>/dev/null || true
docker rm -f auth-nginx 2>/dev/null || true

# =========================
# 4. Run Laravel App (PHP-FPM)
# =========================
echo "Starting Laravel app container..."

docker run -d \
  --name app \
  --network auth-net \
  --env-file "${ENV_FILE}" \
  -e CONTAINER_ROLE=app \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -v auth-storage:/var/www/html/storage \
  -v auth-bootstrap-cache:/var/www/html/bootstrap/cache \
  --restart unless-stopped \
  "${AUTH_IMAGE}"

# =========================
# 5. Run Nginx
# =========================
echo "Starting Nginx container..."

docker run -d \
  --name auth-nginx \
  --network auth-net \
  -p "${NGINX_PORT}:80" \
  -v auth-storage:/var/www/html/storage:ro \
  --restart unless-stopped \
  "${NGINX_IMAGE}"

# =========================
# 6. Done
# =========================
echo "Deployment completed successfully!"
echo "App should be available on port ${NGINX_PORT}"
