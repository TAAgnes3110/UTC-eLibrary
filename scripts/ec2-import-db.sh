#!/usr/bin/env bash
# Import database/utc_elibrary.sql vào MySQL container (không đụng .env).
# Chạy trên EC2: bash scripts/ec2-import-db.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE_FILE="${EC2_COMPOSE_FILE:-docker-compose.ec2.yml}"
DUMP="${1:-database/utc_elibrary.sql}"

if [[ ! -f .env ]]; then
    echo "Thiếu .env" >&2
    exit 1
fi

if [[ ! -f "$DUMP" ]]; then
    echo "Không tìm thấy dump: $DUMP" >&2
    exit 1
fi

DB_PASSWORD="$(grep -E '^DB_PASSWORD=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | xargs)"
DB_DATABASE="$(grep -E '^DB_DATABASE=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | xargs)"
DB_USERNAME="$(grep -E '^DB_USERNAME=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | xargs)"

echo "==> [import-db] Dump: $DUMP ($(du -h "$DUMP" | awk '{print $1}'))"
echo "==> [import-db] Database: $DB_DATABASE"

docker compose -f "$COMPOSE_FILE" exec -T mysql mysql -uroot -p"$DB_PASSWORD" -e "
DROP DATABASE IF EXISTS \`$DB_DATABASE\`;
CREATE DATABASE \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'%';
FLUSH PRIVILEGES;
"

echo "==> [import-db] Đang import (có thể mất 1–2 phút)..."
docker compose -f "$COMPOSE_FILE" exec -T mysql mysql -uroot -p"$DB_PASSWORD" "$DB_DATABASE" < "$DUMP"

echo "==> [import-db] migrate:existing-schema"
docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate:existing-schema --force --no-interaction
docker compose -f "$COMPOSE_FILE" exec -T app php artisan optimize:clear --no-interaction

echo "==> [import-db] Kiểm tra"
docker compose -f "$COMPOSE_FILE" exec -T mysql mysql -uroot -p"$DB_PASSWORD" "$DB_DATABASE" -e "
SELECT COUNT(*) AS loans FROM loans;
SELECT COUNT(*) AS borrow_pending FROM loan_borrow_requests WHERE status='pending';
SELECT COUNT(*) AS renewals FROM loan_renewal_requests;
SELECT COUNT(*) AS books FROM books;
"

echo "==> [import-db] Xong."
