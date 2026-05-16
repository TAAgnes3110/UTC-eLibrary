#!/usr/bin/env bash
# Tạo lại database/Dump20260505.sql khớp migration đã squash (schema + dữ liệu mẫu OTL).
# Chạy từ thư mục gốc repo; cần .env và mysql/mysqldump.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

if [[ ! -f .env ]]; then
  echo "Thiếu file .env" >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a
source .env
set +a

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-utc_elibrary}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

resolve_mysql() {
  if [[ -n "${MYSQL_BIN:-}" && -x "${MYSQL_BIN}" ]]; then
    echo "${MYSQL_BIN}"
    return
  fi
  if command -v mysql >/dev/null 2>&1; then
    command -v mysql
    return
  fi
  local win="/c/Program Files/MySQL/MySQL Server 8.0/bin/mysql.exe"
  if [[ -x "${win}" ]]; then
    echo "${win}"
    return
  fi
  echo "Không tìm thấy mysql" >&2
  exit 127
}

resolve_mysqldump() {
  if [[ -n "${MYSQLDUMP_BIN:-}" && -x "${MYSQLDUMP_BIN}" ]]; then
    echo "${MYSQLDUMP_BIN}"
    return
  fi
  if command -v mysqldump >/dev/null 2>&1; then
    command -v mysqldump
    return
  fi
  local win="/c/Program Files/MySQL/MySQL Server 8.0/bin/mysqldump.exe"
  if [[ -x "${win}" ]]; then
    echo "${win}"
    return
  fi
  echo "Không tìm thấy mysqldump" >&2
  exit 127
}

MYSQL="$(resolve_mysql)"
MYSQLDUMP="$(resolve_mysqldump)"

MYSQL_OPTS=(-h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}")
if [[ -n "${DB_PASSWORD}" ]]; then
  MYSQL_OPTS+=(-p"${DB_PASSWORD}")
fi

LEGACY="${ROOT_DIR}/database/Dump20260505.sql"
OUT="${ROOT_DIR}/database/Dump20260505.sql"
TMP_DB="${DB_DATABASE}_dump_rebuild"

echo "==> DB tạm: ${TMP_DB}"
"${MYSQL}" "${MYSQL_OPTS[@]}" -e "DROP DATABASE IF EXISTS \`${TMP_DB}\`; CREATE DATABASE \`${TMP_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "==> Import dump legacy"
"${MYSQL}" "${MYSQL_OPTS[@]}" "${TMP_DB}" < "${LEGACY}"

echo "==> ALTER schema (squashed migrations)"
set +e
"${MYSQL}" "${MYSQL_OPTS[@]}" "${TMP_DB}" <<'SQL'
-- classifications: bỏ parent_id
ALTER TABLE `classifications` DROP FOREIGN KEY `classifications_parent_id_foreign`;
ALTER TABLE `classifications` DROP INDEX `classifications_parent_id_foreign`;
ALTER TABLE `classifications` DROP COLUMN `parent_id`;

-- books
ALTER TABLE `books`
  ADD COLUMN `view_count` bigint unsigned NOT NULL DEFAULT 0
    COMMENT 'Luot xem trang chi tiet (sach in)' AFTER `quantity`;
ALTER TABLE `books` ADD INDEX `books_resource_deleted_created_id_idx` (`resource_type`,`deleted_at`,`created_at`,`id`);

-- digital_assets
ALTER TABLE `digital_assets`
  ADD COLUMN `view_count` bigint unsigned NOT NULL DEFAULT 0
    COMMENT 'Luot xem + xem truoc PDF' AFTER `byte_size`,
  ADD COLUMN `download_count` bigint unsigned NOT NULL DEFAULT 0
    COMMENT 'Luot tai PDF goc' AFTER `view_count`,
  ADD COLUMN `preview_display` json DEFAULT NULL
    COMMENT 'PNG preview_display.pages' AFTER `download_count`;

-- news_posts (nếu dump cũ chưa có type)
SET @has_type := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'news_posts' AND COLUMN_NAME = 'type'
);
SET @sql := IF(@has_type = 0,
  'ALTER TABLE `news_posts` ADD COLUMN `type` varchar(20) NOT NULL DEFAULT ''news'' COMMENT ''Loại bài viết: news|notice'' AFTER `status`, ADD INDEX `news_posts_type_index` (`type`)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- index bổ sung (dump cũ có thể đã có một phần — bỏ qua lỗi trùng)
ALTER TABLE `news_posts`
  ADD INDEX `news_posts_status_type_published_id_idx` (`status`,`type`,`published_at`,`id`),
  ADD INDEX `news_posts_type_status_published_id_idx` (`type`,`status`,`published_at`,`id`),
  ADD INDEX `news_posts_status_id_idx` (`status`,`id`),
  ADD INDEX `news_posts_status_type_id_idx` (`status`,`type`,`id`);

ALTER TABLE `users`
  ADD INDEX `users_active_type_id_idx` (`is_active`,`user_type`,`id`),
  ADD INDEX `users_type_id_idx` (`user_type`,`id`);

ALTER TABLE `library_cards`
  ADD INDEX `library_cards_workflow_created_id_idx` (`workflow_status`,`created_at`,`id`),
  ADD INDEX `library_cards_holder_created_id_idx` (`holder_type`,`created_at`,`id`),
  ADD INDEX `library_cards_status_created_id_idx` (`status`,`created_at`,`id`),
  ADD INDEX `library_cards_wf_holder_status_created_id_idx` (`workflow_status`,`holder_type`,`status`,`created_at`,`id`),
  ADD INDEX `library_cards_full_name_id_idx` (`full_name`,`id`);

-- migrations: bỏ migration đã squash
DELETE FROM `migrations` WHERE `migration` IN (
  '2026_05_02_200000_normalize_classifications_to_roots',
  '2026_05_05_230000_add_type_to_news_posts_table',
  '2026_05_06_214500_add_performance_indexes_to_news_posts_table',
  '2026_05_06_230500_add_admin_filter_indexes_to_users_table',
  '2026_05_06_231500_add_admin_filter_indexes_to_news_posts_and_library_cards',
  '2026_05_07_160500_normalize_legacy_onsite_access_mode',
  '2026_05_07_170500_add_admin_list_indexes_to_books_table',
  '2026_05_15_180000_add_view_count_to_books_table',
  '2026_05_15_190000_add_view_and_download_counts_to_digital_assets_table',
  '2026_05_16_120000_add_preview_display_to_digital_assets_table'
);
SQL
set -e

echo "==> Export dump mới"
BACKUP="${OUT}.bak.$(date +%Y%m%d%H%M%S)"
cp "${OUT}" "${BACKUP}"
"${MYSQLDUMP}" "${MYSQL_OPTS[@]}" \
  --default-character-set=utf8mb4 \
  --single-transaction --routines --triggers \
  --databases "${TMP_DB}" \
  | sed "s/\`${TMP_DB}\`/\`${DB_DATABASE}\`/g; /^CREATE DATABASE/d; /^USE \`/d" \
  > "${OUT}.new"

mv "${OUT}.new" "${OUT}"
"${MYSQL}" "${MYSQL_OPTS[@]}" -e "DROP DATABASE IF EXISTS \`${TMP_DB}\`;"

echo "==> Xong: ${OUT}"
echo "    Backup: ${BACKUP}"
echo "    Tiếp: cập nhật post_import.sql nếu cần, rồi chạy scripts/import-sample-database.sh để kiểm tra."
