#!/bin/sh
# =============================================================================
# docker-entrypoint.sh — Laravel container initialisation
#
# Runs BEFORE php-fpm starts. Handles:
#   1. Waiting for MySQL to be reachable (with exponential backoff)
#   2. Running database migrations (--isolated prevents concurrent runs)
#   3. Warming the Laravel config/route/view caches
#   4. Creating the storage symlink if missing
#
# Uses /bin/sh (not bash) for maximum Alpine compatibility.
# set -eu: exit on error (-e) and on unbound variable (-u).
#
# CONTAINER_ROLE controls which initialisation steps run:
#   app        (default) — full init: migrations, cache warming, storage link
#   queue | scheduler    — wait for the database only; the app container owns
#                          schema migrations and cache warming so they are not
#                          repeated (and raced) by every worker container.
# =============================================================================
set -eu

CONTAINER_ROLE="${CONTAINER_ROLE:-app}"

# ---------------------------------------------------------------------------
# Colour helpers (falls back gracefully if the terminal has no colour support)
# ---------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Colour

log()  { printf "${GREEN}[entrypoint]${NC} %s\n" "$*"; }
warn() { printf "${YELLOW}[entrypoint]${NC} %s\n" "$*"; }
fail() { printf "${RED}[entrypoint]${NC} %s\n" "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# 1. Wait for MySQL with exponential backoff
#    Does NOT pass the password as a CLI argument — uses MYSQL_PWD instead
#    so it never appears in `ps` output.
# ---------------------------------------------------------------------------
wait_for_mysql() {
    local host="${DB_HOST:-mysql}"
    local port="${DB_PORT:-3306}"
    local user="${DB_USERNAME:-laravel}"
    local max_attempts=30
    local attempt=0
    local wait=2

    log "Waiting for MySQL at ${host}:${port} ..."

    while [ $attempt -lt $max_attempts ]; do
        attempt=$((attempt + 1))

        # MYSQL_PWD is the safe way to supply a password to mysql client tools
        if MYSQL_PWD="${DB_PASSWORD}" mysqladmin \
                ping \
                --host="${host}" \
                --port="${port}" \
                --user="${user}" \
                --silent \
                --connect-timeout=3 \
                2>/dev/null; then
            log "MySQL is ready (attempt ${attempt}/${max_attempts})."
            return 0
        fi

        warn "MySQL not ready yet (attempt ${attempt}/${max_attempts}). Retrying in ${wait}s ..."
        sleep "${wait}"
        # Exponential backoff capped at 30 s
        wait=$(( wait < 30 ? wait * 2 : 30 ))
    done

    fail "MySQL did not become ready after ${max_attempts} attempts. Aborting."
}

# ---------------------------------------------------------------------------
# 2. Generate APP_KEY if the environment has none
#    In production this should always be injected as a secret — this is a
#    safety net for first-run / CI scenarios.
# ---------------------------------------------------------------------------
ensure_app_key() {
    if [ -z "${APP_KEY:-}" ]; then
        warn "APP_KEY is not set. Generating a temporary key..."
        php artisan key:generate --force --no-interaction
        warn "Set APP_KEY as a persistent secret in your .env or secrets manager."
    fi
}

# ---------------------------------------------------------------------------
# 3. Run database migrations
#    --isolated acquires a deployment lock so parallel containers don't
#    race each other during a rolling deploy.
# ---------------------------------------------------------------------------
run_migrations() {
    log "Running database migrations ..."
    php artisan migrate --force --no-interaction --isolated
}

# ---------------------------------------------------------------------------
# Rebuild the package manifest so a persistent bootstrap/cache volume can never
# serve a stale provider list after dependencies change between deploys.
# ---------------------------------------------------------------------------
refresh_package_manifest() {
    log "Refreshing package manifest ..."
    php artisan package:discover --ansi
}

# ---------------------------------------------------------------------------
# Passport: ensure signing keys exist exactly once across all instances.
#
# Priority (first match wins):
#   1. PASSPORT_PRIVATE_KEY + PASSPORT_PUBLIC_KEY env vars (cloud secrets)
#   2. PASSPORT_*_KEY_FILE mounted secret files → copied to storage once
#   3. storage/oauth-*.key already on the shared volume → reuse
#   4. Generate via passport:keys (first boot only, with a lock for races)
# ---------------------------------------------------------------------------
passport_keys_configured_via_env() {
    [ -n "${PASSPORT_PRIVATE_KEY:-}" ] && [ -n "${PASSPORT_PUBLIC_KEY:-}" ]
}

passport_keys_exist_in_storage() {
    [ -f storage/oauth-private.key ] && [ -f storage/oauth-public.key ]
}

import_passport_keys_from_secret_files() {
    private_file="${PASSPORT_PRIVATE_KEY_FILE:-}"
    public_file="${PASSPORT_PUBLIC_KEY_FILE:-}"

    if [ -z "${private_file}" ] || [ -z "${public_file}" ]; then
        return 1
    fi

    if [ ! -f "${private_file}" ] || [ ! -f "${public_file}" ]; then
        fail "PASSPORT_*_KEY_FILE is set but the mounted secret file is missing."
    fi

    if passport_keys_exist_in_storage; then
        log "Passport keys already present in storage (skipping secret file import)."
        return 0
    fi

    log "Importing Passport keys from mounted secret files ..."
    cp "${private_file}" storage/oauth-private.key
    cp "${public_file}" storage/oauth-public.key
    chmod 600 storage/oauth-private.key
    chmod 644 storage/oauth-public.key
}

ensure_passport_keys() {
    if passport_keys_configured_via_env; then
        log "Passport keys loaded from environment (centralized secret)."
        return 0
    fi

    if import_passport_keys_from_secret_files; then
        return 0
    fi

    if passport_keys_exist_in_storage; then
        log "Passport keys already present in storage."
        return 0
    fi

    # First boot: only one instance generates; peers wait on the shared volume.
    lock_dir="storage/framework/passport-keys.lock"
    if mkdir "${lock_dir}" 2>/dev/null; then
        if ! passport_keys_exist_in_storage; then
            log "Generating Passport encryption keys (first run only) ..."
            php artisan passport:keys --no-interaction
        fi
        rmdir "${lock_dir}" 2>/dev/null || true
        return 0
    fi

    log "Waiting for another instance to finish Passport key generation ..."
    attempt=0
    while [ $attempt -lt 60 ]; do
        if passport_keys_exist_in_storage; then
            log "Passport keys available (created by peer instance)."
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 2
    done

    fail "Passport keys were not created. Set PASSPORT_PRIVATE_KEY/PASSPORT_PUBLIC_KEY or check storage permissions."
}

# ---------------------------------------------------------------------------
# Seed roles and the OAuth password-grant client (idempotent).
# ---------------------------------------------------------------------------
run_seeders() {
    log "Seeding roles and OAuth client ..."
    php artisan db:seed --force --no-interaction
}

# ---------------------------------------------------------------------------
# 4. Warm caches — dramatically reduces per-request latency
# ---------------------------------------------------------------------------
warm_caches() {
    log "Clearing stale caches ..."
    php artisan config:clear --no-interaction
    php artisan event:clear  --no-interaction
    php artisan route:clear  --no-interaction
    php artisan view:clear   --no-interaction

    log "Warming caches ..."
    php artisan config:cache --no-interaction
    php artisan event:cache  --no-interaction
    php artisan route:cache  --no-interaction
    php artisan view:cache   --no-interaction
}

# ---------------------------------------------------------------------------
# 5. Create storage symlink (idempotent)
# ---------------------------------------------------------------------------
create_storage_link() {
    if [ ! -L public/storage ]; then
        log "Creating storage symlink ..."
        php artisan storage:link --no-interaction
    fi
}

# =============================================================================
# Main
# =============================================================================
# Every role needs the database to be reachable before starting.
wait_for_mysql

# Only the primary application container mutates shared state (schema + caches).
# Worker containers skip these steps to avoid redundant work and migrate races.
if [ "${CONTAINER_ROLE}" = "app" ]; then
    # Purge stale bootstrap caches before ANY artisan command. A persisted
    # config.php built before optional packages (e.g. Scramble) were added will
    # crash provider boot with config('scramble') === null.
    rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php 2>/dev/null || true

    ensure_app_key
    php artisan config:clear --no-interaction
    refresh_package_manifest
    run_migrations
    ensure_passport_keys
    run_seeders
    warm_caches
    create_storage_link
else
    log "CONTAINER_ROLE=${CONTAINER_ROLE}: skipping migrations and cache warming."
fi

log "Initialisation complete (role: ${CONTAINER_ROLE}). Starting process ..."

# Replace this shell process with the CMD so signals are handled correctly
# by tini (defined as ENTRYPOINT in the Dockerfile).
exec "$@"
