# ─────────────────────────────────────────────────────────────
#  SIAGA / BBM — perintah Docker yang paling sering dipakai.
#  Jalankan `make` untuk melihat daftar perintah.
#
#  Contoh:
#     make up            # build ulang + jalan (semua otomatis)
#     make logs          # lihat log
#     make shell         # masuk ke container
#     make parity        # bandingkan versi host vs container
#     make case          # cek huruf besar/kecil nama file (macOS -> Linux)
# ─────────────────────────────────────────────────────────────
SHELL   := /bin/bash
COMPOSE := docker compose
APP_URL := http://localhost:$${DOCKER_APP_PORT:-8080}

.DEFAULT_GOAL := help

.PHONY: help up build down stop restart ps logs shell tinker artisan migrate seed fresh \
        parity case urls health dev dev-off db db-dump db-import db-from-host db-to-host db-parity \
        db-adopt-legacy storage-sync reset

help: ## Tampilkan daftar perintah
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'
	@echo
	@echo "  Service: app (nginx+php-fpm) | queue | scheduler | db (MySQL)"
	@echo "  URL    : $(APP_URL)"

up: ## Build ulang image + jalankan semua service
	$(COMPOSE) up -d --build
	@echo
	@echo "✅ Aplikasi jalan di $(APP_URL)"
	@$(COMPOSE) ps

build: ## Build image saja (tanpa menjalankan)
	$(COMPOSE) build

down: ## Stop + hapus container (data di volume TETAP aman)
	$(COMPOSE) down --remove-orphans

stop: ## Stop container tanpa menghapusnya
	$(COMPOSE) stop

restart: ## Restart semua service
	$(COMPOSE) restart

ps: ## Status container
	$(COMPOSE) ps

logs: ## Ikuti log semua service
	$(COMPOSE) logs -f --tail=100

shell: ## Masuk ke container app (bash)
	$(COMPOSE) exec app bash

tinker: ## php artisan tinker di dalam container
	$(COMPOSE) exec app php artisan tinker

artisan: ## Jalankan artisan: make artisan ARGS="route:list"
	$(COMPOSE) exec app php artisan $(ARGS)

migrate: ## Jalankan migrasi
	$(COMPOSE) exec app php artisan migrate

seed: ## Jalankan seeder
	$(COMPOSE) exec app php artisan db:seed

fresh: ## Migrate:fresh + seed (HAPUS isi database container)
	$(COMPOSE) exec app php artisan migrate:fresh --seed

health: ## Cek endpoint /up
	@curl -fsS $(APP_URL)/up && echo " ✅ sehat" || echo " ❌ tidak sehat"

urls: ## Tampilkan URL penting
	@echo "Aplikasi : $(APP_URL)"
	@echo "Kesehatan: $(APP_URL)/up"
	@echo "MySQL    : 127.0.0.1:$${DOCKER_DB_PORT:-3307} (user: $${DOCKER_DB_USER:-bbm})"

# ── Paritas host <-> container ───────────────────────────────
parity: ## Bandingkan versi/setting host (Herd/DBngin) dengan container
	bash docker/parity-check.sh

case: ## Cek kesalahan huruf besar/kecil nama view/class/asset
	php docker/check-case.php

# ── Data ─────────────────────────────────────────────────────
db: ## Buka client MySQL di dalam container
	$(COMPOSE) exec db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"'

db-dump: ## Dump database container -> dumps/
	bash docker/db-sync.sh dump-docker $(ARGS)

db-import: ## Impor file dump ke container: make db-import ARGS=nama.sql
	bash docker/db-sync.sh import-docker $(ARGS)

db-from-host: ## Salin data DBngin (host) -> container
	bash docker/db-sync.sh dump-host && \
	bash docker/db-sync.sh import-docker "$$(ls -t dumps/host-*.sql | head -1 | xargs basename)"

db-to-host: ## Salin data container -> DBngin (host)
	bash docker/db-sync.sh dump-docker && \
	bash docker/db-sync.sh import-host "$$(ls -t dumps/docker-*.sql | head -1 | xargs basename)"

db-parity: ## Recreate volume DB dengan lower_case_table_names=1 (dump dulu, aman)
	bash docker/db-sync.sh parity

db-adopt-legacy: ## Pindahkan data database lama 'bbm' -> 'BBM_Patuha' (sekali saja)
	bash docker/db-sync.sh adopt bbm BBM_Patuha

storage-sync: ## Salin file upload dari host (storage/app/public) ke container
	$(COMPOSE) cp storage/app/public/. app:/var/www/html/storage/app/public/
	$(COMPOSE) exec app chown -R www-data:www-data storage/app/public
	@echo "✅ File upload host tersalin ke container."

reset: ## HAPUS semua data (DB + upload) lalu mulai dari nol
	bash docker/db-sync.sh reset

# ── Mode development (kode live tanpa rebuild) ───────────────
dev: ## Aktifkan mode development (bind mount + vite hot reload)
	@test -f docker-compose.override.yml || cp docker-compose.override.yml.example docker-compose.override.yml
	$(COMPOSE) up -d
	@echo "✅ Mode dev aktif. Vite: http://localhost:5173"

dev-off: ## Matikan mode development (kembali ke kode di dalam image)
	@rm -f docker-compose.override.yml
	$(COMPOSE) up -d --build --remove-orphans
	@echo "✅ Kembali ke mode image (container vite ikut dimatikan)."
