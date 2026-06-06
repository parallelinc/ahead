#!/bin/sh
set -e

if [ -n "$APP_KEY" ]; then
	php artisan config:cache --no-interaction
	php artisan route:cache --no-interaction
	php artisan view:cache --no-interaction
fi

chown -R www-data:www-data storage bootstrap/cache

php-fpm -D

exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
