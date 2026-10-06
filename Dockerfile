# syntax=docker/dockerfile:1
# ─────────────────────────────────────────────────────────────
#  SIAGA / BBM Project — image "lokal produksi"
#  nginx + php-fpm + supervisor dalam SATU container.
#
#  Kenapa nginx+php-fpm (bukan Apache)?
#  Supaya SAPI-nya sama dengan Herd (nginx + php-fpm), jadi perilaku
#  request/header/limit di container == perilaku di host.
#
#  Tiga stage (urutan penting):
#    1) app    : runtime PHP (composer install + source aplikasi)
#    2) assets : build CSS/JS pakai Vite; butuh vendor dari stage `app`
#                karena resources/css/app.css memakai @source ke blade
#                pagination milik Laravel
#    3) final  : stage `app` + hasil build aset + entrypoint
#
#  Versi tool bisa di-override supaya bisa disamakan dengan Herd/DBngin:
#      PHP_VERSION=8.5 docker compose build
#  Tabel paritas versi + cara ceknya ada di "faris/setup docker.md".
# ─────────────────────────────────────────────────────────────

ARG PHP_VERSION=8.4
ARG NODE_VERSION=22

# ─────────────────────────────────────────────────────────────
# Stage 1 — runtime aplikasi (PHP-FPM)
# ─────────────────────────────────────────────────────────────
FROM php:${PHP_VERSION}-fpm-bookworm AS app

# WITH_IMAGICK=true  -> samakan dengan Herd (Imagick + dukungan HEIC/WEBP)
# WITH_IMAGICK=false -> image lebih kecil, tapi app jatuh ke jalur GD
ARG WITH_IMAGICK=true

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1

# ── Paket sistem + ekstensi PHP ──────────────────────────────
# Disamakan dengan extension set milik Herd yang dipakai aplikasi:
#   pdo_mysql  -> database
#   gd + webp  -> kompresi struk (ImageCompressor) & barcode/QR
#   zip        -> maatwebsite/excel
#   intl       -> Carbon translatedFormat
#   exif       -> orientasi foto
#   bcmath     -> perhitungan budget
#   pcntl      -> queue:work / schedule:work bisa shutdown rapi
#   imagick    -> jalur kompresi di SuperadminController + HEIC (foto iPhone)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip \
        nginx supervisor \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libxml2-dev libwebp-dev \
        $PHPIZE_DEPS; \
    if [ "$WITH_IMAGICK" = "true" ]; then \
        apt-get install -y --no-install-recommends \
            libmagickwand-dev \
            libmagickcore-6.q16-6-extra; \
    fi; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql gd zip bcmath intl exif pcntl; \
    if [ "$WITH_IMAGICK" = "true" ]; then \
        pecl install imagick; \
        docker-php-ext-enable imagick; \
        apt-mark manual libgomp1 libmagickcore-6.q16-6 libmagickwand-6.q16-6 || true; \
    fi; \
    apt-get purge -y --auto-remove $PHPIZE_DEPS; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/pear

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Konfigurasi PHP / nginx / supervisor ─────────────────────
COPY docker/php/php.ini        /usr/local/etc/php/conf.d/zz-siaga.ini
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf   /etc/supervisor/conf.d/siaga.conf

RUN set -eux; \
    rm -f /etc/nginx/sites-enabled/default; \
    mkdir -p /var/log/supervisor /var/run; \
    php -v

WORKDIR /var/www/html

# Direktori runtime dibuat LEBIH DULU supaya layer composer di bawahnya
# bisa menulis bootstrap/cache.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/fonts \
        storage/app/public \
        storage/app/private \
        storage/app/tmp \
        bootstrap/cache

# Dependensi PHP (layer cache: hanya berubah kalau composer.json/lock berubah)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev --no-interaction --no-progress --prefer-dist \
        --optimize-autoloader --no-scripts

# Source aplikasi
COPY . .

# Autoload final
RUN composer dump-autoload --no-dev --optimize --no-scripts

# ── Perbaikan permission (akar masalah error 403 di container lama) ──
# `COPY` menyalin mode file dari macOS, dan file yang dibuat editor
# tertentu bisa jadi 600 (hanya owner). nginx/php-fpm jalan sebagai
# www-data sehingga file itu tidak terbaca -> 403. Normalisasi di sini.
RUN set -eux; \
    chmod -R a+rX /var/www/html; \
    chown -R www-data:www-data storage bootstrap/cache; \
    chmod -R ug+rwX storage bootstrap/cache

# ─────────────────────────────────────────────────────────────
# Stage 2 — build aset frontend (Vite + Tailwind)
# ─────────────────────────────────────────────────────────────
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app

# package-lock.json wajib ada supaya build reproducible (npm ci).
# Kalau file ini hilang: jalankan `npm install --package-lock-only` di host.
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources

# resources/css/app.css memakai @source ke blade pagination Laravel.
# File itu hanya ada di vendor -> diambil dari stage `app` yang sudah
# menjalankan composer install. Tanpa ini, kelas Tailwind untuk
# pagination (`->links()`, dipakai 30x) hilang dari CSS hasil build.
COPY --from=app /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 3 — image final
# ─────────────────────────────────────────────────────────────
FROM app AS final

# Aset hasil build stage 2
COPY --from=assets /app/public/build ./public/build

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=90s --retries=6 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "--nodaemon", "--logfile", "/dev/null", \
     "--configuration", "/etc/supervisor/supervisord.conf"]
