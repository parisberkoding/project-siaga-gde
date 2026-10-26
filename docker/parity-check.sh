#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
#  parity-check.sh — bandingkan lingkungan HOST (Herd/DBngin)
#  dengan CONTAINER (Docker) lalu laporkan bedanya.
#
#  Pakai:
#      bash docker/parity-check.sh
#      make parity
#
#  Tujuan: perbedaan versi/setting antar dua lingkungan jadi TERLIHAT,
#  bukan berubah jadi bug misterius ("kok di Docker jalan, di Herd tidak?").
#
#  Catatan: nilai php.ini dibaca dari SAPI php-fpm (bukan CLI), karena
#  itulah yang melayani request web di dua sisi.
# ─────────────────────────────────────────────────────────────
set -uo pipefail

cd "$(dirname "$0")/.." || exit 1
PROJECT_DIR="$(pwd)"

SERVICE="app"
HERD_HOME="$HOME/Library/Application Support/Herd"
HOST_MYSQL_PORT=3306

command -v docker >/dev/null 2>&1 || { echo "docker tidak ditemukan di PATH."; exit 1; }

# ── Kumpulan hasil pemeriksaan ───────────────────────────────
RECORDS=()
NOTES=()
DIFFS=0

check() { # check <label> <nilai host> <nilai container> [exact|minor|info]
    local label="$1" h="$2" c="$3" mode="${4:-exact}" hh="$2" cc="$3"
    if [ "$mode" = "info" ]; then
        RECORDS+=("$label|$h|$c|ℹ️ ")
        return
    fi
    if [ "$mode" = "minor" ]; then
        hh="$(printf '%s' "$h" | grep -oE '^[0-9]+\.[0-9]+' || printf '%s' "$h")"
        cc="$(printf '%s' "$c" | grep -oE '^[0-9]+\.[0-9]+' || printf '%s' "$c")"
    fi
    if [ "$hh" = "$cc" ]; then
        RECORDS+=("$label|$h|$c|✅")
    else
        RECORDS+=("$label|$h|$c|⚠️ ")
        DIFFS=$((DIFFS + 1))
    fi
}

note() { NOTES+=("$1"); }

container_up() { docker compose ps --status running --services 2>/dev/null | grep -qx "$SERVICE"; }

if ! container_up; then
    echo "❌ Container '$SERVICE' belum jalan. Jalankan dulu:  docker compose up -d --build"
    exit 1
fi

CEXEC=(docker compose exec -T "$SERVICE")

# ── Client mysql di host (Herd/DBngin sering tidak ada di PATH) ──
HOST_MYSQL=""
for candidate in \
    "$(command -v mysql 2>/dev/null || true)" \
    /Users/Shared/DBngin/mysql/*/bin/mysql \
    /opt/homebrew/bin/mysql \
    /usr/local/bin/mysql
do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then HOST_MYSQL="$candidate"; break; fi
done

HOST_COMPOSER="$(command -v composer 2>/dev/null || true)"
[ -n "$HOST_COMPOSER" ] || HOST_COMPOSER="$HERD_HOME/bin/composer"

# ── Versi PHP yang dipakai Herd ──────────────────────────────
# 1) default: symlink herd.sock -> herd84.sock
HERD_SOCK_TARGET="$(basename "$(readlink "$HERD_HOME/herd.sock" 2>/dev/null)" 2>/dev/null)"
HERD_DEFAULT_PHP="$(printf '%s' "$HERD_SOCK_TARGET" | sed -E 's/^herd([0-9])([0-9])\.sock$/\1.\2/')"
[ -n "$HERD_DEFAULT_PHP" ] || HERD_DEFAULT_PHP="?"
HERD_DEFAULT_CODE="$(printf '%s' "$HERD_DEFAULT_PHP" | tr -d '.')"

# 2) versi yang dipakai site (conf site memakai $herd_sock_NN)
SITE_PHP_CODE="$(grep -rhoE 'herd_sock_[0-9]{2}' "$HERD_HOME/config/valet/Nginx" 2>/dev/null \
    | sort | uniq -c | sort -rn | head -1 | grep -oE '[0-9]{2}$')"
if [ -n "${SITE_PHP_CODE:-}" ]; then
    SITE_PHP="${SITE_PHP_CODE:0:1}.${SITE_PHP_CODE:1:1}"
else
    SITE_PHP="$HERD_DEFAULT_PHP"
    SITE_PHP_CODE="$HERD_DEFAULT_CODE"
fi

# 3) binari php-fpm Herd untuk membaca php.ini versi tersebut
HOST_FPM=""
for candidate in "$HERD_HOME/bin/php${SITE_PHP_CODE}-fpm" "$HERD_HOME/bin/php${HERD_DEFAULT_CODE}-fpm"; do
    if [ -n "$candidate" ] && [ -x "$candidate" ]; then HOST_FPM="$candidate"; break; fi
done

# ── Data HOST ────────────────────────────────────────────────
HOST_PHP_VER="$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo '-')"
HOST_COMPOSER_VER="$("$HOST_COMPOSER" --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1 || echo '-')"
HOST_NODE_VER="$(node -v 2>/dev/null | tr -d 'v' || echo '-')"
HOST_ENV_KEY="$(grep -E '^APP_KEY=' .env 2>/dev/null | cut -d= -f2-)"

# php.ini dari SAPI php-fpm host (fallback ke CLI)
HOST_INI="$(php -r 'foreach(["memory_limit","upload_max_filesize","post_max_size","max_file_uploads","max_execution_time","max_input_time","date.timezone","pcre.jit"] as $k) printf("%s=%s\n",$k,ini_get($k));' 2>/dev/null)"
HOST_INI_SAPI="CLI"
if [ -n "$HOST_FPM" ]; then
    HOST_FPM_INFO="$("$HOST_FPM" -i 2>/dev/null)"
    if printf '%s' "$HOST_FPM_INFO" | grep -q 'upload_max_filesize'; then
        HOST_INI="$(printf '%s' "$HOST_FPM_INFO" \
            | grep -E '^(memory_limit|upload_max_filesize|post_max_size|max_file_uploads|max_execution_time|max_input_time|date.timezone|pcre.jit) =>' \
            | sed -E 's/^([a-z_.]+) => *([^ ]+).*/\1=\2/')"
        HOST_INI_SAPI="FPM ${SITE_PHP}"
    fi
fi

HOST_GD_WEBP="$(php -r 'echo extension_loaded("gd") && gd_info()["WebP Support"] ? "yes" : "no";' 2>/dev/null || echo '-')"
HOST_IMAGICK="$(php -r '$f = extension_loaded("imagick") ? Imagick::queryFormats() : []; echo extension_loaded("imagick") ? (new ReflectionExtension("imagick"))->getVersion()." (".count($f)." format)" : "tidak ada"; echo "|", (in_array("HEIC",$f) ? "HEIC ya" : "HEIC tidak");' 2>/dev/null || echo '-')"

# ── Data MySQL host ─────────────────────────────────────────
HOST_DB_VER="(DBngin mati)"; HOST_DB_COLLATION="-"; HOST_DB_LCNT="-"; HOST_DB_TZ="-"; HOST_DB_SQLMODE="-"; HOST_DB_TABLES="?"
HOST_MYSQL_OK=0
if [ -n "$HOST_MYSQL" ] && "$HOST_MYSQL" -h 127.0.0.1 -P "$HOST_MYSQL_PORT" -uroot --connect-timeout=3 -N -B -e 'SELECT 1' >/dev/null 2>&1; then
    HOST_MYSQL_OK=1
    hq() { "$HOST_MYSQL" -h 127.0.0.1 -P "$HOST_MYSQL_PORT" -uroot -N -B -e "$1" 2>/dev/null; }
    HOST_DB_VER="$(hq 'SELECT VERSION()')"
    HOST_DB_COLLATION="$(hq 'SELECT @@collation_server')"
    HOST_DB_LCNT="$(hq 'SELECT @@lower_case_table_names')"
    # Bandingkan offset efektif (bukan nama zona) supaya WIB == +07:00
    HOST_DB_TZ="$(hq 'SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP())')"
    HOST_DB_SQLMODE="$(hq 'SELECT @@sql_mode')"
    HOST_DB_TABLES="$(hq "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DOCKER_DB_NAME:-BBM_Patuha}'")"
fi

# ── Data CONTAINER ───────────────────────────────────────────
cq() { "${CEXEC[@]}" php -r "$1" 2>/dev/null | tr -d '\r'; }
cdb() {
    docker compose exec -T db sh -c \
        'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -B "$MYSQL_DATABASE" -e "'"$1"'"' 2>/dev/null | tr -d '\r'
}

C_PHP_VER="$(cq 'echo PHP_VERSION;')"
C_COMPOSER_VER="$("${CEXEC[@]}" composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)"
C_FPM_INFO="$("${CEXEC[@]}" php-fpm -i 2>/dev/null)"
C_INI="$(printf '%s' "$C_FPM_INFO" \
    | grep -E '^(memory_limit|upload_max_filesize|post_max_size|max_file_uploads|max_execution_time|max_input_time|date.timezone|pcre.jit) =>' \
    | sed -E 's/^([a-z_.]+) => *([^ ]+).*/\1=\2/')"
C_GD_WEBP="$(cq 'echo extension_loaded("gd") && gd_info()["WebP Support"] ? "yes" : "no";')"
C_IMAGICK="$(cq '$f = extension_loaded("imagick") ? Imagick::queryFormats() : []; echo extension_loaded("imagick") ? (new ReflectionExtension("imagick"))->getVersion()." (".count($f)." format)" : "tidak ada"; echo "|", (in_array("HEIC",$f) ? "HEIC ya" : "HEIC tidak");')"
C_ENV_KEY="$("${CEXEC[@]}" printenv APP_KEY 2>/dev/null | tr -d '\r')"
C_DB_VER="$(cdb 'SELECT VERSION()')"
C_DB_COLLATION="$(cdb 'SELECT @@collation_server')"
C_DB_LCNT="$(cdb 'SELECT @@lower_case_table_names')"
C_DB_TZ="$(cdb 'SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP())')"
C_DB_SQLMODE="$(cdb 'SELECT @@sql_mode')"
C_DB_TABLES="$(cdb 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')"

ini_get() { printf '%s\n' "$1" | grep -E "^$2=" | cut -d= -f2- | head -1; }

# ── Susun hasil ──────────────────────────────────────────────
check "PHP (CLI host)"         "$HOST_PHP_VER"           "$C_PHP_VER"     minor
check "PHP (site Herd)"        "$SITE_PHP"               "$C_PHP_VER"     minor
check "Web server"             "nginx"                   "nginx"
check "PHP SAPI (web)"         "php-fpm"                 "php-fpm"
check "Composer"               "${HOST_COMPOSER_VER:-?}" "${C_COMPOSER_VER:-?}" minor
check "Node (build aset)"      "${HOST_NODE_VER:-?}"     "22-alpine (build)" info

check "MySQL / MariaDB"        "$HOST_DB_VER"            "${C_DB_VER:-?}"  minor
check "collation_server"       "$HOST_DB_COLLATION"      "${C_DB_COLLATION:-?}"
check "lower_case_table_names" "$HOST_DB_LCNT"           "${C_DB_LCNT:-?}"
check "offset time zone MySQL" "$HOST_DB_TZ"             "${C_DB_TZ:-?}"
check "Tabel di DB aplikasi"   "$HOST_DB_TABLES"         "${C_DB_TABLES:-?}" info

check "memory_limit ($HOST_INI_SAPI)"        "$(ini_get "$HOST_INI" memory_limit)"        "$(ini_get "$C_INI" memory_limit)"
check "upload_max_filesize ($HOST_INI_SAPI)" "$(ini_get "$HOST_INI" upload_max_filesize)" "$(ini_get "$C_INI" upload_max_filesize)"
check "post_max_size ($HOST_INI_SAPI)"       "$(ini_get "$HOST_INI" post_max_size)"       "$(ini_get "$C_INI" post_max_size)"
check "max_file_uploads ($HOST_INI_SAPI)"    "$(ini_get "$HOST_INI" max_file_uploads)"    "$(ini_get "$C_INI" max_file_uploads)"
check "max_execution_time ($HOST_INI_SAPI)"  "$(ini_get "$HOST_INI" max_execution_time)"  "$(ini_get "$C_INI" max_execution_time)"
check "max_input_time ($HOST_INI_SAPI)"      "$(ini_get "$HOST_INI" max_input_time)"      "$(ini_get "$C_INI" max_input_time)"
check "date.timezone"                        "$(ini_get "$HOST_INI" date.timezone)"       "$(ini_get "$C_INI" date.timezone)"
check "pcre.jit"                             "$(ini_get "$HOST_INI" pcre.jit)"            "$(ini_get "$C_INI" pcre.jit)"

check "GD WebP"                "$HOST_GD_WEBP"           "$C_GD_WEBP"
check "Imagick (versi+format)" "${HOST_IMAGICK%%|*}"     "${C_IMAGICK%%|*}" info
check "Imagick HEIC"           "${HOST_IMAGICK##*|}"     "${C_IMAGICK##*|}"
check "APP_KEY"                "${HOST_ENV_KEY:0:14}…"   "${C_ENV_KEY:0:14}…"

# ── Cetak ────────────────────────────────────────────────────
echo
echo "PARITAS LINGKUNGAN — HOST (Herd/DBngin) vs CONTAINER (Docker)"
echo "Project : $PROJECT_DIR"
[ -n "$HOST_FPM" ] && echo "Host FPM: $HOST_FPM"
echo
printf '%-38s %-24s %-26s %s\n' "KOMPONEN" "HOST" "CONTAINER" "STATUS"
printf '%s\n' "──────────────────────────────────────────────────────────────────────────────────────────────"
for rec in "${RECORDS[@]}"; do
    IFS='|' read -r label h c status <<<"$rec"
    printf '%-38s %-24s %-26s %s\n' "$label" "${h:0:24}" "${c:0:26}" "$status"
done
printf '%s\n' "──────────────────────────────────────────────────────────────────────────────────────────────"

echo
if [ -n "$HOST_DB_SQLMODE" ] && [ "$HOST_DB_SQLMODE" != "-" ] && [ -n "$C_DB_SQLMODE" ]; then
    if [ "$HOST_DB_SQLMODE" = "$C_DB_SQLMODE" ]; then
        echo "sql_mode: ✅ sama"
    else
        echo "sql_mode: ⚠️  BEDA"
        echo "   host      : $HOST_DB_SQLMODE"
        echo "   container : $C_DB_SQLMODE"
    fi
else
    echo "sql_mode: (host tidak terbaca — nyalakan MySQL di DBngin untuk membandingkan)"
fi

# ── Tindakan ─────────────────────────────────────────────────
echo
echo "TINDAKAN YANG DISARANKAN:"

if [ "$HOST_MYSQL_OK" != "1" ]; then
    note "Nyalakan MySQL di DBngin supaya perbandingan DB (collation, timezone, sql_mode) lengkap."
fi

if [ "$(ini_get "$HOST_INI" upload_max_filesize)" != "$(ini_get "$C_INI" upload_max_filesize)" ] \
   || [ "$(ini_get "$HOST_INI" post_max_size)" != "$(ini_get "$C_INI" post_max_size)" ] \
   || [ "$(ini_get "$HOST_INI" max_file_uploads)" != "$(ini_get "$C_INI" max_file_uploads)" ]; then
    note "Batas upload BEDA -> upload struk besar bisa berhasil di satu sisi & gagal di sisi lain."
    note "Samakan: tempel isi docker/herd-parity.ini ke php.ini Herd (lihat faris/setup docker.md)."
fi

if [ "$(ini_get "$HOST_INI" max_execution_time)" != "$(ini_get "$C_INI" max_execution_time)" ]; then
    note "max_execution_time BEDA (host=$(ini_get "$HOST_INI" max_execution_time), container=$(ini_get "$C_INI" max_execution_time)) -> ekspor PDF/Excel besar bisa timeout hanya di satu sisi."
fi

if [ "$HOST_DB_LCNT" != "-" ] && [ -n "$HOST_DB_LCNT" ] && [ "$HOST_DB_LCNT" != "${C_DB_LCNT:-}" ]; then
    note "lower_case_table_names beda (host=$HOST_DB_LCNT, container=${C_DB_LCNT:-?}): nama tabel case-insensitive di host, case-sensitive di container."
    note "Opsional: aktifkan di docker-compose.yml lalu jalankan 'make db-parity'."
fi

if [ "$HOST_PHP_VER" != "$C_PHP_VER" ]; then
    note "Versi PHP CLI host ($HOST_PHP_VER) beda dengan container ($C_PHP_VER)."
fi

C_MM="$(printf '%s' "$C_PHP_VER" | grep -oE '^[0-9]+\.[0-9]+')"
if [ -n "$SITE_PHP" ] && [ "$SITE_PHP" != "$C_MM" ]; then
    note "Site Herd memakai PHP $SITE_PHP sedangkan container $C_MM."
    note "Pilih satu: 'PHP_VERSION=$SITE_PHP make up'  ATAU  'herd isolate $C_MM' dari folder project ini."
fi

for n in "${NOTES[@]:-}"; do
    [ -n "$n" ] && echo "  • $n"
done
echo "  • Jalankan 'make case' sebelum commit: macOS tidak peduli huruf besar/kecil, Linux peduli."
echo
echo "Ringkasan: $DIFFS perbedaan terdeteksi."
exit 0
