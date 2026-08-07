#!/usr/bin/env bash
set -e

# Render assigns the listen port via $PORT; default to 80 for local docker run.
: "${PORT:=80}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

exec apache2-foreground
