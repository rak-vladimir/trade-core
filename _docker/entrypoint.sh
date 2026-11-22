#!/bin/sh

set -eu

cd /var/www/html

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions when container starts as root
if [ "$(id -u)" -eq 0 ]; then
    chown -R app:app storage bootstrap/cache
fi

exec "$@"
