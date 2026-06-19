# Quick Start Guide - Docker Setup

## Overview

This guide will help you get the Ecommerce Auth Service running in Docker in just a few minutes.

## Prerequisites

1. **Docker** - [Install Docker Desktop](https://www.docker.com/products/docker-desktop/)
   - Windows: Requires Windows 10/11 Pro or Docker Desktop
   - macOS: Intel or Apple Silicon supported
   - Linux: Install Docker Engine

2. **Docker Compose** - Usually included with Docker Desktop
   - Verify: `docker-compose --version`

3. **Git** (optional, for cloning)

## One-Minute Quick Start

### Windows Users

```bash
# Navigate to project directory
cd c:\Apache\htdocs\ecommerce-auth-service

# Run the setup script
docker-start.bat
```

### macOS/Linux Users

```bash
# Navigate to project directory
cd ecommerce-auth-service

# Run the setup script
bash docker-start.sh
```

Or simply run:

```bash
docker-compose up -d --build
```

## Access Your Application

- **Application**: http://localhost
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Default Credentials

| Service | Username | Password | Database |
|---------|----------|----------|----------|
| MySQL | auth_user | authpassword123 | ecommerce_auth |
| MySQL Root | root | rootpassword123 | - |
| Redis | - | (no auth) | - |

## Essential Commands

### Check if everything is running

```bash
docker-compose ps
```

Should show all 4 services as "Up" and "healthy"

### View real-time logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f mysql
docker-compose logs -f nginx
docker-compose logs -f redis
```

### Run Laravel Artisan commands

```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker
docker-compose exec app php artisan cache:clear
```

### Run NPM commands

```bash
docker-compose exec app npm install
docker-compose exec app npm run build
docker-compose exec app npm run dev
```

### Run Composer commands

```bash
docker-compose exec app composer install
docker-compose exec app composer update
```

### Access MySQL

```bash
docker-compose exec mysql mysql -u auth_user -p ecommerce_auth
# Password: authpassword123
```

### Stop containers

```bash
docker-compose down
```

### Restart containers

```bash
docker-compose restart
```

## Using Makefile (macOS/Linux)

If you have `make` installed, use these convenient commands:

```bash
make help              # Show all available commands
make up                # Start containers
make down              # Stop containers
make migrate           # Run migrations
make seed              # Seed database
make test              # Run tests
make logs-app          # View app logs
make bash              # SSH into app container
```

## Project Structure

```
ecommerce-auth-service/
├── Dockerfile                 # PHP 8.3 container definition
├── docker-compose.yml         # Multi-container orchestration
├── .dockerignore              # Files to exclude from Docker build
├── .env.docker                # Docker environment configuration
├── docker-start.sh            # Quick start script (Linux/macOS)
├── docker-start.bat           # Quick start script (Windows)
├── Makefile                   # Convenient commands
├── DOCKER.md                  # Comprehensive Docker guide
│
├── docker/
│   ├── nginx/
│   │   ├── conf.d/
│   │   │   └── app.conf       # Nginx configuration
│   │   └── ssl/               # SSL certificates (if using HTTPS)
│   │
│   └── mysql/
│       └── my.cnf             # MySQL configuration (schema owned by migrations)
│
├── app/                       # Laravel application
├── config/                    # Configuration files
├── database/                  # Migrations and seeders
├── public/                    # Public assets
├── resources/                 # Views, CSS, JS
├── routes/                    # Route definitions
├── storage/                   # Storage directory
├── tests/                     # Test files
└── vendor/                    # PHP dependencies
```

## What's in the Box?

### Services Running in Docker

1. **PHP 8.3 FPM** (app)
   - Laravel 13.8
   - All required PHP extensions
   - Composer and Node.js installed

2. **MySQL 8.0** (mysql)
   - Automatic database creation
   - Persistent volume for data
   - Health checks enabled

3. **Nginx** (nginx)
   - Reverse proxy
   - SSL/TLS ready
   - Performance optimized

4. **Redis 7** (redis)
   - Caching layer
   - Session storage
   - Job queue support

## Common Tasks

### First Time Setup

```bash
# 1. Start containers
docker-compose up -d --build

# 2. Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# 3. Run migrations
docker-compose exec app php artisan migrate

# 4. Create admin user (if you have seeders)
docker-compose exec app php artisan db:seed
```

### Development Workflow

```bash
# Terminal 1: Watch frontend changes
docker-compose exec app npm run dev

# Terminal 2: View application logs
docker-compose logs -f app

# Terminal 3: Run tests
docker-compose exec app php artisan test --watch
```

### Database Operations

```bash
# Create database backup
docker-compose exec mysql mysqldump -u auth_user -p ecommerce_auth > backup.sql

# Restore database
docker-compose exec -T mysql mysql -u auth_user -p ecommerce_auth < backup.sql

# Access MySQL CLI
docker-compose exec mysql mysql -u auth_user -p ecommerce_auth
```

### Cache Operations

```bash
# Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# View Redis keys
docker-compose exec redis redis-cli KEYS "*"
```

## Customizing Configuration

### Change Database Credentials

Edit `docker-compose.yml` and modify:

```yaml
environment:
  - DB_DATABASE=my_database
  - DB_USERNAME=my_user
  - DB_PASSWORD=my_password
```

Then restart:

```bash
docker-compose down
docker-compose up -d --build
```

### Change Application Port

Edit `docker-compose.yml`:

```yaml
nginx:
  ports:
    - "8080:80"    # Access at http://localhost:8080
```

### Add SSL/HTTPS

1. Place certificates in `docker/nginx/ssl/`:
   - `cert.pem`
   - `key.pem`

2. Uncomment HTTPS section in `docker/nginx/conf.d/app.conf`

3. Restart: `docker-compose restart nginx`

## Troubleshooting

### Containers won't start

```bash
# Check logs
docker-compose logs

# Rebuild
docker-compose down -v
docker-compose up -d --build
```

### Port already in use

```bash
# Find process using port
# Windows: netstat -ano | findstr :80
# Linux/Mac: lsof -i :80

# Change port in docker-compose.yml
```

### Database connection errors

```bash
# Verify MySQL is running
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Test connection
docker-compose exec app mysql -h mysql -u auth_user -p ecommerce_auth -e "SELECT 1"
```

### Permission issues

```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

### Out of disk space

```bash
# Clean up Docker
docker system prune -a --volumes

# Rebuild
docker-compose up -d --build
```

## Performance Tips

1. **Disable unnecessary services** - Edit `docker-compose.yml`
2. **Allocate more memory to Docker** - Docker Desktop settings
3. **Use .env file** for environment-specific configs
4. **Enable Docker BuildKit** for faster builds:
   ```bash
   export DOCKER_BUILDKIT=1
   docker-compose up -d --build
   ```

## Next Steps

1. Review [DOCKER.md](./DOCKER.md) for comprehensive documentation
2. Configure your application in `config/` directory
3. Create your first model and migration:
   ```bash
   docker-compose exec app php artisan make:model YourModel -m
   ```
4. Set up authentication:
   ```bash
   docker-compose exec app php artisan make:auth
   ```
5. Start developing!

## Getting Help

- **Docker Documentation**: https://docs.docker.com
- **Docker Compose**: https://docs.docker.com/compose
- **Laravel Documentation**: https://laravel.com/docs
- **Check logs**: `docker-compose logs -f`

## Common Issues and Solutions

| Issue | Solution |
|-------|----------|
| Permission denied | `chmod +x docker-start.sh` |
| Port 80 in use | Change port in `docker-compose.yml` |
| MySQL won't connect | Wait 30 seconds for MySQL to initialize |
| File changes not reflecting | Check volume mounts in `docker-compose.yml` |
| Out of memory | Increase Docker Desktop memory allocation |
| Slow performance | Check system resources, disk space |

## Production Deployment

Before deploying to production:

1. Update `.env` with production values
2. Set `APP_DEBUG=false`
3. Configure HTTPS with valid certificates
4. Use managed database (AWS RDS, Azure Database)
5. Set up proper monitoring and logging
6. Configure backups
7. Use environment variables for secrets

See [DOCKER.md](./DOCKER.md) for production deployment guide.

---

**Happy coding!** 🚀

For more detailed information, see [DOCKER.md](./DOCKER.md)
