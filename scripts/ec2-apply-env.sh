#!/usr/bin/env bash
# Áp dụng .env đã có trên EC2: clear cache + recreate container (không pull git).
# Chạy trên server: bash scripts/ec2-apply-env.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE_FILE="${EC2_COMPOSE_FILE:-docker-compose.ec2.yml}"

if [[ ! -f .env ]]; then
    echo "Thiếu .env trong $ROOT" >&2
    exit 1
fi

echo "==> [env] Áp dụng .env (compose: ${COMPOSE_FILE})"

docker compose -f "${COMPOSE_FILE}" exec -T app php artisan config:clear --no-interaction 2>/dev/null \
    || echo "    (app chưa chạy — bỏ qua config:clear trong container)"

docker compose -f "${COMPOSE_FILE}" up -d --force-recreate app scheduler queue

docker compose -f "${COMPOSE_FILE}" exec -T app php artisan config:clear --no-interaction
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan optimize:clear --no-interaction

echo "==> [env] Container:"
docker compose -f "${COMPOSE_FILE}" ps

echo "==> [env] Xong. Kiểm tra https://$(grep -E '^APP_URL=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | sed 's#/$##')"
