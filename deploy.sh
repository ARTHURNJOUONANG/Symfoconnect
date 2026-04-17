#!/usr/bin/env bash
set -euo pipefail

echo "Install dependencies"
composer install --no-dev --optimize-autoloader --classmap-authoritative

echo "Run database migrations"
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Clear and warmup cache"
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "Build assets"
php bin/console asset-map:compile --env=prod

echo "Restart messenger worker (example)"
echo "php bin/console messenger:consume async --time-limit=3600 --memory-limit=256M --env=prod"

echo "Deploy script completed"
