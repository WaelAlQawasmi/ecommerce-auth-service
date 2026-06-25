@echo off
REM Quick start script for Docker setup (Windows)

echo =========================================
echo Ecommerce Auth Service - Docker Setup
echo =========================================
echo.

REM Check if Docker is installed
where docker >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Docker is not installed. Please install Docker first.
    exit /b 1
)

REM Check if Docker Compose is installed
where docker-compose >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Docker Compose is not installed. Please install Docker Compose first.
    exit /b 1
)

echo ✓ Docker and Docker Compose are installed
echo.

REM Create .env file if it doesn't exist
if not exist .env (
    echo Creating .env file from .env.docker...
    copy .env.docker .env
    echo ✓ .env file created
) else (
    echo ✓ .env file already exists
)

REM Enable bundled PostgreSQL unless DOCKER_DB_ENABLED=false in env files
set DOCKER_DB_ENABLED=
if exist .env.docker.local (
    for /f "usebackq tokens=1,* delims==" %%A in (`findstr /b /r "DOCKER_DB_ENABLED=" .env.docker.local`) do set DOCKER_DB_ENABLED=%%B
)
if not defined DOCKER_DB_ENABLED (
    for /f "usebackq tokens=1,* delims==" %%A in (`findstr /b /r "DOCKER_DB_ENABLED=" .env.docker`) do set DOCKER_DB_ENABLED=%%B
)
if not defined DOCKER_DB_ENABLED set DOCKER_DB_ENABLED=true

if /i "%DOCKER_DB_ENABLED%"=="false" (
    set COMPOSE_PROFILES=
    echo → External database mode (DOCKER_DB_ENABLED=false)
) else (
    set COMPOSE_PROFILES=docker-db
    echo → Bundled PostgreSQL enabled (DOCKER_DB_ENABLED=true)
)

set COMPOSE_ARGS=--env-file .env.docker
if exist .env.docker.local set COMPOSE_ARGS=%COMPOSE_ARGS% --env-file .env.docker.local

echo.
echo Building and starting containers...
echo.

REM Build and start containers
docker-compose %COMPOSE_ARGS% up -d --build

echo.
echo Waiting for services to be healthy...
timeout /t 5 /nobreak

REM Check if containers are running
docker-compose %COMPOSE_ARGS% ps | find "unhealthy" >nul 2>nul
if %errorlevel% equ 0 (
    echo WARNING: Some services are not healthy. Checking logs...
    docker-compose %COMPOSE_ARGS% logs
) else (
    echo ✓ All services are running
)

echo.
echo =========================================
echo Setup Complete!
echo =========================================
echo.
echo Application is running at: http://localhost
echo.
echo Useful commands:
echo   docker-compose logs -f app      # View app logs
echo   docker-compose exec app php artisan migrate  # Run migrations
echo   docker-compose exec app php artisan db:seed  # Run seeders
echo   docker-compose ps               # Show container status
echo   docker-compose down             # Stop containers
echo.
echo Database credentials:
if /i "%DOCKER_DB_ENABLED%"=="false" (
    echo   Using external database — see DB_HOST in .env.docker / .env.docker.local
) else (
    echo   Host: localhost:5432 (container: postgres)
    echo   User: auth_user
    echo   Password: authpassword123
    echo   Database: ecommerce_auth
)
echo.
echo For more information, see DOCKER.md
echo.
pause
