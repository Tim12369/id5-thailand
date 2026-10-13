FROM php:8.2-apache AS base

RUN apt-get update && apt-get install -y \
        git unzip libpng-dev libjpeg-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql gd mbstring exif pcntl bcmath zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── WordPress for the editors, served from this same container at /wp ───────
# (docs/free-hosting.md). It runs on SQLite, so it needs no database server of
# its own; the Thai language pack is included because nothing can be installed
# from the dashboard on a host whose disk does not last. 5.8.18 is the newest
# Thai pack wordpress.org publishes.
ARG WORDPRESS_VERSION=7.1.3
ARG SQLITE_INTEGRATION_VERSION=3.1.0
RUN mkdir -p /var/www/wordpress /var/www/wordpress-data \
    && curl -fsSL https://wordpress.org/wordpress-${WORDPRESS_VERSION}.tar.gz | tar -xz --strip-components=1 -C /var/www/wordpress \
    && rm -rf /var/www/wordpress/wp-content/plugins/akismet /var/www/wordpress/wp-content/plugins/hello.php \
    && curl -fsSL -o /tmp/sqlite.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.${SQLITE_INTEGRATION_VERSION}.zip \
    && unzip -q /tmp/sqlite.zip -d /var/www/wordpress/wp-content/plugins \
    && cp /var/www/wordpress/wp-content/plugins/sqlite-database-integration/db.copy /var/www/wordpress/wp-content/db.php \
    && sed -i "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#/var/www/wordpress/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" /var/www/wordpress/wp-content/db.php \
    && curl -fsSL -o /tmp/th.zip https://downloads.wordpress.org/translation/core/5.8.18/th.zip \
    && mkdir -p /var/www/wordpress/wp-content/languages /var/www/wordpress/wp-content/uploads /var/www/wordpress/wp-content/mu-plugins \
    && unzip -q -o /tmp/th.zip -d /var/www/wordpress/wp-content/languages \
    && rm /tmp/sqlite.zip /tmp/th.zip

WORKDIR /var/www/html

# Apache: serve from public/, listen on $PORT (Render sets this at runtime)
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/wordpress/php.ini /usr/local/etc/php/conf.d/zz-id5.ini

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# Frontend build (Node, only needed at build time)
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM base AS final
COPY --from=assets /app/public/build /var/www/html/public/build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# WordPress: this project's plugin, configuration and starting content.
RUN cp -r /var/www/html/wordpress/plugins/id5-cms /var/www/wordpress/wp-content/plugins/id5-cms \
    && cp /var/www/html/docker/wordpress/mu-plugins/*.php /var/www/wordpress/wp-content/mu-plugins/ \
    && cp /var/www/html/docker/wordpress/wp-config.php /var/www/wordpress/wp-config.php \
    && cp /var/www/html/docker/wordpress/htaccess /var/www/wordpress/.htaccess \
    && mkdir -p /usr/local/share/id5 \
    && cp /var/www/html/docker/wordpress/boot.php /var/www/html/docker/wordpress/sqlite-sync.php /var/www/html/docker/wordpress/maintainer-public.pem /usr/local/share/id5/ \
    && cp -r /var/www/html/docker/wordpress/seed /usr/local/share/id5/seed \
    && chown -R www-data:www-data /var/www/wordpress /var/www/wordpress-data

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# The start-up script handles stopping itself (it saves WordPress's last
# changes first); the base image would otherwise signal Apache directly.
STOPSIGNAL SIGTERM

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
