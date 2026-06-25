#!/bin/bash

# Exit on error
set -e

echo "Waiting for PostgreSQL to be ready..."
while ! PGPASSWORD="${DB_PASSWORD}" pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -q; do
    echo "Waiting for PostgreSQL..."
    sleep 1
done

echo "PostgreSQL is ready!"

# Check if .env file exists, if not copy from .env.docker
if [ ! -f .env ]; then
    echo "Creating .env file from .env.docker..."
    cp .env.docker .env
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear existing caches
echo "Clearing application caches..."
php artisan config:clear --no-interaction
php artisan cache:clear --no-interaction
php artisan view:clear --no-interaction

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed database if needed (uncomment if you have seeders)
# echo "Seeding database..."
# php artisan db:seed --force

# Create storage link if needed
if [ ! -L public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

echo "Docker initialization complete!"

# Execute the CMD
exec "$@"
