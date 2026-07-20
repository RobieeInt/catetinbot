#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f artisan ]; then
    echo "❌ artisan tidak ditemukan — jalankan script ini dari root project Laravel." >&2
    exit 1
fi

echo "==> Pull commit terbaru..."
git pull origin main

echo "==> Install dependencies composer..."
composer install --no-dev --optimize-autoloader

echo "==> Migrate database..."
php artisan migrate --force

echo "==> Sync asset publik (build/project/img) ke document root..."
rm -rf build project img
cp -r public/build build
cp -r public/project project
cp -r public/img img

# .htaccess & robots.txt di root ini cuma copy plain dari public/ (belum pernah
# di-custom), aman disinkron otomatis. index.php SENGAJA tidak disentuh —
# path-nya udah disesuaikan manual buat struktur flat (public_html = root app).
cp public/.htaccess .htaccess
cp public/robots.txt robots.txt
cp public/sitemap.xml sitemap.xml

echo "==> Refresh cache..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

echo "==> Selesai. Deploy sukses."
