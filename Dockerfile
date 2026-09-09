# syntax=docker/dockerfile:1

FROM node:22-alpine AS frontend-build
WORKDIR /build/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci
COPY frontend/ ./
ARG VITE_API_URL=/api
ENV VITE_API_URL=${VITE_API_URL}
RUN npm run build

FROM composer:2 AS php-dependencies
WORKDIR /build/backend
COPY backend/composer.json backend/composer.lock ./
RUN set -e; \
    for wait_seconds in 0 15 30; do \
        if [ "$wait_seconds" -gt 0 ]; then sleep "$wait_seconds"; fi; \
        if composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts; then exit 0; fi; \
    done; \
    exit 1

FROM php:8.4-apache AS runtime
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=8000

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath mbstring opcache pdo_mysql pdo_sqlite \
    && a2enmod rewrite headers \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's!Listen 80!Listen 8000!g' /etc/apache2/ports.conf \
    && sed -ri -e 's!<VirtualHost \*:80>!<VirtualHost *:8000>!g' /etc/apache2/sites-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY backend/ ./
COPY --from=php-dependencies /build/backend/vendor ./vendor
COPY --from=frontend-build /build/frontend/dist ./public
COPY deploy/entrypoint.sh /usr/local/bin/seef-entrypoint

RUN chmod +x /usr/local/bin/seef-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 8000
ENTRYPOINT ["seef-entrypoint"]
CMD ["apache2-foreground"]
