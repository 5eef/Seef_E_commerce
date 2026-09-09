#!/bin/sh
set -eu

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
