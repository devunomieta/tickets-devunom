#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running Laravel database migrations..."
    php /var/www/html/artisan migrate --force
fi

if [ -n "${SUPERADMIN_EMAIL}" ] && [ -n "${SUPERADMIN_PASSWORD}" ]; then
    echo "Ensuring Super Admin account..."
    php /var/www/html/artisan app:ensure-superadmin || true
fi

if [ "${SEED_DEMO}" = "true" ]; then
    echo "Seeding Hi.Events demo events..."
    php /var/www/html/artisan demo:seed --confirm \
        --email="${DEMO_EMAIL:-demo@cortdevs.com}" \
        --password="${DEMO_PASSWORD:-DemoPass123!}" || true
fi
