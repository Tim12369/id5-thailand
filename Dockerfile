FROM php:8.2-apache AS base

RUN apt-get update && apt-get install -y \
        git unzip libpng-dev libjpeg-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql gd mbstring exif pcntl bcmath zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Apache: serve from public/, listen on $PORT (Render sets this at runtime)
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

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

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
