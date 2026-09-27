#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    attempts=0
    until php artisan migrate --force; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 10 ]; then
            echo "Database migrations failed after $attempts attempts." >&2
            exit 1
        fi
        echo "Database is not ready; retrying migration in 3 seconds."
        sleep 3
    done
fi

exec apache2-foreground
