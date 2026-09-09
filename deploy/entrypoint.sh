#!/bin/sh
set -eu

case "${APP_KEY:-}" in
    base64:*) ;;
    "") export APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')" ;;
    *) export APP_KEY="base64:${APP_KEY}" ;;
esac

if [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ] && [ -z "${APP_URL:-}" ]; then
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
fi

if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    database_file="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$database_file")"
    touch "$database_file"
    chown www-data:www-data "$database_file"
fi

php artisan config:clear
php artisan migrate --force

if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan view:cache

exec "$@"
