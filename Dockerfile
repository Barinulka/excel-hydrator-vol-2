FROM composer:2 AS composer

WORKDIR /app
ARG INSTALL_DEV=0

COPY composer.json composer.lock symfony.lock ./
RUN if [ "$INSTALL_DEV" = "1" ]; then \
        composer install \
            --prefer-dist \
            --no-interaction \
            --no-progress \
            --optimize-autoloader \
            --no-scripts; \
    else \
        composer install \
            --no-dev \
            --prefer-dist \
            --no-interaction \
            --no-progress \
            --optimize-autoloader \
            --no-scripts; \
    fi

COPY . .
RUN if [ "$INSTALL_DEV" = "1" ]; then \
        composer dump-autoload --optimize; \
    else \
        composer dump-autoload --classmap-authoritative --no-dev; \
    fi

FROM php:8.4-fpm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        intl \
        pdo_pgsql \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer /app /var/www/html

RUN mkdir -p var/cache var/log public/images/main excel/output \
    && chown -R www-data:www-data var public/images/main excel/output

ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN APP_SECRET=docker-build-secret \
    DATABASE_URL='postgresql://build:build@localhost:5432/build?serverVersion=16&charset=utf8' \
    EXCEL_HYDRATOR_URL='http://localhost:8080' \
    php bin/console assets:install public \
    && php bin/console asset-map:compile \
    && php bin/console ckeditor:install --tag=4.22.1 --clear=drop \
    && php bin/console cache:clear

FROM nginx:1.27-alpine AS nginx

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

FROM caddy:2-alpine AS caddy

COPY docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public
