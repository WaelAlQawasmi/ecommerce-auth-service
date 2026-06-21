#!/bin/bash
set -e

echo "========================================="
echo "Ecommerce Auth Service - Production Startup"
echo "========================================="

# ─── Fix line endings on all scripts ──────────────────────────────────────
echo "Fixing line endings..."
find ./scripts -type f -name "*.sh" | xargs sed -i 's/\r//' 2>/dev/null || true

# ─── Ensure swap is active ────────────────────────────────────────────────
if swapon --show | grep -q /swapfile 2>/dev/null; then
  echo "✓ Swap already active"
else
  echo "→ No swap detected — creating 2GB swapfile..."
  sudo fallocate -l 2G /swapfile
  sudo chmod 600 /swapfile
  sudo mkswap /swapfile
  sudo swapon /swapfile
  if ! grep -q '/swapfile' /etc/fstab; then
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab > /dev/null
  fi
  echo "✓ Swap ready"
fi

# ─── Fix docker-compose package conflict ──────────────────────────────────
if dpkg -l docker-compose-v2 2>/dev/null | grep -q '^ii'; then
  echo "→ Removing conflicting docker-compose-v2 package..."
  sudo apt remove -y docker-compose-v2
fi

# ─── Fix my.cnf (must be a file, not a directory) ─────────────────────────
CNF_PATH="./docker/mysql/my.cnf"
if [ -d "$CNF_PATH" ]; then
  echo "→ my.cnf is a directory — removing and recreating as file..."
  rm -rf "$CNF_PATH"
fi
if [ ! -f "$CNF_PATH" ]; then
  echo "→ Creating my.cnf..."
  mkdir -p ./docker/mysql
  cat > "$CNF_PATH" <<'EOF'
[mysqld]
pid_file                        = /var/run/mysqld/mysqld.pid
socket                          = /var/run/mysqld/mysqld.sock
datadir                         = /var/lib/mysql
log_error                       = /dev/stderr
character_set_server            = utf8mb4
collation_server                = utf8mb4_0900_ai_ci
bind_address                    = 0.0.0.0
max_connections                 = 50
innodb_buffer_pool_size         = 128M
innodb_buffer_pool_instances    = 1
innodb_redo_log_capacity        = 64M
innodb_flush_method             = O_DIRECT
innodb_file_per_table           = ON
local_infile                    = OFF
slow_query_log                  = ON
slow_query_log_file             = /dev/stderr
long_query_time                 = 1
EOF
  echo "✓ my.cnf created"
else
  echo "✓ my.cnf already exists"
fi

test -x "$(command -v docker)" >/dev/null 2>&1 || {
  echo "ERROR: Docker is not installed. Install Docker and try again."
  exit 1
}

compose() {
  if command -v docker-compose >/dev/null 2>&1; then
    docker-compose "$@"
  elif docker compose version >/dev/null 2>&1; then
    docker compose "$@"
  else
    echo "ERROR: Docker Compose is not installed. Install Docker Compose and try again."
    exit 1
  fi
}

echo "✓ Docker and Docker Compose are installed"

echo ""
if [ ! -f .env ]; then
  if [ -f .env.docker ]; then
    echo "Creating .env from .env.docker"
    cp .env.docker .env
    echo "✓ .env created"
  else
    echo "ERROR: .env.docker file not found. Create a .env file manually."
    exit 1
  fi
else
  echo "✓ .env already exists"
fi

echo ""
echo "Building Docker images..."
compose build

echo ""
echo "Starting Docker containers..."
compose up -d

echo ""
echo "Waiting for containers to stabilise..."
sleep 10

echo "Checking service health..."
if compose ps | grep -E "Exit|unhealthy" >/dev/null 2>&1; then
  echo "WARNING: one or more services are not healthy."
  compose ps
  echo "Recent logs from services:"
  compose logs --tail=50
else
  echo "✓ All services appear to be running"
fi

echo ""
echo "Application is available at: http://localhost"
echo "Run migrations with: docker compose exec app php artisan migrate --force"
echo "Run seeders with: docker compose exec app php artisan db:seed"
echo ""