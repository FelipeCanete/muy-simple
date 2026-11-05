#!/bin/sh
# Prepara la API antes de levantar el servidor: valida configuración,
# crea la base SQLite si no existe, migra y siembra el usuario demo.
set -e

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY no está definida. Ejecuta 'make setup' en la raíz del repo." >&2
  exit 1
fi

if [ -z "$WEBHOOK_SECRET" ]; then
  echo "ERROR: WEBHOOK_SECRET no está definida. Ejecuta 'make setup' en la raíz del repo." >&2
  exit 1
fi

mkdir -p storage/app/private storage/framework/cache storage/framework/sessions \
         storage/framework/views storage/logs

DB_FILE="${DB_DATABASE:-/app/database/data/database.sqlite}"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan config:cache
php artisan route:cache

exec "$@"
