#!/bin/sh
set -e

echo "Waiting for database to become available..."
TRIES=0
MAX_TRIES=30
while ! mysql -h"${DB_HOST}" -u"${DB_USERNAME}" --password="${DB_PASSWORD}" -e "SELECT 1" "${DB_DATABASE}" >/dev/null 2>&1; do
  TRIES=$((TRIES+1))
  if [ "$TRIES" -ge "$MAX_TRIES" ]; then
    echo "Database not available after ${MAX_TRIES} attempts. Exiting."
    exit 1
  fi
  sleep 2
done

echo "Database reachable. Attempting to acquire migration lock..."
ACQUIRED=$(mysql -h"${DB_HOST}" -u"${DB_USERNAME}" --password="${DB_PASSWORD}" -N -s -e "SELECT GET_LOCK('jedligram_migrate_lock',10);" "${DB_DATABASE}" 2>/dev/null || echo 0)

if [ "${ACQUIRED}" = "1" ]; then
  echo "Migration lock acquired. Running migrations..."
  php artisan migrate --force || {
    echo "Migrations failed." >&2
    # Release lock in case of failure to avoid deadlocks
    mysql -h"${DB_HOST}" -u"${DB_USERNAME}" --password="${DB_PASSWORD}" -N -s -e "SELECT RELEASE_LOCK('jedligram_migrate_lock');" "${DB_DATABASE}" || true
    exit 1
  }
  echo "Migrations completed. Releasing lock..."
  mysql -h"${DB_HOST}" -u"${DB_USERNAME}" --password="${DB_PASSWORD}" -N -s -e "SELECT RELEASE_LOCK('jedligram_migrate_lock');" "${DB_DATABASE}" || true
else
  echo "Could not acquire migration lock; another instance will handle migrations or they already ran."
fi

echo "Optimizing laravel application..."
RUN php artisan storage:link
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting container process..."
exec "php artisan serve --host=0.0.0.0 --port=8000"
