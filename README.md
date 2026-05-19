# UTC eLibrary

Hệ thống quản lý thư viện số — **Đại học Giao thông Vận tải (UTC)**.  
Stack: **Laravel 12** + **Vue 3 (Inertia)** + **MySQL** + **Redis**.

## Tính năng chính

- **Độc giả:** tra cứu, mượn/trả, thẻ thư viện, tài liệu số, nộp đồ án/luận văn
- **Admin / thủ thư:** quản lý sách, kho, user, thẻ, phiếu mượn, duyệt hồ sơ, thông báo
- **API:** `/api/v1` (JWT + session Inertia)

## Cài đặt local

```bash
git clone https://github.com/TAAgnes3110/UTC-eLibrary.git
cd UTC-eLibrary
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve   # terminal 1
npm run dev         # terminal 2
```

Mở: `http://localhost:8000`

### Tài khoản seed (mặc định)

| Vai trò        | Email                    | Mật khẩu   |
|----------------|--------------------------|------------|
| Super Admin    | `superadmin@utc.edu.vn`  | `password` |
| Admin          | `admin@utc.edu.vn`       | `password` |
| Thủ thư        | `librarian@utc.edu.vn`   | `password` |
| Sinh viên      | `student@st.utc.edu.vn`  | `password` |

## Deploy EC2 (Docker)

Trên server (ví dụ `~/utc-elibrary`):

```bash
cd ~/utc-elibrary
git pull origin main
bash scripts/ec2-deploy.sh
```

Script tự: pull → `ec2-prepare-build.sh` → build image → `up -d` → **`migrate:existing-schema`** → clear cache.

Chi tiết: [`docs/deployment/docker.md`](docs/deployment/docker.md)

### DB import từ backup SQL

Nếu restore file `.sql` cũ, bảng đã có nhưng thiếu dòng trong `migrations`:

```bash
php artisan migrate:existing-schema --force
```

Lệnh chạy từng migration pending; nếu MySQL báo **bảng/cột đã tồn tại** → ghi nhận migration và **tiếp tục** (không dừng giữa chừng).

## CI/CD (tự deploy sau push `main`)

1. Cấu hình GitHub **Secrets**: `EC2_HOST`, `EC2_USER`, `EC2_SSH_KEY` (và tùy chọn `EC2_APP_PATH`)
2. Push lên `main` → workflow **Deploy EC2** chạy `scripts/ec2-deploy.sh` trên server

Hướng dẫn: [`docs/deployment/ec2-cicd.md`](docs/deployment/ec2-cicd.md)

**Lưu ý:** Chỉ `git pull` trên EC2 **không đủ** — code PHP nằm trong Docker image, cần `build app` (deploy script đã gồm).

## `.env` gợi ý (EC2, HTTP)

```env
DEPLOY_PROFILE=vps
APP_URL=http://<IP-EC2>
SESSION_SECURE_COOKIE=false
SANCTUM_STATEFUL_DOMAINS=<IP-EC2>,localhost,127.0.0.1
DIGITAL_PREVIEW_DISPATCH_SYNC=true
QUEUE_CONNECTION=redis
```

## Kiểm tra & chất lượng

```bash
npm run build
php artisan route:list
php artisan test
vendor/bin/pint
```

## Tài liệu thêm

- [`docs/README.md`](docs/README.md) — mục lục docs
- [`docs/HUONG-DAN-CHAY-NHANH.md`](docs/HUONG-DAN-CHAY-NHANH.md)
- [`docs/API.md`](docs/API.md)
- [`docs/ai/context-utc-library.md`](docs/ai/context-utc-library.md) — nghiệp vụ UTC

## Ghi chú

- Không commit `.env`, key, credentials
- `resource_type`: `textbook` | `reference` | `digital`
- Sau deploy: **Ctrl+F5** trình duyệt để nạp JS mới
