#!/usr/bin/env bash
set -e

cd /var/www/html

# ── .env (di-mount dari host; fallback ke .env.example bila tidak ada) ──
if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || true
fi

# ── Tunggu database siap ──
echo "[entrypoint] Menunggu database ${DB_HOST}:${DB_PORT} ..."
until php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'ok'; } catch (Throwable \$e) { exit(1); }" >/dev/null 2>&1; do
    sleep 2
done
echo "[entrypoint] Database siap."

# ── APP_KEY (hanya bila belum ada) ──
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# ── Symlink storage (relatif => tetap valid di dalam container) ──
php artisan storage:link --relative --force >/dev/null 2>&1 || ln -sfn ../storage/app/public public/storage

# ── Direktori & permission runtime ──
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache >/dev/null 2>&1 || true

# ── Package discovery (karena composer install memakai --no-scripts) ──
php artisan package:discover --ansi >/dev/null 2>&1 || true

# ── Migrasi selalu; seed HANYA saat boot pertama ──
php artisan migrate --force

if [ ! -f storage/.docker-seeded ]; then
    echo "[entrypoint] Seed data awal (sekali saja) ..."
    if php artisan db:seed --force; then
        touch storage/.docker-seeded
        echo "[entrypoint] Seed selesai."
    else
        echo "[entrypoint] PERINGATAN: seed gagal, lanjut tanpa marker (coba lagi saat boot berikutnya)."
    fi
fi

# ── Bersihkan cache konfigurasi lama dari host bila ada ──
php artisan optimize:clear >/dev/null 2>&1 || true

echo "[entrypoint] Menjalankan: $*"
exec "$@"
