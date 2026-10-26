#!/usr/bin/env bash
#
# share.sh — jalankan SIAGA + buka akses publik lewat Cloudflare Quick Tunnel.
#
# Cukup:
#     ./share.sh
#
# Opsi env:
#     PORT=8001 ./share.sh          # ganti port lokal (default 8000)
#     HOST=127.0.0.1 ./share.sh     # alamat bind server (default 127.0.0.1)
#     CF_PROTOCOL=http2 ./share.sh  # paksa protocol tunnel (kalau QUIC diblokir)
#
# Cloudflare Quick Tunnel TIDAK butuh akun/login. URL publik berbentuk
# https://<acak>.trycloudflare.com dan HANYA hidup selama script ini berjalan.
# Tekan Ctrl+C untuk menutup server & tunnel.
#
# Catatan: script ini membuka aplikasi ke internet. Pastikan akun demo/password
# default sudah diganti sebelum membagikan URL-nya.

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

usage() {
  cat <<'EOF'
share.sh — jalankan SIAGA + buka akses publik lewat Cloudflare Quick Tunnel.

Cukup:
    ./share.sh

Opsi env:
    PORT=8001 ./share.sh          # ganti port lokal (default 8000)
    HOST=127.0.0.1 ./share.sh     # alamat bind server (default 127.0.0.1)
    CF_PROTOCOL=http2 ./share.sh  # paksa protocol tunnel (kalau QUIC diblokir)

Cloudflare Quick Tunnel TIDAK butuh akun/login. URL publik berbentuk
https://<acak>.trycloudflare.com dan HANYA hidup selama script ini berjalan.
Tekan Ctrl+C untuk menutup server & tunnel.

Catatan: script ini membuka aplikasi ke internet. Pastikan akun demo/password
default sudah diganti sebelum membagikan URL-nya.
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

PORT="${PORT:-8000}"
HOST="${HOST:-127.0.0.1}"
CF_PROTOCOL="${CF_PROTOCOL:-}"

TMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/siaga-share.XXXXXX")"
CF_LOG="$TMP_DIR/cloudflared.log"
SERVER_LOG="$TMP_DIR/server.log"

CF_PID=""
SERVER_PID=""
URL=""

cleanup() {
  trap - EXIT INT TERM

  if [[ -n "$CF_PID" ]] && kill -0 "$CF_PID" 2>/dev/null; then
    kill "$CF_PID" 2>/dev/null || true
    wait "$CF_PID" 2>/dev/null || true
  fi

  if [[ -n "$SERVER_PID" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi

  echo
  echo "Tunnel & server lokal ditutup."
  echo "Log tersimpan di: $TMP_DIR"
}

trap cleanup EXIT INT TERM

for bin in php curl cloudflared; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    case "$bin" in
      cloudflared)
        echo "ERROR: cloudflared tidak ditemukan."
        echo "Install dulu:  brew install cloudflared"
        ;;
      *)
        echo "ERROR: '$bin' tidak ditemukan di PATH."
        ;;
    esac
    exit 1
  fi
done

server_is_up() {
  curl -fsS --max-time 2 "http://$HOST:$PORT/up" >/dev/null 2>&1
}

port_is_open() {
  if command -v nc >/dev/null 2>&1; then
    nc -z "$HOST" "$PORT" >/dev/null 2>&1
  else
    (exec 3<>"/dev/tcp/$HOST/$PORT") >/dev/null 2>&1
  fi
}

# ── Preflight port ────────────────────────────────────────────────────────
# Kalau port sudah dipakai aplikasi lain, tunnel akan mengarah ke aplikasi yang
# salah. Deteksi lebih awal dan minta user memilih port lain.
if ! server_is_up && port_is_open; then
  echo "ERROR: port $PORT sudah dipakai proses lain (bukan server SIAGA)."
  echo "Jalankan dengan port lain, misalnya:  PORT=8001 ./share.sh"
  exit 1
fi

# ── Cloudflare Quick Tunnel ───────────────────────────────────────────────
echo "Membuka Cloudflare Quick Tunnel untuk http://$HOST:$PORT ..."

CF_ARGS=(tunnel --url "http://$HOST:$PORT" --no-autoupdate --loglevel info)
if [[ -n "$CF_PROTOCOL" ]]; then
  CF_ARGS+=(--protocol "$CF_PROTOCOL")
fi

cloudflared "${CF_ARGS[@]}" >"$CF_LOG" 2>&1 &
CF_PID=$!

for _ in $(seq 1 60); do
  URL="$(grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' "$CF_LOG" 2>/dev/null | head -n 1 || true)"

  if [[ -n "$URL" ]]; then
    break
  fi

  if ! kill -0 "$CF_PID" 2>/dev/null; then
    break
  fi

  sleep 0.5
done

if [[ -z "$URL" ]]; then
  echo "ERROR: gagal mendapatkan URL Cloudflare."
  echo "----- log cloudflared -----"
  tail -n 30 "$CF_LOG" 2>/dev/null || true
  exit 1
fi

# ── Server Laravel ────────────────────────────────────────────────────────
if server_is_up; then
  echo "Server SIAGA sudah berjalan di port $PORT — memakai server yang ada."
else
  echo "Menjalankan server Laravel di http://$HOST:$PORT ..."

  # APP_URL di-set ke URL tunnel supaya tautan absolut (mis. signed URL) ikut
  # memakai domain publik selama sesi share ini.
  APP_URL="$URL" APP_PUBLIC_URL="$URL" \
    php artisan serve --host="$HOST" --port="$PORT" >"$SERVER_LOG" 2>&1 &
  SERVER_PID=$!

  for _ in $(seq 1 40); do
    if server_is_up; then
      break
    fi

    if ! kill -0 "$SERVER_PID" 2>/dev/null; then
      break
    fi

    sleep 0.5
  done

  if ! server_is_up; then
    echo "ERROR: server Laravel gagal start di port $PORT."
    echo "----- log server -----"
    tail -n 30 "$SERVER_LOG" 2>/dev/null || true
    exit 1
  fi
fi

cat <<EOF

============================================================
  SIAGA siap dibagikan
============================================================
  Publik : $URL
  Lokal  : http://$HOST:$PORT
  Log    : $TMP_DIR

  URL ini aktif selama script berjalan.
  Tekan Ctrl+C untuk menutup tunnel & server.
============================================================

EOF

# Tunggu proses tunnel; kalau cloudflared mati (mis. jaringan putus), script
# ikut berhenti dan cleanup berjalan.
wait "$CF_PID" || true
