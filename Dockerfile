ARG PHP_VERSION=8.4
ARG NODE_VERSION=24

FROM php:${PHP_VERSION}-apache-bookworm AS php-base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APACHE_HTTP_PORT=8080

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        gosu \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libsqlite3-dev \
        libxml2-dev \
        libzip-dev; \
    docker-php-ext-install -j"$(nproc)" \
        dom \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        xmlreader \
        zip; \
    a2enmod rewrite; \
    sed -ri 's!Listen 80!Listen 8080!' /etc/apache2/ports.conf; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

FROM php-base AS composer-dependencies

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --classmap-authoritative \
    --no-ansi \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY . .

RUN composer dump-autoload \
        --classmap-authoritative \
        --no-ansi \
        --no-dev \
        --no-interaction; \
    composer check-platform-reqs --no-dev

FROM node:${NODE_VERSION}-bookworm-slim AS frontend-assets

WORKDIR /var/www/html

COPY package.json package-lock.json .npmrc ./

RUN npm ci

COPY --from=composer-dependencies /var/www/html/vendor ./vendor
COPY app ./app
COPY public ./public
COPY resources ./resources
COPY vite.config.js ./

RUN npm run build

FROM php-base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8080 \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database

COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY --from=composer-dependencies /var/www/html /var/www/html
COPY --from=frontend-assets /var/www/html/public/build /var/www/html/public/build
COPY docker-entrypoint.sh /usr/local/bin/bankflow-entrypoint

RUN set -eux; \
    cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    chmod +x /usr/local/bin/bankflow-entrypoint; \
    touch database/database.sqlite; \
    chown www-data:www-data database database/database.sqlite; \
    chown -R www-data:www-data storage bootstrap/cache; \
    chmod -R ug+rwX storage bootstrap/cache

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/health") === false ? 1 : 0);'

ENTRYPOINT ["bankflow-entrypoint"]

CMD ["apache2-foreground"]
