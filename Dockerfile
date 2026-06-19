# =============================================================================
# Stage 1: PHP extension builder
# Builds and compiles all PHP extensions once. Nothing from this stage
# reaches production — only the compiled .so files are copied forward.
# =============================================================================
FROM php:8.4-fpm-alpine AS php-ext-builder

# Install build-time dependencies (discarded after this stage)
RUN apk add --no-cache \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
        pcntl \
    # phpredis (PECL) — Laravel uses Redis for cache, sessions and queues.
    # `yes ''` accepts the default answer for every optional-feature prompt.
    && yes '' | pecl install redis \
    && docker-php-ext-enable redis

# =============================================================================
# Stage 2: Composer dependency installer
# Runs composer in isolation so the composer binary and dev-packages never
# appear in the final image.
# =============================================================================
FROM composer:2 AS composer-builder

WORKDIR /app

# Copy dependency manifests first for better layer caching.
# Vendor directory is rebuilt only when composer.json / composer.lock change.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --classmap-authoritative

# Copy the full source so post-install scripts run in the correct context
COPY . .

# bootstrap/cache is excluded by .dockerignore, so recreate it before the
# autoload dump — package:discover (post-autoload-dump) writes packages.php here.
RUN mkdir -p bootstrap/cache \
    && composer dump-autoload \
        --optimize \
        --classmap-authoritative \
        --no-dev

# =============================================================================
# Stage 3: Production runtime image
# Minimal Alpine-based image — no build tools, no compiler, no composer.
# =============================================================================
FROM php:8.4-fpm-alpine AS production

LABEL maintainer="ecommerce-auth-service" \
      org.opencontainers.image.title="ecommerce-auth-service" \
      org.opencontainers.image.description="Laravel PHP-FPM service" \
      org.opencontainers.image.base.name="php:8.4-fpm-alpine"

# ---------------------------------------------------------------------------
# Runtime shared libraries (no headers, no compilers)
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
        libpng \
        libjpeg-turbo \
        freetype \
        icu-libs \
        libzip \
        oniguruma \
        # Used by the entrypoint wait-for-db helper
        mysql-client \
        # Provides cgi-fcgi for a real FPM liveness probe (/fpm-ping)
        fcgi \
        # Allows graceful signal forwarding to php-fpm child processes
        tini

# ---------------------------------------------------------------------------
# Copy compiled PHP extensions from builder stage
# ---------------------------------------------------------------------------
COPY --from=php-ext-builder \
        /usr/local/lib/php/extensions/ \
        /usr/local/lib/php/extensions/

COPY --from=php-ext-builder \
        /usr/local/etc/php/conf.d/ \
        /usr/local/etc/php/conf.d/

# ---------------------------------------------------------------------------
# PHP configuration: production hardened
# ---------------------------------------------------------------------------
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/php.ini         $PHP_INI_DIR/conf.d/99-app.ini
COPY docker/php/opcache.ini     $PHP_INI_DIR/conf.d/10-opcache.ini
COPY docker/php/php-fpm.conf    /usr/local/etc/php-fpm.d/zz-app.conf

# ---------------------------------------------------------------------------
# Application source
# ---------------------------------------------------------------------------
WORKDIR /var/www/html

COPY --from=composer-builder --chown=www-data:www-data /app/vendor  ./vendor
COPY --chown=www-data:www-data . .

# Bake the package manifest generated during the composer stage. Worker
# containers (queue/scheduler) run on a read-only filesystem and must not need
# to regenerate it at boot; the app container overlays a writable volume here.
COPY --from=composer-builder --chown=www-data:www-data /app/bootstrap/cache ./bootstrap/cache

# ---------------------------------------------------------------------------
# Filesystem permissions — principle of least privilege
# The www-data user owns only what it must write to at runtime.
# All source files remain readable but not writable.
# ---------------------------------------------------------------------------
# Explicit paths: Alpine's /bin/sh (busybox) does not support brace expansion.
RUN mkdir -p storage/framework/sessions \
             storage/framework/views \
             storage/framework/cache \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data \
             storage \
             bootstrap/cache \
    && chmod -R 750 storage bootstrap/cache \
    # Ensure application files are not world-writable
    && find /var/www/html -type f -exec chmod 640 {} \; \
    && find /var/www/html -type d -exec chmod 750 {} \; \
    # Restore write permission for runtime directories
    && chmod -R u+w storage bootstrap/cache \
    # Pre-create the public/storage symlink at build time. The runtime root
    # filesystem is read-only, so the entrypoint cannot create it later.
    && ln -sf /var/www/html/storage/app/public public/storage

# ---------------------------------------------------------------------------
# Entrypoint
# ---------------------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Drop to unprivileged user for all subsequent commands
USER www-data

EXPOSE 9000

# tini is a minimal init that properly reaps zombie processes and forwards
# signals (SIGTERM → graceful shutdown) to php-fpm.
ENTRYPOINT ["/sbin/tini", "--", "docker-entrypoint"]
CMD ["php-fpm"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD SCRIPT_NAME=/fpm-ping SCRIPT_FILENAME=/fpm-ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

# =============================================================================
# Stage 4: Nginx runtime image
# Serves static assets directly and reverse-proxies dynamic requests to FPM.
# The public/ directory is baked into the image so the assets are always in
# sync with the released code — no fragile named-volume seeding.
# =============================================================================
FROM nginx:1.27-alpine AS nginx

LABEL maintainer="ecommerce-auth-service" \
      org.opencontainers.image.title="ecommerce-auth-service-nginx" \
      org.opencontainers.image.description="Nginx reverse proxy for the Laravel service" \
      org.opencontainers.image.base.name="nginx:1.27-alpine"

# Server-wide and server-block configuration
COPY docker/nginx/nginx.conf      /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf

# Baked-in public assets (index.php front controller, robots.txt, etc.)
COPY --from=composer-builder /app/public /var/www/html/public

# Pre-create the storage symlink so user-uploaded files served from the
# app-storage volume (mounted read-only at runtime) resolve correctly.
RUN ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD wget --quiet --spider --tries=1 http://localhost/health || exit 1

# =============================================================================
# Stage 5: Redis runtime image
# Bakes the config and a secure entrypoint that injects the password at runtime
# (kept out of the image, version control, and the process list).
# =============================================================================
FROM redis:7-alpine AS redis

LABEL maintainer="ecommerce-auth-service" \
      org.opencontainers.image.title="ecommerce-auth-service-redis" \
      org.opencontainers.image.description="Hardened Redis cache/queue broker" \
      org.opencontainers.image.base.name="redis:7-alpine"

COPY docker/redis/redis.conf     /etc/redis/redis.conf
COPY docker/redis/entrypoint.sh  /usr/local/bin/redis-secure-entrypoint
RUN chmod +x /usr/local/bin/redis-secure-entrypoint

ENTRYPOINT ["redis-secure-entrypoint"]
CMD ["redis-server", "/etc/redis/redis.conf"]