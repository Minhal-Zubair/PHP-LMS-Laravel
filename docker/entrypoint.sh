#!/bin/sh
set -e

: "${PORT:=10000}"
export PORT

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-enabled/default

cd /var/www/laravel
php artisan config:clear
php artisan config:cache
php artisan route:cache

exec "$@"
