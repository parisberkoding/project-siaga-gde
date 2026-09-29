# syntax=docker/dockerfile:1

# ─────────────────────────────────────────────────────────────
# Stage 1 — build aset frontend (Vite + Tailwind)
# ─────────────────────────────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build

# ─────────────────────────────────────────────────────────────
# Stage 2 — aplikasi Laravel (Apache + PHP 8.4)
# ─────────────────────────────────────────────────────────────
FROM php:8.4-apache

# Paket sistem untuk ekstensi PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Ekstensi PHP yang dibutuhkan project
# (pdo_mysql, gd = driver gambar, zip = Excel, intl = Carbon translatedFormat, exif, mbstring)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip bcmath intl exif mbstring \
    && a2enmod rewrite headers

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Config PHP + virtual host Apache
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-uploads.ini
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Dependensi PHP dulu (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Source aplikasi
COPY . .

# Autoload final (package:discover dijalankan saat entrypoint agar .env tersedia)
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Aset hasil build dari stage 1
COPY --from=assets /app/public/build ./public/build

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/build

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
