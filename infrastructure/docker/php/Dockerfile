# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Base: PHP extensions and system libraries shared by every later stage.
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        postgresql-libs \
        icu-libs \
        libzip \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        intl \
        zip \
        opcache \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apk del .build-deps

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Vendor: dependencies resolved in their own layer so that a source-only change
# does not invalidate the Composer cache.
# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./
COPY packages/Contracts/composer.json ./packages/Contracts/
COPY packages/Shared/composer.json    ./packages/Shared/
COPY packages/Database/composer.json  ./packages/Database/
COPY packages/Security/composer.json  ./packages/Security/
COPY apps/api/composer.json           ./apps/api/

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# ---------------------------------------------------------------------------
# Production runtime.
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && rm -rf /usr/bin/composer /root/.composer \
    # storage/ is the only writable path; everything else can stay read-only.
    && mkdir -p apps/api/storage/logs apps/api/storage/cache apps/api/storage/tmp \
    && chown -R www-data:www-data apps/api/storage \
    && chmod -R 755 /var/www/html

COPY infrastructure/docker/php/php.ini      /usr/local/etc/php/conf.d/zz-yume.ini
COPY infrastructure/docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-yume.conf

# Never run the application as root: a PHP RCE then owns only www-data.
USER www-data

EXPOSE 9000

# cgi-fcgi is not present in the slim image, so liveness is proven by asking the
# FPM master whether its pool is up.
HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 9000) ? 0 : 1);'

CMD ["php-fpm", "-F"]

# ---------------------------------------------------------------------------
# Development: adds Xdebug and dev dependencies, keeps the source bind-mounted.
# ---------------------------------------------------------------------------
FROM base AS development

ENV APP_ENV=local \
    APP_DEBUG=true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-deps

COPY infrastructure/docker/php/php.ini     /usr/local/etc/php/conf.d/zz-yume.ini
COPY infrastructure/docker/php/php.dev.ini /usr/local/etc/php/conf.d/zzz-yume-dev.ini

CMD ["php-fpm", "-F"]
