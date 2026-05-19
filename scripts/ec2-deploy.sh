#!/usr/bin/env bash
# Deploy UTC-eLibrary lên EC2 (Docker). Dùng thủ công hoặc GitHub Actions SSH.
# Chạy trên server: bash scripts/ec2-deploy.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

COMPOSE_FILE="${EC2_COMPOSE_FILE:-docker-compose.ec2.yml}"
GIT_BRANCH="${EC2_DEPLOY_BRANCH:-main}"
export COMPOSE_PARALLEL_LIMIT="${COMPOSE_PARALLEL_LIMIT:-1}"

echo "==> [deploy] Thư mục: $ROOT"

if [[ "${EC2_DEPLOY_SKIP_GIT:-0}" != "1" ]]; then
    echo "==> [deploy] Git pull origin/${GIT_BRANCH}"
    git fetch origin "${GIT_BRANCH}"
    git checkout "${GIT_BRANCH}"
    git pull --ff-only origin "${GIT_BRANCH}"
fi

echo "==> [deploy] Chuẩn bị vendor + Vite (host)"
chmod +x scripts/ec2-prepare-build.sh
bash scripts/ec2-prepare-build.sh

echo "==> [deploy] Docker build + up"
docker compose -f "${COMPOSE_FILE}" build app
docker compose -f "${COMPOSE_FILE}" up -d

echo "==> [deploy] Đợi MySQL..."
sleep 15

if [[ -f .env ]]; then
    DB_PASSWORD="$(grep -E '^DB_PASSWORD=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | xargs)"
    DB_DATABASE="$(grep -E '^DB_DATABASE=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | xargs)"
    DB_USERNAME="$(grep -E '^DB_USERNAME=' .env | head -1 | cut -d= -f2- | tr -d "\"'" | xargs)"
fi
DB_PASSWORD="${DB_PASSWORD:-secret}"
DB_DATABASE="${DB_DATABASE:-utc_elibrary}"
DB_USERNAME="${DB_USERNAME:-utc}"

echo "==> [deploy] Ghi nhận migration paywall nếu DB import cũ đã có bảng"
docker compose -f "${COMPOSE_FILE}" exec -T mysql mysql -u "${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "
INSERT INTO migrations (migration, batch)
SELECT '2026_05_12_120000_create_digital_asset_paywall_and_payment_tables', IFNULL(MAX(batch),0)+1 FROM migrations
WHERE NOT EXISTS (
  SELECT 1 FROM migrations WHERE migration = '2026_05_12_120000_create_digital_asset_paywall_and_payment_tables'
);
" 2>/dev/null || true

echo "==> [deploy] Artisan migrate + clear cache"
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan migrate --force --no-interaction
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan config:clear --no-interaction
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan optimize:clear --no-interaction

echo "==> [deploy] Trạng thái container"
docker compose -f "${COMPOSE_FILE}" ps

echo "==> [deploy] Xong. Nhớ Ctrl+F5 trên trình duyệt."
