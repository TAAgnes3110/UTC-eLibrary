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
    SSH_KEY_FOR_OPENSSH="${EC2_SSH_KEY}"
    # Git Bash: OpenSSH cần đường dẫn Windows cho -i; MSYS_NO_PATHCONV làm hỏng cả key lẫn remote path.
    if [[ -n "${MSYSTEM:-}" ]] && command -v cygpath >/dev/null 2>&1; then
        SSH_KEY_FOR_OPENSSH="$(cygpath -w "${EC2_SSH_KEY}")"
    fi
    SSH_OPTS+=(-i "${SSH_KEY_FOR_OPENSSH}")
    SCP_OPTS+=(-i "${SSH_KEY_FOR_OPENSSH}")
fi

REMOTE="${EC2_USER}@${EC2_HOST}"
# MSYS: //home/... không bị đổi thành E:/Git/home/... khi truyền cho ssh/scp remote.
REMOTE_APP_PATH="${EC2_APP_PATH}"
if [[ -n "${MSYSTEM:-}" ]]; then
    REMOTE_APP_PATH="//${EC2_APP_PATH#/}"
fi

STAMP="$(date +%Y%m%d-%H%M%S)"

echo "==> [sync-env] Backup .env trên server → .env.backup.${STAMP}"
ssh "${SSH_OPTS[@]}" "${REMOTE}" "cd '${REMOTE_APP_PATH}' && test -f .env && cp .env .env.backup.${STAMP} || true"

echo "==> [sync-env] Upload .env → ${EC2_APP_PATH}/.env"
scp "${SCP_OPTS[@]}" .env "${REMOTE}:${REMOTE_APP_PATH}/.env"

if [[ "${SYNC_ENV_SKIP_APPLY:-0}" == "1" ]]; then
    echo "==> [sync-env] Bỏ qua áp dụng (SYNC_ENV_SKIP_APPLY=1). Trên server chạy: bash scripts/ec2-apply-env.sh"
    exit 0
fi

echo "==> [sync-env] Áp dụng trên server (config:clear + recreate container)"
ssh "${SSH_OPTS[@]}" "${REMOTE}" "cd '${REMOTE_APP_PATH}' && bash -c '
set -euo pipefail
COMPOSE_FILE=\"\${EC2_COMPOSE_FILE:-docker-compose.ec2.yml}\"
if [[ -f scripts/ec2-apply-env.sh ]]; then
  bash scripts/ec2-apply-env.sh
else
  echo \"==> [env] ec2-apply-env.sh chưa có — chạy docker trực tiếp\"
  docker compose -f \"\${COMPOSE_FILE}\" up -d --force-recreate app scheduler queue
  docker compose -f \"\${COMPOSE_FILE}\" exec -T app php artisan config:clear --no-interaction
  docker compose -f \"\${COMPOSE_FILE}\" exec -T app php artisan optimize:clear --no-interaction
  docker compose -f \"\${COMPOSE_FILE}\" ps
fi
'"

echo "==> [sync-env] Xong."
