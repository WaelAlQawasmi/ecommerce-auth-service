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

REM Compose env files (same as Makefile / run-production.sh)
set COMPOSE_ARGS=--env-file .env.docker
if exist .env.docker.local set COMPOSE_ARGS=%COMPOSE_ARGS% --env-file .env.docker.local

REM DOCKER_DB_ENABLED: false = external RDS, true = bundled postgres container
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
  echo → External database mode (DOCKER_DB_ENABLED=false, e.g. RDS)
) else (
  set COMPOSE_PROFILES=docker-db
  echo → Bundled PostgreSQL enabled (DOCKER_DB_ENABLED=true)
)

echo.
echo Building Docker images...
docker-compose %COMPOSE_ARGS% build
if errorlevel 1 exit /b 1

echo.
echo Starting Docker containers...
docker-compose %COMPOSE_ARGS% up -d
if errorlevel 1 exit /b 1

echo.
echo Waiting for containers to stabilise...
timeout /t 10 /nobreak >nul

echo Checking service health...
docker-compose %COMPOSE_ARGS% ps | findstr /R /C:"Exit" /C:"unhealthy" >nul 2>nul
if not errorlevel 1 (
  echo WARNING: one or more services are not healthy.
  docker-compose %COMPOSE_ARGS% ps
  echo Recent logs from services:
  docker-compose %COMPOSE_ARGS% logs --tail=50
) else (
  echo ✓ All services appear to be running
)

echo.
if /i "%DOCKER_DB_ENABLED%"=="false" (
  echo Database: external (RDS) — postgres container not started
) else (
  echo Database: bundled PostgreSQL container
)
echo Application is available at: http://localhost:8080
echo Run migrations with: docker-compose %COMPOSE_ARGS% exec app php artisan migrate --force
echo Run seeders with: docker-compose %COMPOSE_ARGS% exec app php artisan db:seed
echo.
