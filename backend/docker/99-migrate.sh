#!/bin/sh
set -e

if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running Laravel database migrations..."
    php /var/www/html/artisan migrate --force
fi

if [ "${SEED_DEMO}" = "true" ]; then
    echo "Seeding Hi.Events demo events..."
    php /var/www/html/artisan demo:seed --confirm \
        --email="${DEMO_EMAIL:-demo@devunomieta.xyz}" \
        --password="${DEMO_PASSWORD:-DemoPass123!}" || true
fi
