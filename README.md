# UTC eLibrary

<p align="center">
  <strong>Hệ thống quản lý thư viện số — Đại học Giao thông Vận tải (UTC)</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12"/>
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white" alt="Vue 3"/>
  <img src="https://img.shields.io/badge/Inertia-2-9553E9" alt="Inertia"/>
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white" alt="MySQL 8"/>
  <img src="https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white" alt="Redis"/>
  <img src="https://img.shields.io/badge/Docker-EC2-2496ED?logo=docker&logoColor=white" alt="Docker"/>
</p>

<p align="center">
  <a href="http://3.0.56.220/"><strong>Demo (IP)</strong></a> ·
  <a href="http://kiet.mmoall.com/"><strong>Demo (domain)</strong></a> ·
  <a href="http://3.0.56.220/admin"><code>/admin</code></a>
</p>

<p align="center">
  <sub>Ưu tiên <code>http://</code> cho IP và domain (HTTPS cần Certbot hoặc Cloudflare SSL đúng chế độ).</sub>
</p>

<p align="center">
  <img src="readme/assets/architecture.svg" alt="Kiến trúc UTC eLibrary" width="720"/>
</p>

---

## Mục lục

1. [Tổng quan](#tổng-quan)
2. [Tính năng](#tính-năng)
3. [Ảnh minh họa & sơ đồ](#ảnh-minh-họa--sơ-đồ)
4. [Cài đặt local](#cài-đặt-local)
5. [Tài khoản demo](#tài-khoản-demo)
6. [Cấu trúc thư mục](#cấu-trúc-thư-mục)
7. [API & Postman](#api--postman)
8. [ERD cơ sở dữ liệu](#erd-cơ-sở-dữ-liệu)
9. [Nghiệp vụ UTC (tóm tắt)](#nghiệp-vụ-utc-tóm-tắt)
10. [AI / ECC (Cursor)](#ai--ecc-cursor)
11. [Deploy EC2 (Docker)](#deploy-ec2-docker)
12. [CI/CD](#cicd)
13. [Biến môi trường](#biến-môi-trường)
14. [Kiểm tra chất lượng](#kiểm-tra-chất-lượng)
15. [Ghi chú bảo mật](#ghi-chú-bảo-mật)
16. [Xử lý sự cố deploy](#xử-lý-sự-cố-deploy)

---

## Tổng quan

UTC eLibrary phục vụ:

| Đối tượng | Kênh | Mô tả |
|-----------|------|--------|
| **Độc giả** | Web reader (`/`) | Tra cứu, mượn/trả, thẻ, tài liệu số, thanh toán PDF |
| **Thủ thư / Admin** | Web admin (`/admin`) | Quản lý sách, kho, user, phiếu mượn, duyệt hồ sơ |
| **Client bên thứ ba** | REST `/api/v1` | JWT + header `domain` |

**Stack:** PHP 8.2+, Laravel 12, Vue 3 + Inertia, Vite, MySQL 8, Redis (cache/queue), Sanctum (session SPA), JWT (API).

---

## Tính năng

### Độc giả
- Tra cứu sách in & tài liệu số (đồ án, luận văn)
- Đăng ký / đăng nhập OTP, quên mật khẩu
- Làm thẻ thư viện (sinh viên, giảng viên, khách)
- Gửi yêu cầu mượn, xem phiếu mượn, gia hạn
- Nộp đồ án/luận văn để duyệt
- Giỏ mua quyền tải PDF, thanh toán SePay
- Thông báo trong app

### Admin / thủ thư
- CRUD sách in, **tài liệu số** (upload PDF atomic)
- Kho, phân loại, tủ/kệ, chính sách mượn (`loan_policies`)
- Phiếu mượn/trả, duyệt yêu cầu mượn/gia hạn
- Quản lý thẻ, user, RBAC (Spatie)
- Duyệt submission tài liệu số
- Tin tức, cấu hình thư viện & giá paywall

---

## Ảnh minh họa & sơ đồ

### Vai trò

<p align="center">
  <img src="readme/assets/roles.svg" alt="Vai trò hệ thống" width="640"/>
</p>

### Luồng mượn sách in

<p align="center">
  <img src="readme/assets/loan-flow.svg" alt="Luồng mượn sách" width="720"/>
</p>

### Giao diện (screenshot từ [demo EC2](http://3.0.56.220/))

Ảnh **full-page**, viewport **máy tính 1920×1080** (Chrome desktop, dark mode) — mỗi trang **một hàng**, `width="100%"`. Tái chụp: `node scripts/capture-readme-screenshots.mjs`.

---

#### Luồng trọng tâm — Mượn sách & phiếu mượn

**Quy định mượn sách** — `/quy-dinh/muon-sach`

<p align="center"><img src="readme/assets/screenshots/11-reader-borrowing-rules.png" alt="Quy định mượn sách" width="100%"/></p>

**Làm thẻ thư viện** — `/dich-vu/cap-the-thu-vien`

<p align="center"><img src="readme/assets/screenshots/09-reader-library-card.png" alt="Đăng ký thẻ thư viện" width="100%"/></p>

**Phiếu mượn của tôi (độc giả)** — `/dich-vu/phieu-muon`

<p align="center"><img src="readme/assets/screenshots/10-reader-loan-requests.png" alt="Phiếu mượn độc giả" width="100%"/></p>

**Duyệt yêu cầu mượn (thủ thư)** — `/admin/loans/borrow-requests`

<p align="center"><img src="readme/assets/screenshots/18-admin-borrow-requests.png" alt="Duyệt yêu cầu mượn" width="100%"/></p>

**Lập phiếu mượn tại quầy** — `/admin/loans/create`

<p align="center"><img src="readme/assets/screenshots/19-admin-loan-create.png" alt="Lập phiếu mượn" width="100%"/></p>

**Danh sách phiếu mượn** — `/admin/loans`

<p align="center"><img src="readme/assets/screenshots/08-admin-loans.png" alt="Quản lý phiếu mượn" width="100%"/></p>

**Chi tiết phiếu mượn** — `/admin/loans/{id}`

<p align="center"><img src="readme/assets/screenshots/21-admin-loan-detail.png" alt="Chi tiết phiếu mượn" width="100%"/></p>

**Gia hạn mượn (duyệt)** — `/admin/loans/renewal-requests`

<p align="center"><img src="readme/assets/screenshots/20-admin-renewal-requests.png" alt="Duyệt gia hạn mượn" width="100%"/></p>

---

#### Luồng trọng tâm — Thanh toán & tài liệu số

**Tra cứu & chi tiết sách** — `/tra-cuu-sach`, `/tra-cuu-sach/{id}`

<p align="center"><img src="readme/assets/screenshots/04-catalog.png" alt="Tra cứu sách" width="100%"/></p>

<p align="center"><img src="readme/assets/screenshots/05-book-detail.png" alt="Chi tiết sách" width="100%"/></p>

**Giỏ sách (mượn in)** — `/dich-vu/gio-sach` *(đã thêm sách từ tra cứu trước khi chụp)*

<p align="center"><img src="readme/assets/screenshots/12-reader-book-cart.png" alt="Giỏ mượn sách in có sách" width="100%"/></p>

**Giỏ mua tài liệu số** — `/dich-vu/gio-sach?tab=purchase`

<p align="center"><img src="readme/assets/screenshots/13-reader-digital-cart.png" alt="Giỏ mua PDF" width="100%"/></p>

**Thanh toán SePay — bước cuối (QR VietQR)** — `/dich-vu/thanh-toan` *(bước 3/3 sau «Đặt hàng»)*

<p align="center"><img src="readme/assets/screenshots/14-reader-payment.png" alt="Thanh toán QR SePay bước cuối" width="100%"/></p>

**Đơn hàng của tôi** — `/dich-vu/don-hang-cua-toi`

<p align="center"><img src="readme/assets/screenshots/15-reader-orders.png" alt="Đơn hàng tài liệu số" width="100%"/></p>

**Cấu hình giá paywall (admin)** — `/admin/library-settings/pricing`

<p align="center"><img src="readme/assets/screenshots/24-admin-digital-pricing.png" alt="Giá tải PDF" width="100%"/></p>

**Đồ án / luận văn (admin)** — `/admin/books/digital`

<p align="center"><img src="readme/assets/screenshots/07-admin-digital-books.png" alt="Quản lý đồ án luận văn" width="100%"/></p>

**Duyệt bài nộp tài liệu số** — `/admin/books/digital/submissions`

<p align="center"><img src="readme/assets/screenshots/26-admin-digital-submissions.png" alt="Duyệt nộp tài liệu số" width="100%"/></p>

**Dịch vụ tài liệu số (độc giả)** — `/dich-vu/tai-lieu-so`

<p align="center"><img src="readme/assets/screenshots/16-reader-digital-documents.png" alt="Dịch vụ tài liệu số" width="100%"/></p>

---

#### Thẻ thư viện & quản trị khác

**Thẻ đã cấp** — `/admin/library-cards`

<p align="center"><img src="readme/assets/screenshots/22-admin-library-cards.png" alt="Quản lý thẻ thư viện" width="100%"/></p>

**Hồ sơ làm thẻ (duyệt)** — `/admin/library-cards/requests`

<p align="center"><img src="readme/assets/screenshots/23-admin-library-card-requests.png" alt="Duyệt làm thẻ" width="100%"/></p>

**Sách in (danh mục)** — `/admin/books/printed`

<p align="center"><img src="readme/assets/screenshots/25-admin-printed-books.png" alt="Sách in" width="100%"/></p>

**Dashboard tổng quan** — `/admin`

<p align="center"><img src="readme/assets/screenshots/06-admin-dashboard.png" alt="Dashboard admin" width="100%"/></p>

---

<details>
<summary><strong>Xem thêm — Auth & trang giới thiệu</strong></summary>

<br>

**Trang chủ** — `/`

<p align="center"><a href="http://3.0.56.220/"><img src="readme/assets/screenshots/01-home.png" alt="Trang chủ" width="100%"/></a></p>

**Đăng nhập** — `/login`

<p align="center"><img src="readme/assets/screenshots/02-login.png" alt="Đăng nhập" width="100%"/></p>

**Đăng ký** — `/register`

<p align="center"><img src="readme/assets/screenshots/03-register.png" alt="Đăng ký" width="100%"/></p>

</details>

> Demo: [Tài khoản demo](#tài-khoản-demo) (`student@…` cho luồng mượn/TT, `admin@…` cho admin). Sau deploy: **Ctrl+F5**.

### Luồng tài liệu số (admin)

```mermaid
sequenceDiagram
    participant A as Admin (form)
    participant API as POST/PUT /books/.../digital
    participant S as BookService
    participant DB as MySQL

    A->>API: FormData (metadata + PDF + ảnh)
    API->>S: createDigitalWithAssets / updateDigitalWithAssets
    S->>DB: BEGIN TRANSACTION
    S->>DB: books + digital_assets (+ cover)
    S->>DB: COMMIT
    API-->>A: 201/200 BookResource
```

### Luồng auth (API vs Admin SPA)

```mermaid
flowchart LR
    subgraph API["API client / Postman"]
        L[POST /auth/login] --> T[JWT token]
        T --> R[Gọi API + Bearer + domain]
    end
    subgraph Admin["Trang /admin"]
        W[Cookie session Sanctum] --> S[POST/PUT không Bearer]
    end
```

> **Lưu ý:** Trang `/admin` **không** dùng JWT trong `localStorage`. Mọi request axios tới `/admin` dùng cookie session (`skipBearerAuth`).

---

## Cài đặt local

### Yêu cầu
- PHP 8.2+, Composer 2
- Node.js 20+, npm
- MySQL 8, Redis (khuyến nghị)
- Extension: `pdo_mysql`, `mbstring`, `openssl`, `gd` hoặc `imagick` (preview PDF)

### Các bước

```bash
git clone https://github.com/TAAgnes3110/UTC-eLibrary.git
cd UTC-eLibrary
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Cấu hình `.env`: `DB_*`, `REDIS_*`, `APP_URL=http://localhost:8000`.

```bash
php artisan migrate --seed
npm run build
```

Chạy song song:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Mở: **http://localhost:8000**

| URL | Mô tả |
|-----|--------|
| `/` | Cổng độc giả |
| `/admin` | Quản trị |
| `/api/health` | Health check |
| `/api/v1/...` | REST API |

### Queue & preview PDF (tùy chọn local)

```bash
php artisan queue:work
# Hoặc đồng bộ preview khi dev:
# DIGITAL_PREVIEW_DISPATCH_SYNC=true
```

---

## Tài khoản demo

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Super Admin | `superadmin@utc.edu.vn` | `password` |
| Admin | `admin@utc.edu.vn` | `password` |
| Thủ thư | `librarian@utc.edu.vn` | `password` |
| Sinh viên | `student@st.utc.edu.vn` | `password` |

---

## Cấu trúc thư mục

```
UTC-eLibrary/
├── app/
│   ├── Http/Controllers/Api/    # REST API
│   ├── Services/                # Nghiệp vụ (Loan, Book, DigitalAsset…)
│   └── Models/
├── resources/js/                # Vue 3 + Inertia
├── routes/api.php               # /api/v1
├── database/migrations/
├── scripts/
│   ├── ec2-deploy.sh              # Deploy đầy đủ trên EC2
│   ├── ec2-prepare-build.sh       # Composer + Vite trước docker build
│   ├── ec2-apply-env.sh           # Áp dụng .env (recreate container)
│   ├── sync-env-to-ec2.sh         # Đẩy .env từ máy dev (Git Bash)
│   └── generate-postman-collection.php
├── deploy/
│   └── nginx-host-certbot.conf    # Nginx host → Docker :8080
├── readme/assets/               # SVG ERD, kiến trúc, screenshot README
│   ├── erd-database.png           # ERD MySQL tổng quan
│   └── screenshots/01-*.png …
├── .cursor/                     # Rules, agents, skills, commands (ECC + UTC)
├── docs/ai/                     # Ngữ cảnh nghiệp vụ & hướng dẫn ECC
├── AGENTS.md                    # Hướng dẫn agent (Cursor / Codex)
├── ecc-install.json             # Cấu hình cài ECC
├── UTC-eLibrary.postman_collection.json
├── docker-compose.ec2.yml
└── Dockerfile.ec2
```

**Không commit:** `.env`, `vendor/`, `node_modules/`, `public/build/`, `playwright-report/`, `test-results/`, `dist/`.

---

## API & Postman

- **Base:** `{{BASE_URL}}/api/v1`
- **Header bắt buộc (JWT):** `domain: {{DOMAIN}}` (thường trùng `APP_URL`)
- **Auth:** `Authorization: Bearer {{token}}` sau `POST /api/v1/auth/login`
- **Middleware `init`:** Hầu hết route sau login — ưu tiên session web nếu có cookie, không thì JWT

### File Postman

| File | Mô tả |
|------|--------|
| `UTC-eLibrary.postman_collection.json` | **197 request** / **21 folder**, sinh từ `php artisan route:list` |
| `scripts/generate-postman-collection.php` | Tái sinh collection khi thêm route |

**Cách dùng:**

1. Import collection vào Postman.
2. Biến `BASE_URL` = `http://localhost:8000`, `DOMAIN` giống `BASE_URL`.
3. Chạy **`POST api/v1/auth/login`** (folder `00 — Auth`) → token tự lưu vào `token`.
4. Gọi các folder còn lại (Me, Staff/Books, Loans, …).

**Tài liệu số (staff):**

| Method | Path | Ghi chú |
|--------|------|---------|
| POST | `/books/digital` | Tạo sách + PDF (multipart) |
| POST | `/books/{book}/digital` | Cập nhật + PDF mới (multipart, khuyến nghị) |
| PUT | `/books/{book}/digital` | Tương đương POST |
| POST | `/books/{book}/digital-assets` | Upload PDF phiên bản mới |

### Health

```http
GET /api/health
```

Trả `200` khi DB + cache OK.

---

## ERD cơ sở dữ liệu

Sơ đồ **tổng quan MySQL** (một ảnh duy nhất — khớp schema hiện tại). Chi tiết cột: `database/migrations/`.

<p align="center">
  <img src="readme/assets/erd-database.png" alt="ERD cơ sở dữ liệu UTC eLibrary" width="100%"/>
</p>

**Các nhóm chính trên sơ đồ:**

| Nhóm | Bảng tiêu biểu |
|------|----------------|
| **Người dùng & tổ chức** | `faculties`, `departments`, `users`, `library_cards` |
| **Danh mục & kho** | `classifications`, `books`, `book_copies`, `digital_assets`, `digital_asset_paywall_settings` |
| **Mượn — trả** | `loan_borrow_requests`, `loan_borrow_request_items`, `loans`, `loan_items` |
| **Giỏ & thanh toán** | `carts`, `cart_items`, `orders`, `order_items`, `payment_transactions` |

**Bảng phụ trợ** (không vẽ đầy đủ trên ERD): RBAC Spatie (`roles`, `permissions`, …), `email_otp`, `news_posts`, `library_settings`, `jobs`, `cache`, …

---

## Nghiệp vụ UTC (tóm tắt)

### Mượn về nhà
- Chỉ **sinh viên / giảng viên / cán bộ có thẻ UTC hợp lệ** được checkout.
- **Khách / người ngoài:** chỉ đọc tại chỗ — **không** mượn về nhà.

### Trước khi cho mượn (`LoanService`)
1. Thẻ còn hạn, trạng thái được phép.
2. Chưa vượt `loan_policies.max_books`.
3. Không có mượn quá hạn chưa xử lý.
4. Không nợ phạt (nếu có).
5. `book_copies` khả dụng.

### Tài liệu số
- `resource_type = digital`, `access_mode = online_only`.
- PDF lưu disk **private**; preview N trang đầu (job queue).
- Admin tạo/sửa: **một transaction** (`POST/POST /books/.../digital`) — không để bản ghi “shell” không file.

### Mã sách
- Sách in: theo kho / ĐKCB.
- Tài liệu số: `TLS000001`, …

---

## AI / ECC (Cursor)

Dự án dùng [Everything Claude Code (ECC)](https://github.com/affaan-m/ECC) cho rules, agents, skills và commands trong Cursor.

| Tài liệu | Mô tả |
|----------|--------|
| `AGENTS.md` | Hướng dẫn agent tổng quan |
| `docs/ai/context-utc-library.md` | Nghiệp vụ UTC (mượn/trả, thẻ, tài liệu số) |
| `docs/ai/ecc/README.md` | Cách cập nhật ECC từ upstream |
| `.cursor/rules/utc-elibrary-core.mdc` | Rule cốt lõi (luôn bật) |

Cập nhật ECC sau khi clone upstream vào `.tmp/ecc-upstream`:

```bash
node .tmp/ecc-upstream/scripts/install-apply.js --config ecc-install.json
```

Hooks ECC (nếu bật): có thể giảm mức bằng biến môi trường `ECC_HOOK_PROFILE=minimal`.

---

## Deploy EC2 (Docker)

Kiến trúc production:

```text
Internet → Nginx (host :80/:443) → Docker app (:8080 → :80) → MySQL / Redis
```

| Thành phần | Vai trò |
|------------|---------|
| **Nginx (host)** | Nhận HTTP/HTTPS từ internet, proxy `127.0.0.1:8080` |
| **Docker `app`** | Laravel + Vue build (`APP_PORT=8080`) |
| **`scheduler` / `queue`** | Job nền (thông báo, preview PDF, SePay…) |

### Cheat sheet — chạy gì, ở đâu?

| Mục tiêu | Ở đâu | Lệnh |
|----------|--------|------|
| Deploy code mới | **EC2** | `cd ~/utc-elibrary && git pull origin main && bash scripts/ec2-deploy.sh` |
| Chỉ áp dụng `.env` | **EC2** | `cd ~/utc-elibrary && bash scripts/ec2-apply-env.sh` |
| Đẩy `.env` từ máy | **Windows Git Bash** | `bash scripts/sync-env-to-ec2.sh` (xem bên dưới) |
| Xem `.env` server | **EC2** | `grep -E '^(APP_URL|APP_PORT)=' ~/utc-elibrary/.env` |
| Tắt job nền (không sửa code) | **EC2** | `docker compose -f docker-compose.ec2.yml stop scheduler queue` |

> Trên EC2 (`ubuntu@ip-...`) **không** chạy `ssh -i /d/AWS/...` — bạn đã ở trên server rồi.

### Lần đầu — checklist AWS + Nginx

1. **Security Group** inbound: **80**, **443** (`0.0.0.0/0`), SSH **22** (IP bạn).
2. Trên EC2: `APP_PORT=8080` trong `.env`, deploy app (mục dưới).
3. Cài Nginx host:

```bash
cd ~/utc-elibrary
sudo apt install -y nginx
sudo cp deploy/nginx-host-certbot.conf /etc/nginx/sites-available/utc-elibrary
sudo ln -sf /etc/nginx/sites-available/utc-elibrary /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl enable nginx && sudo systemctl restart nginx
```

4. Kiểm tra: `curl -sI http://127.0.0.1 | head -3` → `HTTP/1.1 200`.
5. HTTPS (tùy chọn): `sudo certbot --nginx -d kiet.mmoall.com` → `SESSION_SECURE_COOKIE=true` → `bash scripts/ec2-apply-env.sh`.

### Deploy code trên EC2

```bash
cd ~/utc-elibrary
git pull origin main
bash scripts/ec2-deploy.sh
```

Luồng script: `git pull` → `ec2-prepare-build.sh` (Composer + `npm run build`) → `docker compose build` → `up -d` → migrate → clear cache.

### Đồng bộ `.env` từ Windows (Git Bash)

`.env` **không** commit Git.

```bash
cd /d/UTC-eLibrary
git pull origin main
chmod 400 /d/AWS/utc-elibrary.pem

export EC2_HOST=3.0.56.220
export EC2_USER=ubuntu
export EC2_SSH_KEY=/d/AWS/utc-elibrary.pem
export EC2_APP_PATH=/home/ubuntu/utc-elibrary

ssh -i "$EC2_SSH_KEY" "$EC2_USER@$EC2_HOST" "echo OK"
bash scripts/sync-env-to-ec2.sh
```

Deploy code sau khi đổi env (tùy chọn):

```bash
ssh -i "$EC2_SSH_KEY" "$EC2_USER@$EC2_HOST" "cd /home/ubuntu/utc-elibrary && git pull origin main && bash scripts/ec2-deploy.sh"
```

### Vận hành nền (scheduler + queue)

| Container | Mục đích |
|-----------|----------|
| `scheduler` | `schedule:work` — nhắc hạn mượn, đồng bộ quá hạn, hết hạn đơn SePay |
| `queue` | `queue:work` — preview PDF khi `DIGITAL_PREVIEW_DISPATCH_SYNC=false` |

Sau khi sửa `.env` liên quan lịch/thông báo:

```bash
docker compose -f docker-compose.ec2.yml exec app php artisan config:clear
docker compose -f docker-compose.ec2.yml up -d scheduler queue
```

**Tắt job nền** (web vẫn chạy, không đổi code/DB):

```bash
docker compose -f docker-compose.ec2.yml stop scheduler queue
docker compose -f docker-compose.ec2.yml rm -f scheduler queue
```

Bật lại: `docker compose -f docker-compose.ec2.yml up -d scheduler queue`

### DB import từ backup SQL

```bash
docker compose -f docker-compose.ec2.yml exec app php artisan migrate:existing-schema --force
```

### Sau mỗi deploy

- **Ctrl+F5** trình duyệt (asset Vite mới).
- Chỉ `git pull` **không đủ** — cần `ec2-deploy.sh` (build image).

---

## CI/CD

1. GitHub **Secrets:** `EC2_HOST`, `EC2_USER`, `EC2_SSH_KEY` (tùy chọn `EC2_APP_PATH`).
2. Push `main` → workflow **Deploy EC2** chạy `scripts/ec2-deploy.sh`.

File: `.github/workflows/deploy-ec2.yml`

---

## Biến môi trường

### Local (`.env.example`)

| Biến | Mô tả |
|------|--------|
| `APP_URL` | URL gốc |
| `DB_*` | MySQL |
| `REDIS_*` | Cache / queue |
| `SANCTUM_STATEFUL_DOMAINS` | Host SPA (session) |
| `API_ALLOWED_DOMAINS` | Domain cho JWT |
| `API_HIDE_BROWSER_ACCESS` | `true` production — chặn mở `/api/*` trên trình duyệt |
| `API_MINIMAL_HEALTH` | `true` production — health không lộ chi tiết DB |
| `SECURITY_HEADERS` | Bật CSP, X-Frame-Options, … |
| `DIGITAL_ASSETS_DISK` | `local` hoặc `s3` / R2 |
| `DIGITAL_PREVIEW_DISPATCH_SYNC` | `true` khi dev không chạy queue |
| `NOTIFICATION_UI_POLL_INTERVAL_MS` | Poll UI thông báo (ms), mặc định `30000` |
| `LOAN_DUE_SOON_DAYS_BEFORE` | Báo trước N ngày (mặc định `2`) |
| `SCHEDULE_LOANS_*_AT` | Giờ chạy `loans:sync-overdue` / `loans:notify-due-soon` |

### EC2 production (ví dụ)

```env
DEPLOY_PROFILE=vps
APP_URL=https://kiet.mmoall.com
APP_PORT=8080
BASE_URL=https://kiet.mmoall.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=kiet.mmoall.com,3.0.56.220,localhost,127.0.0.1
API_ALLOWED_DOMAINS=https://kiet.mmoall.com,http://kiet.mmoall.com,3.0.56.220
API_HIDE_BROWSER_ACCESS=true
API_MINIMAL_HEALTH=true
SECURITY_HEADERS=true
DB_HOST=mysql
REDIS_HOST=redis
QUEUE_CONNECTION=redis
```

| Biến | Ghi chú |
|------|---------|
| `APP_PORT=8080` | Docker map `8080:80`; Nginx host proxy vào đây |
| `SESSION_SECURE_COOKIE` | `false` nếu chỉ HTTP; `true` sau HTTPS (Certbot / Cloudflare Full) |
| `API_HIDE_BROWSER_ACCESS=true` | Gõ `/api/v1/...` trên thanh địa chỉ → 404 (SPA vẫn gọi bình thường) |

**Admin:** `/admin` là SPA + cookie session — **không** ẩn URL; bảo vệ bằng đăng nhập + RBAC.

---

## Kiểm tra chất lượng

```bash
npm run build
php artisan route:list
php artisan test
vendor/bin/pint
```

Tái sinh Postman sau khi đổi route:

```bash
php scripts/generate-postman-collection.php
```

---

## Ghi chú bảo mật

- Không commit `.env`, `.pem`, key, credentials (`*.pem` đã nằm trong `.gitignore`).
- Không log PII / mật khẩu.
- PDF tài liệu số: không lộ URL public khi disk `local`.
- **Production:** `APP_DEBUG=false`, `API_HIDE_BROWSER_ACCESS=true`, `API_ALLOWED_DOMAINS` khớp domain thật.
- **HTTPS:** `SESSION_SECURE_COOKIE=true` khi user truy cập qua `https://`.
- Header: CSP, `X-Frame-Options`, CSRF (web + Sanctum SPA), rate limit (`auth` / `api` / `refresh`).
- Tin tức HTML: lọc XSS server (`SafeHtml`) + client (`DOMPurify`).

| Đường dẫn | “Ẩn” URL? | Bảo vệ |
|-----------|-----------|--------|
| `/api/v1/*` | Một phần (404 khi mở tay trên browser) | JWT + `domain` header + RBAC |
| `/admin/*` | Không (SPA cần route) | Session + role admin/thủ thư |

---

## Xử lý sự cố deploy

| Triệu chứng | Nguyên nhân thường gặp | Cách xử lý |
|-------------|------------------------|------------|
| `3.0.56.220` **connection refused** | Security Group chưa mở **80/443** | AWS → Inbound rules |
| Cloudflare **521** + `http://` OK | Origin chưa có **443** hoặc SSL mode sai | Certbot trên EC2 **hoặc** Cloudflare **Flexible** |
| `https://domain` 521, `http://domain` OK | Cloudflare gọi HTTPS origin, EC2 chỉ HTTP | SSL → **Flexible** (tạm) hoặc cài cert |
| `curl 127.0.0.1` OK, IP ngoài refused | Firewall / SG | Mở 80, 443; `sudo ufw allow 80/tcp` |
| `sync-env` lỗi key trên Git Bash | Quyền `.pem` / path MSYS | `chmod 400 /d/AWS/utc-elibrary.pem`; dùng script mới nhất |
| `routes-v7.php` sau apply env | Cache route cũ | `bash scripts/ec2-apply-env.sh` (đã xử lý trong script) |

**Chẩn đoán nhanh trên EC2:**

```bash
docker compose -f docker-compose.ec2.yml ps
curl -sI http://127.0.0.1:8080 | head -2
curl -sI http://127.0.0.1 | head -2
sudo systemctl is-active nginx
```

---

<p align="center">
  <strong>Đại học Giao thông Vận tải (UTC)</strong><br>
  <sub>Laravel 12 · Vue 3 · Inertia · Docker EC2 · <a href="AGENTS.md">AGENTS.md</a></sub>
</p>
