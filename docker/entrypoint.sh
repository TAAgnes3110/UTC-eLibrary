#!/bin/sh
set -e

cd /var/www/html

if [ -f artisan ]; then
    php docker/wait-mysql.php

    if [ -z "${APP_KEY:-}" ] || [ "${APP_KEY}" = "" ]; then
        echo "APP_KEY trống — chạy: docker compose exec app php artisan key:generate"
    fi

    php artisan storage:link --force 2>/dev/null || true

    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        php artisan migrate --force --no-interaction || true
    fi

    if [ -n "${APP_KEY:-}" ] && [ "${APP_ENV:-local}" = "production" ]; then
        php artisan config:cache --no-interaction || true
        php artisan route:clear --no-interaction || true
        php artisan view:cache --no-interaction || true
    fi
fi

exec "$@"
