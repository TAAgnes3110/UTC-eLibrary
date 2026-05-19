#!/usr/bin/env bash
# Chuẩn bị vendor + public/build trên EC2 (t3.micro) TRƯỚC khi docker build.
# Chạy trong thư mục repo: bash scripts/ec2-prepare-build.sh
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> 1/3 Composer install (container tạm)..."
docker run --rm \
    -v "$PWD:/app" \
    -w /app \
    composer:2 \
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs
sudo chown -R "$(id -u):$(id -g)" vendor 2>/dev/null || true

echo "==> 2/3 npm ci + vite build (trên host, dùng swap)..."
if ! command -v node >/dev/null 2>&1; then
    echo "Cài Node 20..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt install -y nodejs
fi

export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=768}"
npm ci
npm run build

echo "==> 3/3 Kiểm tra public/build..."
test -f public/build/manifest.json

echo "Xong (vendor trong Docker build; public/build trên host). Chạy tiếp:"
echo "  COMPOSE_PARALLEL_LIMIT=1 docker compose -f docker-compose.ec2.yml build --no-cache app"
echo "  docker compose -f docker-compose.ec2.yml up -d"
