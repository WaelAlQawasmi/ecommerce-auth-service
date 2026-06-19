.PHONY: help build up down logs bash app-bash migrate seed tinker test ps

# Route every compose command through .env.docker so ${DB_*}/${REDIS_*}
# interpolation resolves consistently (Compose only auto-reads ./.env otherwise).
COMPOSE := docker-compose --env-file .env.docker

# Default target
help:
	@echo "Ecommerce Auth Service - Docker Commands"
	@echo "=========================================="
	@echo ""
	@echo "Available commands:"
	@echo "  make help              - Show this help message"
	@echo "  make build             - Build Docker images"
	@echo "  make up                - Start all containers"
	@echo "  make down              - Stop all containers"
	@echo "  make restart           - Restart all containers"
	@echo "  make logs              - View logs from all services"
	@echo "  make logs-app          - View app container logs"
	@echo "  make logs-mysql        - View MySQL container logs"
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

# Build Docker images
build:
	@echo "Building Docker images..."
	$(COMPOSE) build

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

logs-mysql:
	$(COMPOSE) logs -f mysql

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
	$(COMPOSE) exec mysql mysqldump -u auth_user -p ecommerce_auth > backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "✓ Backup created"

# Restore database from backup (usage: make db-restore BACKUP=backup_20231201_120000.sql)
db-restore:
	@if [ -z "$(BACKUP)" ]; then \
		echo "Usage: make db-restore BACKUP=backup_file.sql"; \
		exit 1; \
	fi
	@echo "Restoring database from $(BACKUP)..."
	$(COMPOSE) exec -T mysql mysql -u auth_user -p ecommerce_auth < $(BACKUP)
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
