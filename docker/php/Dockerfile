FROM composer:2.8 AS builder

WORKDIR /build
RUN composer create-project laravel/laravel:^13.0 app --prefer-dist --no-interaction
WORKDIR /build/app
RUN composer require predis/predis:^3.0 --no-interaction
COPY overlay/ /build/app/
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl libpq-dev libicu-dev libzip-dev libxml2-dev unzip \
    && docker-php-ext-install pdo_pgsql intl bcmath opcache xmlreader \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY --from=builder /build/app /var/www/html
COPY docker/php/entrypoint.sh /usr/local/bin/conectate-entrypoint

RUN chmod +x /usr/local/bin/conectate-entrypoint \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["conectate-entrypoint"]
CMD ["php-fpm"]
