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
    echo "==> [deploy] Git sync origin/${GIT_BRANCH} (server luôn khớp remote)"
    git fetch origin "${GIT_BRANCH}"
    git checkout "${GIT_BRANCH}"
  # EC2 không giữ sửa tay trên file tracked — tránh pull bị chặn.
    if ! git diff --quiet || ! git diff --cached --quiet; then
        echo "==> [deploy] Phát hiện thay đổi local — reset về origin/${GIT_BRANCH}"
    fi
    git reset --hard "origin/${GIT_BRANCH}"
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

echo "==> [deploy] Artisan migrate (bỏ qua bảng/cột đã có từ DB import)"
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan migrate:existing-schema --force --no-interaction
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan config:clear --no-interaction
docker compose -f "${COMPOSE_FILE}" exec -T app sh -c 'rm -f bootstrap/cache/routes-*.php' 2>/dev/null || true
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan route:clear --no-interaction 2>/dev/null || true
docker compose -f "${COMPOSE_FILE}" exec -T app php artisan optimize:clear --no-interaction

echo "==> [deploy] Trạng thái container"
docker compose -f "${COMPOSE_FILE}" ps

echo "==> [deploy] Kiểm tra scheduler + queue (thông báo / job nền)"
if ! docker compose -f "${COMPOSE_FILE}" ps --status running 2>/dev/null | grep -qE 'scheduler|queue'; then
    echo "    Cảnh báo: chưa thấy container scheduler hoặc queue — chạy: docker compose -f ${COMPOSE_FILE} up -d scheduler queue"
fi

echo "==> [deploy] Xong. Nhớ Ctrl+F5 trên trình duyệt."
echo "    Thông báo & lịch: chỉnh .env (NOTIFICATION_*, LOAN_DUE_SOON_*, SCHEDULE_*) rồi config:clear."
