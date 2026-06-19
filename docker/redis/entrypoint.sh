#!/bin/sh
# =============================================================================
# redis entrypoint — injects the password at runtime without exposing it.
#
# Why this exists:
#   • Passing --requirepass on the command line leaks the secret into
#     `docker inspect` and the process list (`ps`).
#   • Hard-coding it in the version-controlled redis.conf leaks it into git.
#
# This script writes the password into a 0600 config file on a tmpfs mount
# (/tmp). The baked redis.conf pulls it in via `include /tmp/redis-runtime.conf`.
# It then delegates to the stock redis docker-entrypoint, which performs the
# data-dir chown and drops privileges to the redis user (via setpriv).
# =============================================================================
set -eu

: "${REDIS_PASSWORD:?REDIS_PASSWORD must be provided to the redis container}"

RUNTIME_CONF="/tmp/redis-runtime.conf"

umask 077
printf 'requirepass %s\n' "${REDIS_PASSWORD}" > "${RUNTIME_CONF}"
chown redis:redis "${RUNTIME_CONF}" 2>/dev/null || true

# "$@" is "redis-server /etc/redis/redis.conf" (from CMD). The stock entrypoint
# handles the /data chown and privilege drop to the redis user.
exec /usr/local/bin/docker-entrypoint.sh "$@"
