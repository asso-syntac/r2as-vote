#!/bin/sh
set -e

mkdir -p /app/var

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec "$@"
