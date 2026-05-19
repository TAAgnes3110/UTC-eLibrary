#!/usr/bin/env bash
# Khởi động UTC-eLibrary bằng Docker (lần đầu hoặc sau git pull)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env ]]; then
    echo "Chưa có .env — sao chép từ .env.docker.example"
    cp .env.docker.example .env
    echo "Đã tạo .env — hãy sửa APP_URL, DB_PASSWORD, … rồi chạy lại script."
    exit 0
fi

docker compose up -d --build

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "Tạo APP_KEY…"
    docker compose exec -T app php artisan key:generate --force
fi

docker compose exec -T app php artisan migrate --force --no-interaction

echo ""
echo "Xong. Mở: ${APP_URL:-http://localhost} (xem APP_URL / APP_PORT trong .env)"
