#!/bin/sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "${1:-}" = "php-fpm" ]; then
  echo "[Conectate TV] Waiting for PostgreSQL..."
  ATTEMPTS=0
  until php -r '
    try {
      $dsn = "pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE");
      new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
      exit(0);
    } catch (Throwable $e) { exit(1); }
  '; do
    ATTEMPTS=$((ATTEMPTS+1))
    if [ "$ATTEMPTS" -gt 60 ]; then
      echo "[Conectate TV] PostgreSQL timeout"
      exit 1
    fi
    sleep 2
  done

  php artisan migrate --force
  php artisan astra:sync || true
fi

exec "$@"
