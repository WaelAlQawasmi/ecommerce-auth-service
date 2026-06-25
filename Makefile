.PHONY: help build build-production build-nginx push-ecr ecr-login up down logs bash app-bash migrate seed tinker test ps

# ECR image URIs (override: make build-nginx ECR_NGINX=...)
ECR_REGISTRY ?= 975049961565.dkr.ecr.us-east-1.amazonaws.com
ECR_AUTH ?= $(ECR_REGISTRY)/microservices/auth
ECR_NGINX ?= $(ECR_REGISTRY)/microservices/nginx
AWS_REGION ?= us-east-1

# Route compose interpolation through .env.docker (+ optional .env.docker.local).
COMPOSE_ENV := --env-file .env.docker
ifneq (,$(wildcard .env.docker.local))
COMPOSE_ENV += --env-file .env.docker.local
endif
ifneq (, $(shell command -v docker-compose 2>/dev/null))
COMPOSE := docker-compose $(COMPOSE_ENV)
else
COMPOSE := docker compose $(COMPOSE_ENV)
endif

# Compose profiles: bundled postgres (docker-db) and redis (docker-redis).
# Disabled when DOCKER_*_ENABLED=false in .env.docker / .env.docker.local.
DOCKER_DB_ENABLED := $(shell grep -E '^DOCKER_DB_ENABLED=' .env.docker 2>/dev/null | tail -1 | cut -d= -f2 | tr -d '\r')
DOCKER_REDIS_ENABLED := $(shell grep -E '^DOCKER_REDIS_ENABLED=' .env.docker 2>/dev/null | tail -1 | cut -d= -f2 | tr -d '\r')
ifneq (,$(wildcard .env.docker.local))
DOCKER_DB_ENABLED := $(shell grep -E '^DOCKER_DB_ENABLED=' .env.docker.local 2>/dev/null | tail -1 | cut -d= -f2 | tr -d '\r')
DOCKER_REDIS_ENABLED := $(shell grep -E '^DOCKER_REDIS_ENABLED=' .env.docker.local 2>/dev/null | tail -1 | cut -d= -f2 | tr -d '\r')
endif
COMPOSE_PROFILES :=
ifneq ($(DOCKER_DB_ENABLED),false)
COMPOSE_PROFILES += docker-db
endif
ifneq ($(DOCKER_REDIS_ENABLED),false)
COMPOSE_PROFILES += docker-redis
endif
export COMPOSE_PROFILES

# Default target
help:
	@echo "Ecommerce Auth Service - Docker Commands"
	@echo "=========================================="
	@echo ""
	@echo "Available commands:"
	@echo "  make help              - Show this help message"
	@echo "  make build             - Build Docker images (compose)"
	@echo "  make build-production  - Build app image only (production target)"
	@echo "  make build-nginx       - Build nginx image for ECR"
	@echo "  make push-ecr          - Build, tag, and push auth + nginx to ECR"
	@echo "  make up                - Start all containers"
	@echo "  make down              - Stop all containers"
	@echo "  make restart           - Restart all containers"
	@echo "  make logs              - View logs from all services"
	@echo "  make logs-app          - View app container logs"
	@echo "  make logs-postgres     - View PostgreSQL container logs"
	@echo "  make logs-nginx        - View Nginx container logs"
	@echo "  make ps                - Show container status"
	@echo "  make bash              - SSH into app container"
	@echo "  make migrate           - Run database migrations"
	@echo "  make migrate-fresh     - Reset and run migrations"
	@echo "  make seed              - Run database seeders"
	@echo "  make tinker            - Start Laravel Tinker"
	@echo "  make test              - Run tests"
	@echo "  make composer-install  - Install Composer dependencies"
	@echo "  make npm-install       - Install NPM dependencies"
	@echo "  make npm-build         - Build frontend assets"
	@echo "  make npm-dev           - Start development server (watch mode)"
	@echo "  make clean             - Remove containers and volumes"
	@echo "  make fresh             - Clean and rebuild everything"
	@echo "  make db-backup         - Create database backup"
	@echo "  make db-restore        - Restore database from backup"
	@echo ""
	@echo "External DB (RDS): DOCKER_DB_ENABLED=false in .env.docker.local"
	@echo "No Redis container: DOCKER_REDIS_ENABLED=false in .env.docker.local"
	@echo "  Template: .env.docker.production.example"
	@echo "  Production (ECR+EC2): docs/PRODUCTION-ECR.md"
	@echo ""

# Build Docker images
build:
	@echo "Building Docker images..."
	$(COMPOSE) build

# Build PHP-FPM app image only (for ECR / standalone deploy)
build-production:
	@echo "Building production app image..."
	docker build --target production -t ecommerce-auth-service:latest .

# Build nginx reverse-proxy image (for ECR / standalone deploy)
build-nginx:
	@echo "Building nginx image..."
	docker build -f docker/nginx/Dockerfile -t ecommerce-auth-service-nginx:latest .
	docker tag ecommerce-auth-service-nginx:latest $(ECR_NGINX):latest
	@echo "Tagged: $(ECR_NGINX):latest"

# Login to AWS ECR
ecr-login:
	aws ecr get-login-password --region $(AWS_REGION) | \
		docker login --username AWS --password-stdin $(ECR_REGISTRY)

# Tag and push app + nginx images to ECR (run ecr-login first, or use push-ecr)
push-production: build-production
	docker tag ecommerce-auth-service:latest $(ECR_AUTH):latest
	docker push $(ECR_AUTH):latest
	@echo "Pushed: $(ECR_AUTH):latest"

push-nginx: build-nginx
	docker push $(ECR_NGINX):latest
	@echo "Pushed: $(ECR_NGINX):latest"

push-ecr: ecr-login push-production push-nginx
	@echo "All images pushed to $(ECR_REGISTRY)"

# Start containers
up:
	@echo "Starting containers..."
	$(COMPOSE) up -d
	@echo "✓ Containers started"
	@echo "Application URL: http://localhost"

# Stop containers
down:
	@echo "Stopping containers..."
	$(COMPOSE) down

# Restart containers
restart: down up
	@echo "✓ Containers restarted"

# View logs
logs:
	$(COMPOSE) logs -f

logs-app:
	$(COMPOSE) logs -f app

logs-postgres:
	$(COMPOSE) logs -f postgres

logs-nginx:
	$(COMPOSE) logs -f nginx

# Show container status
ps:
	$(COMPOSE) ps

# SSH into app container
bash:
	$(COMPOSE) exec app bash

# Run database migrations
migrate:
	$(COMPOSE) exec app php artisan migrate

# Reset and run migrations
migrate-fresh:
	$(COMPOSE) exec app php artisan migrate:fresh

# Run database seeders
seed:
	$(COMPOSE) exec app php artisan db:seed

# Start Laravel Tinker
tinker:
	$(COMPOSE) exec app php artisan tinker

# Run tests
test:
	$(COMPOSE) exec app php artisan test

# Install Composer dependencies
composer-install:
	$(COMPOSE) exec app composer install

# Install NPM dependencies
npm-install:
	$(COMPOSE) exec app npm install

# Build frontend assets
npm-build:
	$(COMPOSE) exec app npm run build

# Start development server (watch mode)
npm-dev:
	$(COMPOSE) exec app npm run dev

# Remove containers and volumes
clean:
	@echo "Removing containers and volumes..."
	$(COMPOSE) down -v
	@echo "✓ Containers and volumes removed"

# Clean and rebuild everything
fresh: clean build up
	@echo "Running migrations..."
	$(COMPOSE) exec app php artisan migrate
	@echo "✓ Fresh setup complete"

# Create database backup
db-backup:
	@echo "Creating database backup..."
	$(COMPOSE) exec -T postgres pg_dump -U auth_user ecommerce_auth > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "✓ Backup created"

# Restore database from backup (usage: make db-restore BACKUP=backup_20231201_120000.sql)
db-restore:
	@if [ -z "$(BACKUP)" ]; then \
		echo "Usage: make db-restore BACKUP=backup_file.sql"; \
		exit 1; \
	fi
	@echo "Restoring database from $(BACKUP)..."
	$(COMPOSE) exec -T postgres psql -U auth_user -d ecommerce_auth < $(BACKUP)
	@echo "✓ Database restored"

# Artisan commands
artisan:
	$(COMPOSE) exec app php artisan $(cmd)

# Composer commands
composer:
	$(COMPOSE) exec app composer $(cmd)

# NPM commands
npm:
	$(COMPOSE) exec app npm $(cmd)
