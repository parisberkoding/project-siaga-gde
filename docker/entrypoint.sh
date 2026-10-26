#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
#  Entrypoint container SIAGA/BBM.
#
#  Tujuan: `docker compose up -d --build` langsung siap pakai,
#  tanpa langkah manual (permission, key, migrate, seed, cache).
#
#  Dua peran (env ENTRYPOINT_ROLE):
#    web    (default) -> init penuh + migrate + seed + jalankan nginx/fpm
#    worker           -> init ringan saja (queue / scheduler)
#  Hanya peran `web` yang menjalankan migrasi supaya tidak ada
#  3 container migrasi bersamaan.
# ─────────────────────────────────────────────────────────────
set -Eeuo pipefail

cd /var/www/html

ROLE="${ENTRYPOINT_ROLE:-web}"
SEED_ON_FIRST_BOOT="${SEED_ON_FIRST_BOOT:-true}"
APP_KEY_FILE="storage/.docker-app-key"
SEED_MARKER="storage/.docker-seeded"

log() { echo "[entrypoint][${ROLE}] $*"; }
warn() { echo "[entrypoint][${ROLE}] PERINGATAN: $*" >&2; }

# ── 1. APP_KEY ───────────────────────────────────────────────
# Sumber utama: APP_KEY dari .env host (di-inject docker compose lewat
# env_file). Jadi kunci container = kunci host -> session/encrypt sama.
# Fallback: kalau .env host belum punya APP_KEY, buat sekali dan simpan
# di volume storage supaya web/queue/scheduler memakai kunci yang sama
# dan tidak berubah setiap restart.
if [ -z "${APP_KEY:-}" ]; then
    if [ -s "$APP_KEY_FILE" ]; then
        APP_KEY="$(cat "$APP_KEY_FILE")"
    else
        APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
        mkdir -p "$(dirname "$APP_KEY_FILE")"
        printf '%s' "$APP_KEY" > "$APP_KEY_FILE"
        warn "APP_KEY tidak ada di .env host -> dibuat otomatis di ${APP_KEY_FILE}."
        warn "Agar identik dengan host, jalankan di host: php artisan key:generate"
    fi
    export APP_KEY
fi

# ── 2. Direktori runtime ─────────────────────────────────────
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/fonts \
    storage/app/public \
    storage/app/private \
    storage/app/tmp \
    bootstrap/cache

# ── 3. Permission ────────────────────────────────────────────
# File yang dibuat di macOS bisa bermode 600 -> tidak terbaca www-data
# -> error 403 dari nginx. Diperbaiki di image, tapi tetap diulang di
# sini supaya mode development (bind mount dari host) juga aman.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
chmod -R a+rX public 2>/dev/null || true

# ── 4. Tunggu database siap ──────────────────────────────────
# Dicek di level SERVER (tanpa nama database) supaya container tidak
# menggantung hanya karena database aplikasi belum dibuat.
wait_for_db() {
    if [ "${DB_CONNECTION:-mysql}" != "mysql" ]; then
        return 0
    fi

    local host="${DB_HOST:-127.0.0.1}"
    local port="${DB_PORT:-3306}"
    local tries=0
    local max_tries=90   # 90 x 2s = 3 menit

    log "Menunggu database ${host}:${port} ..."
    until php -r '
        try {
            new PDO(
                sprintf("mysql:host=%s;port=%s",
                    getenv("DB_HOST") ?: "127.0.0.1",
                    getenv("DB_PORT") ?: "3306"
                ),
                getenv("DB_USERNAME") ?: "root",
                getenv("DB_PASSWORD") ?: ""
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }' >/dev/null 2>&1; do
        tries=$((tries + 1))
        if [ "$tries" -ge "$max_tries" ]; then
            warn "Database ${host}:${port} tidak merespons setelah $((max_tries * 2)) detik."
            warn "Cek: docker compose logs db   (atau pastikan service 'db' berjalan)"
            exit 1
        fi
        sleep 2
    done
    log "Database siap."
}

# ── 4b. Pastikan database aplikasi ada + user punya hak akses ──
# Berguna kalau nama database berubah (mis. volume lama masih berisi
# database 'bbm' sementara konfigurasi sekarang memakai 'BBM_Patuha').
ensure_database() {
    if [ "${DB_CONNECTION:-mysql}" != "mysql" ] || [ -z "${DOCKER_DB_ROOT_PASSWORD:-}" ]; then
        return 0
    fi

    php -r '
        $host = getenv("DB_HOST") ?: "127.0.0.1";
        $port = getenv("DB_PORT") ?: "3306";
        $name = getenv("DB_DATABASE") ?: "";
        $user = getenv("DB_USERNAME") ?: "";
        $root = getenv("DOCKER_DB_ROOT_USER") ?: "root";
        $rootPass = getenv("DOCKER_DB_ROOT_PASSWORD") ?: "";

        if ($name === "") { exit(0); }

        $pdo = new PDO(
            sprintf("mysql:host=%s;port=%s", $host, $port),
            $root,
            $rootPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec(sprintf(
            "CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            $name
        ));

        if ($user !== "") {
            $pdo->exec(sprintf(
                "GRANT ALL PRIVILEGES ON `%s`.* TO %s",
                $name,
                $pdo->quote($user) . "@\"%\""
            ));
            $pdo->exec("FLUSH PRIVILEGES");
        }
    ' >/dev/null 2>&1 \
        && log "Database '${DB_DATABASE:-?}' siap (dibuat kalau belum ada)." \
        || warn "Tidak bisa memastikan database '${DB_DATABASE:-?}' otomatis; lanjutkan boot dan cek error migrate."
}

wait_for_db

if [ "$ROLE" = "web" ]; then
    ensure_database
fi

# ── 5. Package discovery ─────────────────────────────────────
# composer install di image memakai --no-scripts, jadi dijalankan di sini
# (butuh .env/env yang sudah tersedia).
php artisan package:discover --ansi >/dev/null 2>&1 || warn "package:discover gagal (lanjut)."

if [ "$ROLE" = "web" ]; then
    # ── 6. Symlink storage (relatif => tetap valid di dalam container) ──
    php artisan storage:link --relative --force >/dev/null 2>&1 \
        || ln -sfn ../storage/app/public public/storage

    # ── 7. Migrasi (selalu, aman diulang) ──
    log "Menjalankan migrasi ..."
    php artisan migrate --force

    # ── 8. Seed hanya pada boot pertama ──
    if [ "$SEED_ON_FIRST_BOOT" = "true" ] && [ ! -f "$SEED_MARKER" ]; then
        log "Seed data awal (sekali saja) ..."
        if php artisan db:seed --force; then
            touch "$SEED_MARKER"
            log "Seed selesai."
        else
            warn "seed gagal; akan dicoba lagi pada boot berikutnya."
        fi
    fi
fi

# ── 9. Bersihkan cache lama (mis. config:cache dari host terbawa) ──
php artisan optimize:clear >/dev/null 2>&1 || true

# ── 10. Kembalikan kepemilikan file yang dibuat langkah di atas ──
# (package:discover / optimize:clear berjalan sebagai root).
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

log "Menjalankan: $*"
exec "$@"
