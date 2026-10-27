# syntax=docker/dockerfile:1
# ============================================================
#  Backend API Laravel 12 — imagen de producción multi-stage
#  base    → PHP 8.2 FPM (Alpine) + extensiones necesarias
#  vendor  → Composer instala dependencias (sin dev) y genera el autoload
#  runtime → imagen final: PHP-FPM + Nginx, código + SOLO vendor/ del build
# ============================================================

ARG PHP_VERSION=8.2

# ---------- Base: PHP-FPM + extensiones ----------
FROM php:${PHP_VERSION}-fpm-alpine AS base

# mbstring, openssl, tokenizer, xml, ctype y json ya vienen compiladas en la imagen oficial.
# Se compilan pdo_mysql y opcache, y se borran las herramientas de compilación en la MISMA capa.
RUN set -eux; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql opcache; \
    apk del .build-deps; \
    rm -rf /tmp/* /usr/src/php*; \
    for ext in pdo_mysql mbstring openssl tokenizer xml ctype json curl dom fileinfo "Zend OPcache"; do \
      php -m | grep -qix "$ext" || { echo "Falta la extension $ext"; exit 1; }; \
    done

# ---------- Dependencias de Composer (etapa de build) ----------
FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN apk add --no-cache git unzip
WORKDIR /app

# 1) Solo composer.json/lock → esta capa se reutiliza mientras no cambien las dependencias
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-interaction --no-progress --prefer-dist \
                     --no-scripts --no-autoloader

# 2) Código de la app → autoload optimizado (classmap) para producción
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------- Runtime ----------
FROM base AS runtime

# Nginx como proxy inverso delante de PHP-FPM, en el mismo contenedor
RUN apk add --no-cache nginx \
 && mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

COPY docker/php/php.ini      /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf     /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint

WORKDIR /var/www/html

# Código de la aplicación (sin vendor, sin .env, sin tests: ver .dockerignore)
COPY . .
# De la etapa de build se copia ÚNICAMENTE vendor/
COPY --from=vendor /app/vendor ./vendor

# El código queda de root (solo lectura para la app);
# solo storage/ y bootstrap/cache/ son escribibles por www-data
RUN set -eux; \
    chmod +x /usr/local/bin/entrypoint; \
    mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
             storage/logs bootstrap/cache; \
    php artisan package:discover --ansi; \
    chown -R www-data:www-data storage bootstrap/cache; \
    rm -rf docker

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

# Contenedor sin root: Nginx escucha en 8000 (puerto no privilegiado)
USER www-data
EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8000/up > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
