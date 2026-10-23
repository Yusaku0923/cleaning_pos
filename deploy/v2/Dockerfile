# v2 (Laravel 12) の開発・テスト用。XREA と同じ PHP 8.3。Node は XREA に入れるのと同じ 20.18.1 (Vite のビルド用)。
FROM node:20.18.1-bookworm-slim AS node
FROM php:8.3-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev unzip git mariadb-client \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install pdo_mysql gd zip mbstring bcmath \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
WORKDIR /app
