#!/usr/bin/env bash
# Đẩy .env local lên EC2 và áp dụng (backup bản cũ trên server).
# Chạy từ máy dev (Git Bash / WSL / macOS):
#
#   export EC2_HOST=3.0.56.220
#   export EC2_USER=ubuntu
#   export EC2_SSH_KEY=~/.ssh/your-key.pem
#   export EC2_APP_PATH=/home/ubuntu/utc-elibrary
#   bash scripts/sync-env-to-ec2.sh
#
# Tùy chọn: SYNC_ENV_SKIP_APPLY=1 — chỉ upload, không recreate container.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

EC2_HOST="${EC2_HOST:-}"
EC2_USER="${EC2_USER:-ubuntu}"
EC2_APP_PATH="${EC2_APP_PATH:-/home/ubuntu/utc-elibrary}"
EC2_SSH_PORT="${EC2_SSH_PORT:-22}"
EC2_SSH_KEY="${EC2_SSH_KEY:-}"

if [[ -z "${EC2_HOST}" ]]; then
    echo "Thiếu EC2_HOST (ví dụ: export EC2_HOST=3.0.56.220)" >&2
    exit 1
fi

if [[ ! -f .env ]]; then
    echo "Không tìm thấy .env trong $ROOT" >&2
    exit 1
fi

SSH_OPTS=(-p "${EC2_SSH_PORT}" -o StrictHostKeyChecking=accept-new)
SCP_OPTS=(-P "${EC2_SSH_PORT}" -o StrictHostKeyChecking=accept-new)
if [[ -n "${EC2_SSH_KEY}" ]]; then
    SSH_OPTS+=(-i "${EC2_SSH_KEY}")
    SCP_OPTS+=(-i "${EC2_SSH_KEY}")
fi

REMOTE="${EC2_USER}@${EC2_HOST}"
STAMP="$(date +%Y%m%d-%H%M%S)"

echo "==> [sync-env] Backup .env trên server → .env.backup.${STAMP}"
ssh "${SSH_OPTS[@]}" "${REMOTE}" "cd '${EC2_APP_PATH}' && test -f .env && cp .env .env.backup.${STAMP} || true"

echo "==> [sync-env] Upload .env → ${EC2_APP_PATH}/.env"
scp "${SCP_OPTS[@]}" .env "${REMOTE}:${EC2_APP_PATH}/.env"

if [[ "${SYNC_ENV_SKIP_APPLY:-0}" == "1" ]]; then
    echo "==> [sync-env] Bỏ qua áp dụng (SYNC_ENV_SKIP_APPLY=1). Trên server chạy: bash scripts/ec2-apply-env.sh"
    exit 0
fi

echo "==> [sync-env] Áp dụng trên server (config:clear + recreate container)"
ssh "${SSH_OPTS[@]}" "${REMOTE}" bash -s -- "${EC2_APP_PATH}" <<'REMOTE_APPLY'
set -euo pipefail
APP_PATH="$1"
cd "$APP_PATH"
COMPOSE_FILE="${EC2_COMPOSE_FILE:-docker-compose.ec2.yml}"
if [[ -f scripts/ec2-apply-env.sh ]]; then
  bash scripts/ec2-apply-env.sh
else
  echo "==> [env] ec2-apply-env.sh chưa có — chạy docker trực tiếp (git pull sau để dùng script)"
  docker compose -f "${COMPOSE_FILE}" up -d --force-recreate app scheduler queue
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan config:clear --no-interaction
  docker compose -f "${COMPOSE_FILE}" exec -T app php artisan optimize:clear --no-interaction
  docker compose -f "${COMPOSE_FILE}" ps
fi
REMOTE_APPLY

echo "==> [sync-env] Xong."
