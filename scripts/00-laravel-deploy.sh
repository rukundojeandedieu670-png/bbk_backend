#!/bin/sh

set -eu

if [ -n "${PORT:-}" ]; then
    sed -i "s/listen   80;/listen   ${PORT};/" /etc/nginx/sites-available/default.conf
    sed -i "s/listen   \[::\]:80/listen   [::]:${PORT}/" /etc/nginx/sites-available/default.conf
fi

composer install --no-dev --working-dir=/var/www/html --optimize-autoloader

mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan migrate --force
