# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1: front-end build (Tailwind CSS + Vite)
# ---------------------------------------------------------------------------
FROM node:22-bookworm-slim AS frontend-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources/ resources/
# Vite reads composer-installed vendor paths (e.g. Pagination views) via
# @source in app.css, but does not need vendor/ present to build — only the
# listed paths are globbed and missing ones are simply skipped.
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2: PHP dependencies (composer, production only)
# ---------------------------------------------------------------------------
FROM composer:2 AS composer-build

WORKDIR /app

# Full application source is required here because Laravel's composer.json
# runs `artisan package:discover` as a post-autoload-dump script, which
# needs bootstrap/app.php and the app/ tree to be present.
COPY . .
RUN composer install \
        --no-dev \
        --prefer-dist \
        --optimize-autoloader \
        --no-interaction \
        --no-ansi \
        --no-progress \
    && composer clear-cache

# ---------------------------------------------------------------------------
# Stage 3: production runtime image — PHP-FPM (app / queue / scheduler)
#
# This is the one image reused, unmodified, by the app, queue and scheduler
# compose services — only the `command:` differs between them.
# ---------------------------------------------------------------------------
FROM php:8.5-fpm-bookworm AS app

ARG APP_UID=1000
ARG APP_GID=1000

# Dev packages (-dev) are kept rather than purged after the extension build:
# purging them with --auto-remove risks apt also removing the runtime .so
# libraries the compiled extensions link against, since Debian package names
# for the runtime libs vary by release. Trading a larger image for a build
# that doesn't silently break gd/zip/intl at runtime.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Reuse the existing www-data user/group but align its ids with the host
# deploy user so the bind-mounted video storage (owned by that host user)
# stays writable without opening up world-writable permissions.
RUN usermod -u ${APP_UID} www-data \
    && groupmod -g ${APP_GID} www-data

WORKDIR /var/www/html

# Application source (excludes anything matched by .dockerignore: .env,
# node_modules, vendor, storage/app/* contents, tests, etc.)
COPY --chown=www-data:www-data . .

# Production PHP dependencies built in the composer-build stage.
COPY --from=composer-build --chown=www-data:www-data /app/vendor/ vendor/

# Compiled Tailwind/Vite assets. Never contains uploaded/generated content —
# this is build output only, not the persistent video storage.
COPY --from=frontend-build --chown=www-data:www-data /app/public/build/ public/build/

# Recreate the persistent-storage directory skeleton so a bind mount over
# storage/app has somewhere to attach at container start, and pre-create the
# public symlink. The actual video/public/private files live on the
# bind-mounted host volume, never inside the image.
RUN php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# Stage 4: production runtime image — Nginx
#
# A separate image from `app`, built from the same source in the same CI
# run, so nginx always serves the exact static assets that shipped with a
# given app image tag. It bakes in only build OUTPUT (public/build, and the
# handful of static files public/ ships) — never index.php, never uploaded
# content, and never the persistent video storage. Server/domain/TLS
# configuration is intentionally NOT baked in here — it's mounted from the
# EC2 host (docker/nginx/*) so the domain can be changed without a rebuild.
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS nginx

COPY --from=frontend-build /app/public/build/ /var/www/html/public/build/
COPY --from=app /var/www/html/public/favicon.ico /var/www/html/public/favicon.ico
COPY --from=app /var/www/html/public/robots.txt /var/www/html/public/robots.txt
# The storage:link symlink itself (public/storage -> ../storage/app/public).
# Resolving it requires storage/app to be bind-mounted into this container
# too (see compose.yaml) — same persistent volume the app/queue/scheduler
# containers use, mounted read-only here since nginx only ever reads it.
COPY --from=app /var/www/html/public/storage /var/www/html/public/storage

EXPOSE 80 443
