#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi
php artisan migrate --force
php artisan acopio:setup-oauth
php artisan route:cache
php artisan view:cache
exec supervisord -c /etc/supervisord.conf
