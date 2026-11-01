#!/usr/bin/env bash
# Perbarui ALMARA MUSLIM di server setelah ada perubahan di GitHub. @2026-Alifiandra
# Pemakaian (dari folder aplikasi):  sudo -u www-data bash deploy/update.sh
set -euo pipefail
cd "$(dirname "$0")/.."

php artisan down --render="errors::503" --retry=30 || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan almara:quran:validate
php artisan up
echo "Selesai. Versi: $(php artisan tinker --execute='echo config("almara.version");' 2>/dev/null)"
