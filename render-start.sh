#!/bin/sh
set -eu
cd /var/www/html

: "${APP_KEY:?Set APP_KEY in Render environment variables}"
: "${DB_DATABASE:=/var/www/html/database/database.sqlite}"
: "${PORT:=10000}"
export DB_DATABASE
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:$PORT}}"

# Render generates a base64-encoded 32-byte secret; Laravel requires the prefix.
case "$APP_KEY" in
    base64:*) ;;
    *) export APP_KEY="base64:$APP_KEY" ;;
esac

mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/\*:10000/*:$PORT/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear --no-interaction
php artisan app:install --no-interaction
php artisan app:provision-administrator --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
chown -R www-data:www-data storage bootstrap/cache database

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/application.conf
