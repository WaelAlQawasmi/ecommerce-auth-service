# Docker Setup Guide - Ecommerce Auth Service

## Prerequisites

- Docker (version 20.10+)
- Docker Compose (version 2.0+)
- Git

## Project Structure

```
docker/
├── nginx/
│   ├── conf.d/
│   │   └── app.conf          # Nginx configuration
│   └── ssl/                  # SSL certificates (create if needed)
└── mysql/
    └── my.cnf                # MySQL configuration
```

## Quick Start

### 1. Clone or Setup Repository

```bash
cd c:\Apache\htdocs\ecommerce-auth-service
```

### 2. Create Environment File

The `.env.docker` file is provided with default credentials. For local development:

```bash
cp .env.docker .env
```

Or modify environment variables in `docker-compose.yml` before running.

### 3. Build and Start Containers

```bash
docker-compose up -d --build
```

This will:
- Build the PHP-FPM image
- Start PHP-FPM container
- Start MySQL 8.0 container
- Start Nginx container
- Start Redis container
- Run database migrations automatically

### 4. Verify Services

```bash
docker-compose ps
```

Expected output:
```
NAME                          STATUS
ecommerce-auth-app           Up (healthy)
ecommerce-auth-mysql         Up (healthy)
ecommerce-auth-nginx         Up (healthy)
ecommerce-auth-redis         Up (healthy)
```

### 5. Access the Application

- **Application**: http://localhost
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Environment Configuration

### Default Credentials

```
MySQL:
  Database: ecommerce_auth
  Username: auth_user
  Password: authpassword123
  Root Password: rootpassword123

Redis: No authentication (default)
```

### Customize Environment

Edit `docker-compose.yml` or create a `.env` file:

```bash
# Example customization
DB_DATABASE=my_database
DB_USERNAME=my_user
DB_PASSWORD=my_secure_password
DB_PORT=3307  # Custom port if needed
APP_PORT=8080  # Custom app port if needed
```

## Common Commands

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f mysql
docker-compose logs -f nginx
```

### Execute Commands in Container

```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# NPM commands
docker-compose exec app npm install
docker-compose exec app npm run build
docker-compose exec app npm run dev
```

### Database Access

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u auth_user -p ecommerce_auth
# Password: authpassword123

# Create database backup
docker-compose exec mysql mysqldump -u auth_user -p ecommerce_auth > backup.sql
# Password: authpassword123

# Restore database backup
docker-compose exec -T mysql mysql -u auth_user -p ecommerce_auth < backup.sql
# Password: authpassword123
```

### Redis Access

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# View all keys
docker-compose exec redis redis-cli KEYS "*"

# Flush all data
docker-compose exec redis redis-cli FLUSHALL
```

## PHP Modules

The Docker image includes the following PHP extensions:
- pdo, pdo_mysql
- bcmath
- ctype
- fileinfo
- gd (with freetype and jpeg support)
- intl
- json
- mbstring
- openssl
- tokenizer
- xml
- curl
- zip

## Development Workflow

### First Time Setup

```bash
# 1. Build and start containers
docker-compose up -d --build

# 2. Install PHP dependencies
docker-compose exec app composer install

# 3. Install Node dependencies and build assets
docker-compose exec app npm install
docker-compose exec app npm run build

# 4. Run migrations
docker-compose exec app php artisan migrate

# 5. Run seeders (if available)
docker-compose exec app php artisan db:seed
```

### Daily Development

```bash
# Start containers
docker-compose up -d

# Watch for file changes (Vite)
docker-compose exec app npm run dev

# In another terminal, watch for Laravel changes
docker-compose exec app php artisan queue:listen

# View logs
docker-compose logs -f app
```

### Stop and Remove Containers

```bash
# Stop containers (data preserved)
docker-compose stop

# Remove containers (data preserved in volumes)
docker-compose down

# Remove everything including volumes
docker-compose down -v
```

## SSL/HTTPS Configuration

To enable HTTPS:

1. Place SSL certificate and key in `docker/nginx/ssl/`:
   - `cert.pem` - SSL certificate
   - `key.pem` - Private key

2. Uncomment the HTTPS section in `docker/nginx/conf.d/app.conf`

3. Restart Nginx:
   ```bash
   docker-compose restart nginx
   ```

## Production Deployment

### Important Changes for Production

1. **Update `.env`**:
   ```
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Use environment variables** for sensitive data (from CI/CD or secrets manager)

3. **Disable query cache** or configure appropriately in `docker/mysql/my.cnf`

4. **Configure HTTPS** with valid SSL certificates

5. **Set up proper logging and monitoring**

6. **Use managed database** (AWS RDS, Azure Database) instead of containerized MySQL

## Troubleshooting

### Containers Won't Start

```bash
# Check error logs
docker-compose logs -f

# Rebuild containers
docker-compose down -v
docker-compose up -d --build
```

### Permission Denied Errors

```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

### Database Connection Failed

```bash
# Verify MySQL is running and healthy
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Test connection
docker-compose exec app mysql -h mysql -u auth_user -p ecommerce_auth -e "SELECT 1"
```

### Memory Issues

Update `docker/mysql/my.cnf`:
```ini
innodb_buffer_pool_size = 128M  # Reduce if low memory
```

### Port Already in Use

Change ports in `docker-compose.yml`:
```yaml
ports:
  - "8000:80"    # Changed from 80:80
```

## Performance Tips

1. **Use named volumes** for MySQL and Redis
2. **Configure Docker Desktop** memory allocation
3. **Use `.dockerignore`** to exclude unnecessary files
4. **Enable BuildKit**:
   ```bash
   export DOCKER_BUILDKIT=1
   docker-compose up -d --build
   ```

## Next Steps

- Configure your application in `config/`
- Create your models and migrations
- Set up authentication and authorization
- Configure mail, caching, and queues
- Deploy to production environment

## Support

For issues or questions:
- Check Docker Compose documentation: https://docs.docker.com/compose/
- Check Laravel documentation: https://laravel.com/docs
- Review service logs: `docker-compose logs -f`
