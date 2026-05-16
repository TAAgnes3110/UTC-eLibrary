#!/usr/bin/env bash
set -euo pipefail

# Prepare a production-ready package for InfinityFree deploy.
# Usage: bash scripts/prepare-infinityfree-deploy.sh

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M%S)"
PKG_DIR="${ROOT_DIR}/dist"
PKG_FILE="${PKG_DIR}/utc-elibrary-infinityfree-${STAMP}.zip"

required_files=(
  "vendor/autoload.php"
  "vendor/composer/autoload_real.php"
  "vendor/composer/autoload_static.php"
)

cd "${ROOT_DIR}"

echo "==> Build production vendor"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "==> Rebuild Laravel caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Verify critical vendor files"
for file in "${required_files[@]}"; do
  if [[ ! -f "${ROOT_DIR}/${file}" ]]; then
    echo "Missing required file: ${file}" >&2
    exit 1
  fi
done

mkdir -p "${PKG_DIR}"
rm -f "${PKG_DIR}"/utc-elibrary-infinityfree-*.zip

echo "==> Create deploy package: ${PKG_FILE}"
python - <<'PY' "${ROOT_DIR}" "${PKG_FILE}"
import os
import sys
import zipfile

root = sys.argv[1]
zip_path = sys.argv[2]

exclude_dirs = {
    ".git",
    ".github",
    ".cursor",
    "node_modules",
    "tests",
    "storage/logs",
    "storage/framework/cache",
    "storage/framework/sessions",
    "storage/framework/testing",
    "storage/framework/views",
}
exclude_files = {
    ".env",
    ".env.local",
}

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED, strict_timestamps=False) as zf:
    for base, dirs, files in os.walk(root):
        rel_base = os.path.relpath(base, root).replace("\\", "/")
        if rel_base == ".":
            rel_base = ""

        kept_dirs = []
        for d in dirs:
            rel = f"{rel_base}/{d}" if rel_base else d
            if rel in exclude_dirs or d in exclude_dirs:
                continue
            kept_dirs.append(d)
        dirs[:] = kept_dirs

        for file_name in files:
            rel = f"{rel_base}/{file_name}" if rel_base else file_name
            rel = rel.replace("\\", "/")
            if file_name in exclude_files:
                continue
            if rel.startswith("dist/"):
                continue
            zf.write(os.path.join(base, file_name), rel)
PY

echo "==> Done"
echo "Package ready: ${PKG_FILE}"
echo "Upload this zip to htdocs, extract, then verify vendor/composer/autoload_static.php exists on host."
echo ""
echo "On host: cp .env.example .env (hoặc upload .env), set DEPLOY_PROFILE=infinityfree + DB + APP_URL"
echo "Preview PDF: run on dev machine before upload:"
echo "  php artisan digital-assets:regenerate-previews --force"
echo "Then upload storage/app/private/utc-elibrary/... (PDF gốc + */preview.pdf)"
