#!/usr/bin/env bash
set -euo pipefail

php artisan down --retry=60 || true

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --prefer-offline --no-audit
npm run build

php artisan migrate --force
php artisan optimize
php artisan queue:restart

php artisan up
