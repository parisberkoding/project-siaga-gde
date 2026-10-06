#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
#  db-sync.sh — pindahkan data antar dua lingkungan (host <-> container).
#
#  Pakai:
#      bash docker/db-sync.sh dump-host     [nama-file]   # DBngin  -> dumps/
#      bash docker/db-sync.sh dump-docker   [nama-file]   # Docker  -> dumps/
#      bash docker/db-sync.sh import-host   <file.sql>    # dumps/  -> DBngin
#      bash docker/db-sync.sh import-docker <file.sql>    # dumps/  -> Docker
#      bash docker/db-sync.sh adopt <lama> <baru>         # mis. bbm -> BBM_Patuha
#      bash docker/db-sync.sh parity                      # aktifkan lower_case_table_names=1
#      bash docker/db-sync.sh reset                       # HAPUS semua data, mulai dari nol
#
#  Nama database default sama di dua sisi (BBM_Patuha) sehingga hasil
#  dump bisa langsung diimpor tanpa diedit.
# ─────────────────────────────────────────────────────────────
set -uo pipefail

cd "$(dirname "$0")/.." || exit 1

DUMP_DIR="dumps"
PROJECT_NAME="bbm-project"
DB_NAME="${DOCKER_DB_NAME:-BBM_Patuha}"
HOST_MYSQL_PORT=3306
HOST_DB_USER="${HOST_DB_USER:-root}"
HOST_DB_PASSWORD="${HOST_DB_PASSWORD:-}"

mkdir -p "$DUMP_DIR"

log()  { printf '\033[1;34m[db-sync]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[db-sync]\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31m[db-sync]\033[0m %s\n' "$*" >&2; exit 1; }

host_client() { # host_client <mysql|mysqldump>
    local tool="$1" candidate
    for candidate in \
        "$(command -v "$tool" 2>/dev/null || true)" \
        /Users/Shared/DBngin/mysql/*/bin/"$tool" \
        /opt/homebrew/bin/"$tool" \
        /usr/local/bin/"$tool"
    do
        if [ -n "$candidate" ] && [ -x "$candidate" ]; then printf '%s' "$candidate"; return 0; fi
    done
    return 1
}

host_mysql() { # jalankan query di DBngin
    local bin; bin="$(host_client mysql)" || die "client 'mysql' tidak ditemukan (buka DBngin / install mysql client)."
    "$bin" -h 127.0.0.1 -P "$HOST_MYSQL_PORT" -u"$HOST_DB_USER" ${HOST_DB_PASSWORD:+-p"$HOST_DB_PASSWORD"} "$@"
}

docker_db_exec() { docker compose exec -T db sh -c "$1"; }

require_db_running() {
    docker compose ps --status running --services 2>/dev/null | grep -qx db \
        || die "service 'db' belum jalan. Jalankan: docker compose up -d db"
}

dump_host() {
    local file="${1:-host-$(date +%Y%m%d-%H%M%S).sql}"
    local bin; bin="$(host_client mysqldump)" || die "mysqldump tidak ditemukan."
    log "Dump DBngin (127.0.0.1:$HOST_MYSQL_PORT / $DB_NAME) -> $DUMP_DIR/$file"
    "$bin" -h 127.0.0.1 -P "$HOST_MYSQL_PORT" -u"$HOST_DB_USER" ${HOST_DB_PASSWORD:+-p"$HOST_DB_PASSWORD"} \
        --single-transaction --routines --triggers --events --databases "$DB_NAME" \
        > "$DUMP_DIR/$file" || die "dump gagal. Pastikan MySQL DBngin menyala."
    log "Selesai: $(du -h "$DUMP_DIR/$file" | cut -f1)"
}

dump_docker() {
    local file="${1:-docker-$(date +%Y%m%d-%H%M%S).sql}"
    require_db_running
    log "Dump container ($DB_NAME) -> $DUMP_DIR/$file"
    docker_db_exec 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --events --databases "$MYSQL_DATABASE"' \
        > "$DUMP_DIR/$file" || die "dump gagal."
    log "Selesai: $(du -h "$DUMP_DIR/$file" | cut -f1)"
}

import_docker() {
    local file="${1:?pakai: db-sync.sh import-docker <file.sql>}"
    [ -f "$DUMP_DIR/$file" ] || [ -f "$file" ] || die "file '$file' tidak ada."
    [ -f "$DUMP_DIR/$file" ] && file="$DUMP_DIR/$file"
    require_db_running
    log "Impor $file -> container ($DB_NAME) ..."
    docker_db_exec 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < "$file" || die "impor gagal."
    # Tandai sudah pernah di-seed: data yang baru diimpor tidak boleh ditimpa seeder.
    docker compose exec -T app sh -c 'mkdir -p storage && touch storage/.docker-seeded' >/dev/null 2>&1 || true
    log "Selesai."
}

import_host() {
    local file="${1:?pakai: db-sync.sh import-host <file.sql>}"
    [ -f "$DUMP_DIR/$file" ] || [ -f "$file" ] || die "file '$file' tidak ada."
    [ -f "$DUMP_DIR/$file" ] && file="$DUMP_DIR/$file"
    log "Impor $file -> DBngin (127.0.0.1:$HOST_MYSQL_PORT) ..."
    host_mysql < "$file" || die "impor gagal."
    log "Selesai."
}

# ── Aktifkan paritas lower_case_table_names ───────────────────
parity() {
    grep -qE '^\s*-\s*--lower_case_table_names=1' docker-compose.yml || {
        warn "Flag --lower_case_table_names=1 masih dikomentari di docker-compose.yml."
        warn ""
        warn "Langkah yang benar:"
        warn "  1. Buka docker-compose.yml, cari baris:  # - --lower_case_table_names=1"
        warn "  2. Hapus tanda '#' di depannya (biarkan '-' tetap di depan)."
        warn "  3. Jalankan ulang: make db-parity"
        warn ""
        warn "Alasannya: MySQL menetapkan opsi ini saat database PERTAMA dibuat,"
        warn "jadi volume db lama harus dibuat ulang — dan itu perlu dump dulu."
        exit 1
    }

    echo
    warn "Proses ini akan:"
    warn "  1) dump database container ke $DUMP_DIR/"
    warn "  2) MENGHAPUS volume ${PROJECT_NAME}_db_data"
    warn "  3) membuat ulang + mengimpor kembali dump tadi"
    echo
    if [ "${FORCE:-0}" != "1" ]; then
        read -r -p "Lanjut? (ketik 'ya'): " answer
        [ "$answer" = "ya" ] || die "dibatalkan."
    fi

    dump_docker "pre-parity-$(date +%Y%m%d-%H%M%S).sql"
    local latest; latest="$(ls -t "$DUMP_DIR"/pre-parity-*.sql | head -1)"

    log "Menghentikan container ..."
    docker compose down

    log "Menghapus volume ${PROJECT_NAME}_db_data ..."
    docker volume rm "${PROJECT_NAME}_db_data" >/dev/null || die "gagal menghapus volume."

    log "Membuat ulang database (lower_case_table_names=1) ..."
    docker compose up -d db

    log "Menunggu MySQL siap ..."
    for _ in $(seq 1 60); do
        docker compose exec -T db sh -c 'mysqladmin ping -h 127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" --silent' >/dev/null 2>&1 && break
        sleep 2
    done

    import_docker "$(basename "$latest")"

    log "Menjalankan kembali semua service ..."
    docker compose up -d

    log "Selesai. Cek dengan: make parity"
}

# ── Pindahkan isi database lama ke nama database baru ────────
# Sekali pakai, mis. saat nama database container berubah dari 'bbm'
# menjadi 'BBM_Patuha' (supaya sama dengan host).
adopt() {
    local old="${1:?pakai: db-sync.sh adopt <db-lama> <db-baru>}"
    local new="${2:?pakai: db-sync.sh adopt <db-lama> <db-baru>}"
    require_db_running

    local stamp; stamp="$(date +%Y%m%d-%H%M%S)"
    local raw="legacy-${old}-${stamp}.sql"
    local renamed="legacy-${old}-to-${new}-${stamp}.sql"

    log "Dump database lama '$old' ..."
    docker_db_exec "mysqldump -uroot -p\"\$MYSQL_ROOT_PASSWORD\" --single-transaction --routines --triggers --databases $old" \
        > "$DUMP_DIR/$raw" || die "dump '$old' gagal (mungkin database itu tidak ada)."

    if [ ! -s "$DUMP_DIR/$raw" ]; then
        die "dump kosong — database '$old' sepertinya tidak ada di container."
    fi

    log "Ganti nama database di dalam dump: $old -> $new"
    sed -E "s/\`${old}\`/\`${new}\`/g" "$DUMP_DIR/$raw" > "$DUMP_DIR/$renamed"

    log "Impor ke '$new' ..."
    docker_db_exec 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < "$DUMP_DIR/$renamed" || die "impor gagal."

    log "Beri hak akses user '${DOCKER_DB_USER:-bbm}' ke '$new' ..."
    docker_db_exec "mysql -uroot -p\"\$MYSQL_ROOT_PASSWORD\" -e \"GRANT ALL PRIVILEGES ON $new.* TO '${DOCKER_DB_USER:-bbm}'@'%'; FLUSH PRIVILEGES;\"" \
        || warn "gagal memberi GRANT (kemungkinan user sudah punya akses)."

    log "Selesai. Dump disimpan di: $DUMP_DIR/$renamed"
    log "Lanjutkan dengan: docker compose up -d"
}

reset() {
    echo
    warn "Ini akan MENGHAPUS SELURUH data container (database + file upload)"
    warn "dan memulai dari nol (migrasi + seed otomatis)."
    echo
    if [ "${FORCE:-0}" != "1" ]; then
        read -r -p "Lanjut? (ketik 'ya'): " answer
        [ "$answer" = "ya" ] || die "dibatalkan."
    fi
    docker compose down -v
    docker compose up -d --build
    log "Selesai."
}

case "${1:-}" in
    dump-host)     shift; dump_host "$@" ;;
    dump-docker)   shift; dump_docker "$@" ;;
    import-host)   shift; import_host "$@" ;;
    import-docker) shift; import_docker "$@" ;;
    adopt)         shift; adopt "$@" ;;
    parity)        parity ;;
    reset)         reset ;;
    *)
        sed -n '3,20p' "$0" | sed 's/^# \{0,1\}//'
        exit 1
        ;;
esac
