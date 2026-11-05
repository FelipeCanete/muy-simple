# syntax=docker/dockerfile:1
# Imagen de la API (Laravel sobre FrankenPHP). Contexto de build: raíz del repo.

FROM dunglas/frankenphp:1-php8.5-alpine AS base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ENV SERVER_NAME=":8000" \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# php.ini de producción + límites acordes al tamaño máximo de PDF (10 MB).
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf "expose_php=Off\nupload_max_filesize=10M\npost_max_size=12M\nmemory_limit=256M\n" \
      > "$PHP_INI_DIR/conf.d/zz-app.ini" \
 && adduser -D -u "${UID}" app \
 && chown -R app:app /data/caddy /config/caddy \
 # Escuchamos en :8000, así que el binario no necesita CAP_NET_BIND_SERVICE;
 # quitarla permite correr con cap_drop: ALL + no-new-privileges.
 && apk add --no-cache --virtual .caps libcap \
 && setcap -r /usr/local/bin/frankenphp \
 && apk del .caps

COPY docker/api/Caddyfile /etc/frankenphp/Caddyfile

# ---------------------------------------------------------------------------
# dev: incluye dependencias de desarrollo; se usa para correr los tests.
FROM base AS dev
COPY api/composer.json api/composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY api/ .
# Los valores de test vienen de phpunit.xml; el .env solo evita avisos de dotenv.
RUN cp .env.example .env \
 && composer dump-autoload --optimize \
 && php artisan package:discover \
 && chown -R app:app /app
USER app
CMD ["php", "artisan", "test"]

# ---------------------------------------------------------------------------
# prod: sin dependencias de desarrollo, autoload optimizado, usuario no-root.
FROM base AS prod
COPY api/composer.json api/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY api/ .
COPY docker/api/entrypoint.sh /usr/local/bin/entrypoint
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
 && php artisan package:discover \
 && mkdir -p database/data storage/app/private \
 && chmod +x /usr/local/bin/entrypoint \
 && chown -R app:app storage bootstrap/cache database \
 && rm /usr/bin/composer

USER app
EXPOSE 8000
HEALTHCHECK --interval=10s --timeout=3s --start-period=20s --retries=5 \
  CMD wget -qO- http://127.0.0.1:8000/up > /dev/null || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
