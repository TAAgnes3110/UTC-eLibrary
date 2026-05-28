#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

MODE="all"
SMOKE="0"
COMPOSE_FILE="${EC2_COMPOSE_FILE:-docker-compose.ec2.yml}"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --code)
            MODE="code"
            shift
            ;;
        --env)
            MODE="env"
            shift
            ;;
        --all)
            MODE="all"
            shift
            ;;
        --smoke)
            SMOKE="1"
            shift
            ;;
        -h|--help)
            cat <<'EOF'
UTC eLibrary - Release quick helper

Usage:
  bash scripts/release-quick.sh [--code|--env|--all] [--smoke]

Modes:
  --code   Pull code moi + ec2-deploy.sh
  --env    Chi apply .env (ec2-apply-env.sh)
  --all    Code + env (mac dinh)

Options:
  --smoke  Chay them check nhanh sau release
EOF
            exit 0
            ;;
        *)
            echo "Unknown argument: $1"
            echo "Run with --help to see usage."
            exit 1
            ;;
    esac
done

echo "==> [quick-release] ROOT: $ROOT"
echo "==> [quick-release] MODE: $MODE"

if [[ "$MODE" == "code" || "$MODE" == "all" ]]; then
    echo "==> [quick-release] Deploy code"
    bash scripts/ec2-deploy.sh
fi

if [[ "$MODE" == "env" || "$MODE" == "all" ]]; then
    echo "==> [quick-release] Apply env"
    bash scripts/ec2-apply-env.sh
fi

if [[ "$SMOKE" == "1" ]]; then
    echo "==> [quick-release] Smoke checks"
    docker compose -f "${COMPOSE_FILE}" ps
    docker compose -f "${COMPOSE_FILE}" exec app php artisan about >/tmp/quick-release-about.txt
    sed -n '1,20p' /tmp/quick-release-about.txt || true
fi

echo "==> [quick-release] Done."
