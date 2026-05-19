# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Stage 1: Frontend (Vite + Vue)
# -----------------------------------------------------------------------------
FROM node:20-bookworm-slim AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# -----------------------------------------------------------------------------
# Stage 2: PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /build

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# -----------------------------------------------------------------------------
# Stage 3: Application (Nginx + PHP-FPM)
# -----------------------------------------------------------------------------
FROM php:8.3-fpm-bookworm

LABEL org.opencontainers.image.title="UTC-eLibrary"

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    libonig-dev \
    qpdf \
    ghostscript \
    poppler-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        mbstring \
        xml \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-utc-elibrary.ini
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/utc-elibrary.conf

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /build/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /build/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
