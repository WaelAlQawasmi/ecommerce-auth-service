#!/bin/bash
set -e

echo "========================================="
echo "Ecommerce Auth Service - Production Startup"
echo "========================================="

test -x "$(command -v docker)" >/dev/null 2>&1 || {
  echo "ERROR: Docker is not installed. Install Docker and try again."
  exit 1
}

test -x "$(command -v docker-compose)" >/dev/null 2>&1 || {
  echo "ERROR: Docker Compose is not installed. Install Docker Compose and try again."
  exit 1
}

echo "✓ Docker and Docker Compose are installed"

echo ""
if [ ! -f .env ]; then
  if [ -f .env.docker ]; then
    echo "Creating .env from .env.docker"
    cp .env.docker .env
    echo "✓ .env created"
  else
    echo "ERROR: .env.docker file not found. Create a .env file manually."
    exit 1
  fi
else
  echo "✓ .env already exists"
fi

echo ""
echo "Building and starting Docker containers..."

docker-compose up -d --build

echo ""
echo "Waiting for containers to stabilise..."
sleep 10

echo "Checking service health..."
if docker-compose ps | grep -E "Exit|unhealthy" >/dev/null 2>&1; then
  echo "WARNING: one or more services are not healthy."
  docker-compose ps
  echo "Recent logs from services:"
  docker-compose logs --tail=50
else
  echo "✓ All services appear to be running"
fi

echo ""
echo "Application is available at: http://localhost"
echo "Run migrations with: docker-compose exec app php artisan migrate --force"
echo "Run seeders with: docker-compose exec app php artisan db:seed"
echo ""
