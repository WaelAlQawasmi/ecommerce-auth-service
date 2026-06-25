#!/bin/bash
# Quick start script for Docker setup

echo "========================================="
echo "Ecommerce Auth Service - Docker Setup"
echo "========================================="
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "ERROR: Docker is not installed. Please install Docker first."
    exit 1
fi

compose() {
    if command -v docker-compose &> /dev/null; then
        docker-compose "$@"
    elif docker compose version &> /dev/null; then
        docker compose "$@"
    else
        echo "ERROR: Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
}

read_env_var() {
    local file="$1" key="$2"
    if [ -f "$file" ]; then
        grep -E "^${key}=" "$file" 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '\r' || true
    fi
}

configure_compose_profiles() {
    local enabled
    enabled="$(read_env_var .env.docker.local DOCKER_DB_ENABLED)"
    [ -z "$enabled" ] && enabled="$(read_env_var .env.docker DOCKER_DB_ENABLED)"
    [ -z "$enabled" ] && enabled="true"

    if [ "$enabled" = "false" ]; then
        export COMPOSE_PROFILES=""
        echo "→ External database mode (DOCKER_DB_ENABLED=false)"
    else
        export COMPOSE_PROFILES="${COMPOSE_PROFILES:-docker-db}"
        echo "→ Bundled PostgreSQL enabled (DOCKER_DB_ENABLED=true)"
    fi
}

compose_env_args=(--env-file .env.docker)
if [ -f .env.docker.local ]; then
    compose_env_args+=(--env-file .env.docker.local)
fi

echo "✓ Docker and Docker Compose are installed"
echo ""

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file from .env.docker..."
    cp .env.docker .env
    echo "✓ .env file created"
else
    echo "✓ .env file already exists"
fi

echo ""
echo "Building Docker images..."
echo ""

configure_compose_profiles
compose "${compose_env_args[@]}" build

echo ""
echo "Starting containers..."
echo ""

compose "${compose_env_args[@]}" up -d

echo ""
echo "Waiting for services to be healthy..."
sleep 5

# Check if all services are running
if compose ps | grep -q "unhealthy\|Exit"; then
    echo "WARNING: Some services are not healthy. Checking logs..."
    compose logs
else
    echo "✓ All services are running"
fi

echo ""
echo "========================================="
echo "Setup Complete!"
echo "========================================="
echo ""
echo "Application is running at: http://localhost"
echo ""
echo "Useful commands:"
echo "  docker compose logs -f app      # View app logs"
echo "  docker compose exec app php artisan migrate  # Run migrations"
echo "  docker compose exec app php artisan db:seed  # Run seeders"
echo "  docker compose ps               # Show container status"
echo "  docker compose down             # Stop containers"
echo ""
echo "Database credentials:"
enabled="$(read_env_var .env.docker.local DOCKER_DB_ENABLED)"
[ -z "$enabled" ] && enabled="$(read_env_var .env.docker DOCKER_DB_ENABLED)"
[ -z "$enabled" ] && enabled="true"
if [ "$enabled" = "false" ]; then
    echo "  Using external database — see DB_HOST in .env.docker / .env.docker.local"
else
    echo "  Host: localhost:5432 (container: postgres)"
    echo "  User: auth_user"
    echo "  Password: authpassword123"
    echo "  Database: ecommerce_auth"
fi
echo ""
echo "For more information, see DOCKER.md"
echo ""
