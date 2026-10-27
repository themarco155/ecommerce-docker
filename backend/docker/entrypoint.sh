#!/bin/sh
# Arranque del contenedor del backend:
#   1. Valida variables obligatorias
#   2. Genera las cachés de Laravel con las variables de entorno reales
#   3. Corre migraciones (y seeders la primera vez, si se pide)
#   4. Levanta PHP-FPM y Nginx
set -e
cd /var/www/html

for var in APP_KEY JWT_SECRET DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
  eval "value=\${$var:-}"
  if [ -z "$value" ]; then
    echo "ERROR: falta la variable $var (revisa tu archivo .env)" >&2
    exit 1
  fi
done

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  # Reintenta por si MySQL todavía está terminando de arrancar
  tries=0
  until php artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge 10 ]; then
      echo "ERROR: no se pudo conectar a la base de datos" >&2
      exit 1
    fi
    echo "Esperando a la base de datos... ($tries/10)"
    sleep 3
  done
fi

if [ "${SEED_DATABASE:-false}" = "true" ]; then
  # Solo siembra si la tabla de productos está vacía (evita duplicados al reiniciar)
  # (HOME y XDG_CONFIG_HOME en /tmp porque el usuario www-data no puede escribir en su carpeta)
  products=$(HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan tinker --execute='echo \App\Models\Product::count();' 2>/dev/null | tail -n 1)
  if [ "$products" = "0" ]; then
    echo "Sembrando datos de ejemplo..."
    php artisan db:seed --force
  fi
fi

php-fpm -D
exec nginx -e /dev/stderr -g 'daemon off;'
