# =============================================================================
# Nginx reverse proxy — standalone image for ECR (microservices/nginx)
#
# Build from repository root:
#   docker build -f docker/nginx/Dockerfile \
#     -t 975049961565.dkr.ecr.us-east-1.amazonaws.com/microservices/nginx:latest .
#
# Or: make build-nginx
# =============================================================================
FROM nginx:1.27-alpine

LABEL maintainer="ecommerce-auth-service" \
      org.opencontainers.image.title="ecommerce-auth-service-nginx" \
      org.opencontainers.image.description="Nginx reverse proxy for the Laravel auth service" \
      org.opencontainers.image.base.name="nginx:1.27-alpine"

COPY docker/nginx/nginx.conf      /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf

# Static front controller and assets (must match the app release tag).
COPY public/ /var/www/html/public

# Resolves user uploads when app-storage is mounted read-only at runtime.
RUN ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=15s --retries=3 \
    CMD wget --quiet --spider --tries=1 http://localhost/health || exit 1
