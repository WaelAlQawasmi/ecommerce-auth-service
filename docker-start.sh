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

compose build

echo ""
echo "Starting containers..."
echo ""

compose up -d

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
echo "  Host: localhost:3306"
echo "  User: auth_user"
echo "  Password: authpassword123"
echo "  Database: ecommerce_auth"
echo ""
echo "For more information, see DOCKER.md"
echo ""
