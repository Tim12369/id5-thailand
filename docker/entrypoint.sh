#!/usr/bin/env bash
set -e

# Render assigns the listen port via $PORT; default to 80 for local docker run.
: "${PORT:=80}"
sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# ── The editors' WordPress lives in this same container, at /wp ─────────────
# Everything that connects the two is worked out here, so nothing has to be
# typed into the hosting panel: the addresses follow from APP_URL, and the
# secrets they share are derived from the application key the site already
# has. Any of them can still be set explicitly in the environment instead.
: "${APP_URL:=${RENDER_EXTERNAL_URL:-http://localhost}}"
export APP_URL

derive() { php -r 'echo substr(hash_hmac("sha256", $argv[1], (string) getenv("APP_KEY")) . hash_hmac("sha256", $argv[1] . "/2", (string) getenv("APP_KEY")), 0, (int) $argv[2]);' "$1" "$2"; }

export CMS_ENABLED="${CMS_ENABLED:-true}"
export CMS_WORDPRESS_URL="${CMS_WORDPRESS_URL:-${APP_URL%/}/wp}"
# The website talks to WordPress over the local web server, not out to the
# public address and back (which, while a new version starts, is still the old one).
export CMS_INTERNAL_URL="${CMS_INTERNAL_URL:-http://127.0.0.1:${PORT}/wp}"
export CMS_WORDPRESS_USER="${CMS_WORDPRESS_USER:-id5-sync}"
export CMS_WORDPRESS_APP_PASSWORD="${CMS_WORDPRESS_APP_PASSWORD:-$(derive id5-sync-app-password 24)}"
export CMS_WEBHOOK_SECRET="${CMS_WEBHOOK_SECRET:-$(derive id5-webhook 48)}"
# This host's disk is wiped on every restart: pictures copied from WordPress
# are also kept in the database.
export CMS_MEDIA_PERSIST="${CMS_MEDIA_PERSIST:-true}"
# The database was filled with sample data before WordPress existed; a full
# sync retires the rows no WordPress item owns, so nothing appears twice.
export CMS_RETIRE_UNMANAGED="${CMS_RETIRE_UNMANAGED:-true}"

php artisan config:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

# ── WordPress: bring its database back, then start it ───────────────────────
# With that user's own home: the database client looks there for certificates,
# and must not be sent to root's, which it may not read.
as_www() { HOME=/var/www runuser -u www-data -- "$@"; }

# The commands above ran as root; the web server must own what they made.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Put back the pictures mirrored from WordPress from their database copies.
as_www php artisan cms:media:restore || true

DATA=/var/www/wordpress-data
mkdir -p "$DATA" /var/www/wordpress/wp-content/uploads
chown -R www-data:www-data "$DATA" /var/www/wordpress/wp-content/uploads
rm -f "$DATA/.offline" "$DATA/.just-created" "$DATA/.stopping"

SYNC_PID=""
set +e
as_www php /usr/local/share/id5/sqlite-sync.php restore
restored=$?
set -e

if [ "$restored" = "0" ] || [ "$restored" = "3" ]; then
    # 0: the saved site is back. 3: nothing saved yet — boot.php creates the
    # site from the seed (the first start ever).
    if as_www php /usr/local/share/id5/boot.php; then
        # Kept running: if it ever dies (this is a small instance), nothing
        # would be saved until the next restart. It is started directly, with
        # no wrapper process, so the stop signal below reaches it and it can
        # save one last time. Exit 9 means it stepped aside for a newer
        # instance on purpose.
        (
            set +e   # a failing run must lead to the next one, not end the loop
            trap '' TERM INT WINCH
            while [ ! -f "$DATA/.stopping" ]; do
                HOME=/var/www setpriv --reuid=www-data --regid=www-data --init-groups php /usr/local/share/id5/sqlite-sync.php daemon
                code=$?
                { [ "$code" = "9" ] || [ -f "$DATA/.stopping" ]; } && break
                echo "[id5] the saving process stopped (exit $code); starting it again"
                sleep 3
            done
        ) &
        SYNC_PID=$!
    else
        echo "[id5] WordPress could not be started; it stays offline until the next restart. The public website is not affected."
        touch "$DATA/.offline"
    fi
else
    # The saved site exists but could not be read. Starting an empty one in
    # its place would overwrite it, so WordPress stays offline instead.
    echo "[id5] WordPress's saved database could not be read; it stays offline until the next restart. The public website is not affected."
    touch "$DATA/.offline"
fi

# The first time the site exists, have the website fetch everything from it —
# once the web server is answering. Later changes arrive by themselves.
if [ -f "$DATA/.just-created" ]; then
    (
        for i in $(seq 1 60); do
            curl -fsS -o /dev/null -m 5 "http://127.0.0.1:${PORT}/wp/wp-json/id5/v1/health" && break
            sleep 2
        done
        echo "[id5] first sync: fetching everything from WordPress"
        as_www php artisan cms:pull || echo "[id5] first sync did not finish; use the button in WordPress to run it again."
    ) &
fi

# Stopping: let the web server finish, then save WordPress's last changes.
shutdown() {
    # From here on nothing may cut this short: not a failing command, not a
    # second signal.
    set +e
    trap '' TERM INT WINCH

    # WINCH asks Apache to finish the requests in hand and stop.
    kill -WINCH "$APACHE_PID" 2>/dev/null || true
    for i in $(seq 1 16); do
        kill -0 "$APACHE_PID" 2>/dev/null || break
        sleep 0.5
    done
    kill -TERM "$APACHE_PID" 2>/dev/null || true

    if [ -n "$SYNC_PID" ]; then
        touch "$DATA/.stopping"
        pkill -TERM -u www-data -f 'sqlite-sync.php daemon' 2>/dev/null || true
        wait "$SYNC_PID" 2>/dev/null || true
    fi
    exit 0
}
# WINCH too: it is the stop signal the base image declares for Apache.
trap shutdown TERM INT WINCH

# In a session of its own: when Apache stops it signals its whole process
# group, which would otherwise take this script down before the last save.
setsid apache2-foreground &
APACHE_PID=$!
wait "$APACHE_PID"
