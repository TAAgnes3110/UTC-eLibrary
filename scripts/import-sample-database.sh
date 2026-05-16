#!/usr/bin/env bash
# Nạp Dump20260505.sql (đã gộp post_import) + migrate nếu cần (.env).
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [[ ! -f .env ]]; then
  echo "Thiếu file .env" >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a
source .env
set +a

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-utc_elibrary}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

resolve_mysql() {
  if [[ -n "${MYSQL_BIN:-}" && -x "${MYSQL_BIN}" ]]; then
    echo "${MYSQL_BIN}"
    return
  fi
  if command -v mysql >/dev/null 2>&1; then
    command -v mysql
    return
  fi
  local win="/c/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe"
  if [[ -x "${win}" ]]; then
    echo "${win}"
    return
  fi
  echo "Không tìm thấy mysql. Cài MySQL client hoặc đặt MYSQL_BIN trong môi trường." >&2
  exit 127
}

MYSQL="$(resolve_mysql)"
MYSQL_OPTS=(-h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}")
if [[ -n "${DB_PASSWORD}" ]]; then
  MYSQL_OPTS+=(-p"${DB_PASSWORD}")
fi

DUMP="${ROOT_DIR}/database/Dump20260505.sql"

echo "==> Tạo lại database ${DB_DATABASE}"
"${MYSQL}" "${MYSQL_OPTS[@]}" -e "DROP DATABASE IF EXISTS \`${DB_DATABASE}\`; CREATE DATABASE \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> Import ${DUMP}"
"${MYSQL}" "${MYSQL_OPTS[@]}" "${DB_DATABASE}" < "${DUMP}"

echo "==> Migrate schema còn thiếu (nếu có)"
php artisan migrate --force

echo "==> Xong. Chạy: php artisan digital-assets:regenerate-previews (khi đã có file PDF trên disk)."
