# Hướng dẫn chạy nhanh UTC-eLibrary

> **Mục đích:** Không quên bước khi chuyển máy / lên VPS / Docker.  
> **Stack:** Laravel 12 + Vue 3 (Inertia) + MySQL + JWT API `/api/v1`.  
> **Chọn môi trường:** đặt `DEPLOY_PROFILE` trong `.env` → `local` | `vps`.

### Docker (một lệnh — khuyến nghị VPS / AWS EC2)

```bash
cp .env.docker.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Chi tiết: [`docs/deployment/docker.md`](deployment/docker.md).

---

## 1. Bảng chọn nhanh (đọc trước)

| Hạng mục | **local** (dev) | **vps** (production) |
|----------|-----------------|----------------------|
| `DEPLOY_PROFILE` | `local` | `vps` |
| `APP_DEBUG` | `true` | `false` |
| Redis | Có (khuyến nghị) | Có |
| `CACHE_STORE` | `redis` | `redis` |
| `SESSION_DRIVER` | `redis` hoặc `database` | `redis` / `database` |
| `QUEUE_CONNECTION` | `redis` hoặc `database` | `redis` |
| `DIGITAL_PREVIEW_DISPATCH_SYNC` | **`true`** | `true` *hoặc* `false`+worker |
| Preview PDF trên host | Có (FPDI/qpdf/Poppler) | Có |
| `queue:work` | Chỉ khi `DIGITAL_PREVIEW_DISPATCH_SYNC=false` | **Nên chạy** (Supervisor / Docker scheduler) |
| Cron `schedule:run` | Tùy chọn | **Bắt buộc** |
| Upload PDF tối đa | Theo `DIGITAL_PDF_MAX_KB` | Theo env |

Logic profile: `app/Enums/DeployProfile.php`, `config/deploy.php`.

---

## 2. Lần đầu trên máy (mọi profile)

```bash
cp .env.example .env
# Sửa .env: DB, APP_URL, DEPLOY_PROFILE, Redis, …

composer install
php artisan key:generate
php artisan migrate
php artisan db:seed          # nếu cần dữ liệu mẫu

npm install
npm run build                # production; dev dùng npm run dev

php artisan storage:link
```

**Frontend dev (2 terminal):**

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Mở: `APP_URL` (mặc định `http://localhost:8000`).

---

## 3. Local — chạy nhanh nhất hàng ngày

### 3.1 `.env` gợi ý

```env
DEPLOY_PROFILE=local
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
BASE_URL=http://localhost:8000

DB_CONNECTION=mysql
# …

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Preview chạy ngay sau upload — KHÔNG cần queue:work
DIGITAL_PREVIEW_DISPATCH_SYNC=true

# Poppler (PNG xem trước) — Windows ví dụ:
PDFTOPPM_BINARY=C:/path/to/pdftoppm.exe
```

### 3.2 Mỗi lần `git pull`

```bash
composer install
php artisan migrate
npm install && npm run build    # hoặc chỉ npm run dev khi đang dev UI

php artisan optimize:clear
php artisan config:clear
```

### 3.3 Preview PDF không chạy?

1. Kiểm tra `DIGITAL_PREVIEW_DISPATCH_SYNC=true`.
2. Nếu `QUEUE_CONNECTION=redis` mà **không** chạy worker → preview **treo** → bật sync hoặc chạy:
   ```bash
   php artisan queue:work redis --queue=default
   ```
3. Tạo lại thủ công:
   ```bash
   php artisan digital-assets:regenerate-previews --asset=ID
   ```
4. Xem log: `storage/logs/laravel.log` (`digital_asset.preview_job_failed`).

### 3.4 Test nhanh

```bash
php artisan test --parallel
# hoặc một nhóm:
php artisan test tests/Feature/Backend/
```

---

## 4. VPS / Docker — production

### 4.1 `.env` gợi ý

```env
DEPLOY_PROFILE=vps
APP_ENV=production
APP_DEBUG=false
APP_URL=https://thu-vien.example.edu.vn
BASE_URL=https://thu-vien.example.edu.vn
API_ALLOWED_DOMAINS=https://thu-vien.example.edu.vn

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Cách A — đơn giản, không cần worker (PDF nhỏ / ít đồng thời):
DIGITAL_PREVIEW_DISPATCH_SYNC=true

# Cách B — nhiều user, PDF lớn (khuyến nghị production):
# DIGITAL_PREVIEW_DISPATCH_SYNC=false
# + Supervisor hoặc container scheduler (xem 4.3)
```

Cài trên server (nếu chưa có): **qpdf** hoặc **ghostscript**, **poppler-utils** (`pdftoppm`), tùy chọn **php-imagick**.  
Docker EC2: xem [`docs/deployment/docker.md`](deployment/docker.md).

### 4.2 Deploy / cập nhật code

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Sau khi đổi `.env`:** luôn `php artisan config:clear` (hoặc `config:cache` lại).

### 4.3 Queue worker (khi `DIGITAL_PREVIEW_DISPATCH_SYNC=false`)

Supervisor ví dụ:

```ini
[program:utc-elibrary-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/utc-elibrary/artisan queue:work redis --sleep=3 --tries=2 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/utc-elibrary/storage/logs/queue.log
```

Docker: service `scheduler` trong `docker-compose.yml` / `docker-compose.ec2.yml`.

### 4.4 Cron (bắt buộc)

```cron
* * * * * cd /var/www/utc-elibrary && php artisan schedule:run >> /dev/null 2>&1
```

Lệnh đã lên lịch (`routes/console.php`): quá hạn mượn, nhắc trả sách, hết hạn đơn tài liệu số, đồng bộ kho, v.v.

### 4.5 Media / PDF lớn

Xem `docs/deployment/cloudflare-r2-media.md` — `MEDIA_DISK`, `DIGITAL_ASSETS_DISK`, R2.

---

## 5. Xóa cache — khi nào & lệnh gì

| Tình huống | Lệnh |
|------------|------|
| Đổi `.env`, config lạ | `php artisan config:clear` |
| Route 404 sau deploy | `php artisan route:clear` + `route:cache` (prod) |
| View/Inertia lỗi template cũ | `php artisan view:clear` |
| Không chắc | `php artisan optimize:clear` |
| Production ổn định | `php artisan config:cache` `route:cache` `view:cache` |

**Dev:** ưu tiên `optimize:clear`, tránh `config:cache` khi đang sửa `.env` liên tục.

---

## 6. Tài liệu số & xem trước (tóm tắt)

| Việc | Cách |
|------|------|
| Upload / duyệt đồ án | API + admin; preview `pending` → job/sync |
| `DIGITAL_PREVIEW_DISPATCH_SYNC=true` | Tạo preview **ngay sau** HTTP (không cần worker) |
| `false` + Redis queue | Cần `php artisan queue:work` |
| User bấm xem trước chưa có file | Trang báo «đang tạo» / «chưa có»; không build sync trên request |
| Tạo lại preview | `php artisan digital-assets:regenerate-previews [--asset=ID] [--force]` |
| Cột DB | `digital_assets.preview_status`: pending / processing / ready / failed |

---

## 7. Thông báo & cron nghiệp vụ

- Chuông thông báo: API `GET /api/v1/me/notifications` (JWT + session).
- Nộp đồ án → staff nhận digest «chờ duyệt»; duyệt/từ chối → độc giả nhận thông báo.
- Cron: `php artisan schedule:run` mỗi phút (VPS / Docker scheduler).

---

## 8. Git — quy ước ngắn

```bash
git pull
# … migrate, build, clear cache (mục 3–4)

git add …
git commit -m "feat(scope): mô tả ngắn"
git push origin main
```

Không commit `.env`, `.tmp/`, `vendor/`, `node_modules/`.

---

## 9. Sự cố thường gặp

| Triệu chứng | Nguyên nhân thường gặp | Xử lý |
|-------------|------------------------|--------|
| Preview mãi «chưa có» | Job trong Redis, không có worker | `DIGITAL_PREVIEW_DISPATCH_SYNC=true` hoặc `queue:work` |
| Lưu sách 500, mô tả dài | Vượt giới hạn cột (đã LONGTEXT) | Rút gọn / chỉ dán tóm tắt; kiểm tra `migrate` |
| API 401 sau login web | JWT/session | Đăng nhập lại; xem `Init` middleware |
| 500 sau đổi `.env` | Config cache cũ | `php artisan config:clear` |
| EC2 không mở web | Security Group / HTTPS nhầm | Mở port 80; dùng `http://IP`; xem `docs/deployment/docker.md` |

---

## 10. Tài liệu liên quan

- Nghiệp vụ: `docs/ai/context-utc-library.md`
- R2 / media: `docs/deployment/cloudflare-r2-media.md`
- Docker / EC2: `docs/deployment/docker.md`
- Mẫu biến môi trường: `.env.example`
- Quy tắc agent: `.cursor/rules/utc-elibrary-core.mdc`

---

*Cập nhật theo codebase: preview queue, `DIGITAL_PREVIEW_DISPATCH_SYNC`, thông báo đồ án, LONGTEXT mô tả sách.*
