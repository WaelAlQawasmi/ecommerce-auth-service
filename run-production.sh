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

test -x "$(command -v docker)" >/dev/null 2>&1 || {
  echo "ERROR: Docker is not installed. Install Docker and try again."
  exit 1
}

# Compose reads .env automatically for interpolation; pass .env.docker so it
# overrides stale .env values (e.g. NGINX_HOST_PORT=80 on the server).
COMPOSE_ENV=(--env-file .env.docker)
if [ -f .env.docker.local ]; then
  COMPOSE_ENV+=(--env-file .env.docker.local)
fi

compose() {
  if command -v docker-compose >/dev/null 2>&1; then
    docker-compose "${COMPOSE_ENV[@]}" "$@"
  elif docker compose version >/dev/null 2>&1; then
    docker compose "${COMPOSE_ENV[@]}" "$@"
  else
    echo "ERROR: Docker Compose is not installed. Install Docker Compose and try again."
    exit 1
  fi
}

# #region agent log
_debug_log() {
  local hypothesis_id="$1" location="$2" message="$3" data="$4"
  printf '{"sessionId":"960710","runId":"pre-fix","hypothesisId":"%s","location":"%s","message":"%s","data":%s,"timestamp":%s}\n' \
    "$hypothesis_id" "$location" "$message" "$data" "$(date +%s000)" >> debug-960710.log 2>/dev/null || true
}
_read_env_var() {
  local file="$1" key="$2"
  if [ -f "$file" ]; then
    grep -E "^${key}=" "$file" 2>/dev/null | tail -1 | cut -d= -f2- | tr -d '\r' || true
  fi
}
_read_docker_db_enabled() {
  local val
  val="$(_read_env_var .env.docker.local DOCKER_DB_ENABLED)"
  [ -z "$val" ] && val="$(_read_env_var .env.docker DOCKER_DB_ENABLED)"
  [ -z "$val" ] && val="true"
  echo "$val"
}

configure_compose_profiles() {
  if [ "$(_read_docker_db_enabled)" = "false" ]; then
    export COMPOSE_PROFILES=""
    echo "→ External database mode (DOCKER_DB_ENABLED=false)"
  else
    export COMPOSE_PROFILES="${COMPOSE_PROFILES:-docker-db}"
    echo "→ Bundled PostgreSQL enabled (DOCKER_DB_ENABLED=true)"
  fi
}
_debug_nginx_port_diagnostics() {
  local env_docker env_local env_file shell_env compose_default compose_resolved docker_ps
  env_docker="$(_read_env_var .env.docker NGINX_HOST_PORT)"
  env_local="$(_read_env_var .env.docker.local NGINX_HOST_PORT)"
  env_file="$(_read_env_var .env NGINX_HOST_PORT)"
  shell_env="${NGINX_HOST_PORT:-}"
  compose_default="$(grep -E 'NGINX_HOST_PORT' docker-compose.yml 2>/dev/null | head -1 | tr -d '\r' || true)"
  compose_resolved="$(compose config 2>/dev/null | grep -A2 'nginx:' | grep -E 'published|target' | tr '\n' ' ' | tr -d '\r' || compose config 2>/dev/null | awk '/nginx:/{f=1} f&&/ports:/{getline; print; exit}' | tr -d '\r' || true)"
  docker_ps="$(docker ps --filter name=nginx --format '{{.Names}} {{.Ports}}' 2>/dev/null | tr -d '\r' || true)"
  _debug_log "H1" "run-production.sh:env-files" "NGINX_HOST_PORT from env files" "{\"env_docker\":\"${env_docker}\",\"env_docker_local\":\"${env_local}\",\"env\":\"${env_file}\",\"shell\":\"${shell_env}\"}"
  _debug_log "H2" "run-production.sh:compose-env" "run-production compose invocation" "{\"uses_env_file_docker\":true,\"compose_cmd\":\"compose --env-file .env.docker up -d\"}"
  _debug_log "H3" "run-production.sh:compose-yml" "docker-compose.yml port mapping line" "{\"line\":\"${compose_default}\"}"
  _debug_log "H4" "run-production.sh:resolved-ports" "Resolved nginx port mapping" "{\"compose_config\":\"${compose_resolved}\",\"docker_ps\":\"${docker_ps}\"}"
  _debug_log "H5" "run-production.sh:override-check" "Possible override sources" "{\"env_docker_local_set\":$([ -n \"$env_local\" ] && echo true || echo false),\"shell_set\":$([ -n \"$shell_env\" ] && echo true || echo false),\"env_set\":$([ -n \"$env_file\" ] && echo true || echo false)}"
  echo "→ Nginx port diagnostics: .env=${env_file:-unset} .env.docker=${env_docker:-unset} resolved=${compose_resolved:-unknown}"
}
# #endregion

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
configure_compose_profiles
_debug_nginx_port_diagnostics
compose build

echo ""
echo "Starting Docker containers..."
compose up -d
_debug_log "H4" "run-production.sh:after-up" "nginx container ports after compose up" "{\"runId\":\"post-fix\",\"docker_ps\":\"$(docker ps --filter name=nginx --format '{{.Names}} {{.Ports}}' 2>/dev/null | tr -d '\r' || true)\"}"

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
NGINX_PORT="$(_read_env_var .env.docker NGINX_HOST_PORT)"
NGINX_PORT="${NGINX_PORT:-8080}"
echo "Application is available at: http://localhost:${NGINX_PORT}"
echo "Run migrations with: docker compose --env-file .env.docker exec app php artisan migrate --force"
echo "Run seeders with: docker compose exec app php artisan db:seed"
echo ""