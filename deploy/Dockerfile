# --- assets build (Laravel Mix 6 / Vue 2) ---
FROM node:16-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY webpack.mix.js ./
COPY resources ./resources
COPY public ./public
ARG ENV_FILE=.env.build
COPY ${ENV_FILE} ./.env
ENV ENV_FILE=.env
RUN npx mix --production

# --- PHP deps ---
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs --prefer-dist

# --- runtime (本番XREAと同じ PHP 8.1) ---
FROM php:8.1-apache
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev unzip \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install pdo_mysql gd zip mbstring bcmath opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/js ./public/js
COPY --from=assets /app/public/css ./public/css
COPY --from=assets /app/public/mix-manifest.json ./public/mix-manifest.json
RUN composer dump-autoload --no-dev --optimize \
 && chown -R www-data:www-data storage bootstrap/cache
