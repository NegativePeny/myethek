#!/usr/bin/env bash
set -e

# ==============================================================================
# Script Deployment & Update Otomatis - MyEthek (Mak Ethek Koperasi Jatim)
# ==============================================================================

PROJECT_DIR="/var/www/projects/myethek"
BRANCH="${1:-main}"

echo "=========================================================="
echo " [MyEthek] Mulai proses deployment pada branch: $BRANCH"
echo "=========================================================="

cd "$PROJECT_DIR"

# 1. Pastikan working tree bersih & ambil perubahan terbaru dari Git
echo ">> [1/8] Git fetch & pull..."
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull origin "$BRANCH"

# 2. Instalasi dan update dependensi PHP (Composer)
echo ">> [2/8] Composer install..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Jalankan migrasi database
echo ">> [3/8] Menjalankan migrasi database..."
php artisan migrate --force

# 4. Instalasi dan kompilasi asset frontend (Vite)
echo ">> [4/8] Build asset frontend (NPM & Vite)..."
if [ -f "package.json" ]; then
    npm install --no-audit --prefer-offline
    npm run build
fi

# 5. Optimasi produksi (Cache Config, Route, View)
echo ">> [5/7] Mengoptimalkan cache produksi Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Atur izin akses direktori storage dan cache
echo ">> [6/7] Memperbarui permissions storage & bootstrap/cache..."
chown -R www-data:www-data "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"
chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

# 7. Reload PHP-FPM dan Nginx
echo ">> [7/7] Me-reload PHP-FPM & Nginx..."
if command -v systemctl >/dev/null 2>&1; then
    systemctl reload php8.4-fpm 2>/dev/null || true
    systemctl reload nginx 2>/dev/null || true
fi

echo "=========================================================="
echo " [MyEthek] Deployment BERHASIL & aplikasi siap digunakan!"
echo " Akses URL: https://rpc-desktop.taild04204.ts.net/myethek/"
echo "=========================================================="
