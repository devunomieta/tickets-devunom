#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running Laravel database migrations..."
    php /var/www/html/artisan migrate --force
fi
