@echo off
setlocal enabledelayedexpansion

echo =========================================
echo Ecommerce Auth Service - Production Startup
echo =========================================
echo.

where docker >nul 2>nul
if errorlevel 1 (
  echo ERROR: Docker is not installed. Install Docker and try again.
  exit /b 1
)

where docker-compose >nul 2>nul
if errorlevel 1 (
  echo ERROR: Docker Compose is not installed. Install Docker Compose and try again.
  exit /b 1
)

echo ✓ Docker and Docker Compose are installed
echo.

if not exist .env (
  if exist .env.docker (
    echo Creating .env from .env.docker...
    copy /y .env.docker .env >nul
    echo ✓ .env created
  ) else (
    echo ERROR: .env.docker file not found. Create a .env file manually.
    exit /b 1
  )
) else (
  echo ✓ .env already exists
)

echo.
echo Building and starting Docker containers...

docker-compose up -d --build

echo.
echo Waiting for containers to stabilise...
timeout /t 10 /nobreak >nul

echo Checking service health...
for /f "tokens=*" %%i in ('docker-compose ps ^| findstr /R /C:"Exit" /C:"unhealthy"') do (
  echo WARNING: one or more services are not healthy.
  docker-compose ps
  echo Recent logs from services:
  docker-compose logs --tail=50
  goto end
)

echo ✓ All services appear to be running

:end
echo.
echo Application is available at: http://localhost
echo Run migrations with: docker-compose exec app php artisan migrate --force
echo Run seeders with: docker-compose exec app php artisan db:seed
echo.
