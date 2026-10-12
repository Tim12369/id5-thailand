#!/usr/bin/env bash
set -e

# The host assigns the listen port via $PORT; default to 80 for a local run.
: "${PORT:=80}"
sed -ri "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Let the official image lay WordPress out in /var/www/html (it does so when
# asked to start Apache); "apache2ctl -v" makes it stop there and return.
docker-entrypoint.sh apache2ctl -v >/dev/null

# Wait for the database, apply settings from the environment, and put the
# uploaded files back on this host's freshly wiped disk. Never fatal.
php /usr/local/share/id5/boot.php || echo "[id5] start-up tasks failed; starting WordPress anyway"
chown -R www-data:www-data /var/www/html/wp-content/uploads 2>/dev/null || true

exec apache2-foreground
