# UTC eLibrary

<p align="center">
  <strong>Hệ thống quản lý thư viện số — Đại học Giao thông Vận tải (UTC)</strong><br>
  Laravel 12 · Vue 3 (Inertia) · MySQL · Redis
</p>

<p align="center">
  <a href="http://3.0.56.220/"><strong>🌐 Demo trực tuyến</strong></a> ·
  <code>http://3.0.56.220/</code> (độc giả) ·
  <a href="http://3.0.56.220/admin"><code>/admin</code></a> (quản trị)
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
10. [Deploy EC2 (Docker)](#deploy-ec2-docker)
11. [CI/CD](#cicd)
12. [Biến môi trường](#biến-môi-trường)
13. [Kiểm tra chất lượng](#kiểm-tra-chất-lượng)
14. [Ghi chú bảo mật](#ghi-chú-bảo-mật)

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

Ảnh **full-page** (1440px, scale 2×) — mỗi trang **một hàng**, `width="100%"`. Tái chụp toàn bộ: `node scripts/capture-readme-screenshots.mjs`.

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

**Giỏ sách (mượn in)** — `/dich-vu/gio-sach`

<p align="center"><img src="readme/assets/screenshots/12-reader-book-cart.png" alt="Giỏ mượn sách in" width="100%"/></p>

**Giỏ mua tài liệu số** — `/dich-vu/gio-sach?tab=purchase`

<p align="center"><img src="readme/assets/screenshots/13-reader-digital-cart.png" alt="Giỏ mua PDF" width="100%"/></p>

**Thanh toán SePay** — `/dich-vu/thanh-toan`

<p align="center"><img src="readme/assets/screenshots/14-reader-payment.png" alt="Thanh toán tài liệu số" width="100%"/></p>

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
│   ├── ec2-deploy.sh
│   ├── ec2-prepare-build.sh
│   └── generate-postman-collection.php
├── readme/assets/               # SVG ERD, kiến trúc, screenshot README
│   ├── erd-*.svg
│   └── screenshots/01-*.png …
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
| `UTC-eLibrary.postman_collection.json` | **195+ request**, sinh từ `php artisan route:list` |
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

Sơ đồ chia theo **miền nghiệp vụ** (MySQL) — dễ đọc hơn một `erDiagram` khổng lồ. Chi tiết cột: `database/migrations/`.

| Miền | Mô tả ngắn |
|------|------------|
| **Người dùng & thẻ** | Khoa/bộ môn, tài khoản, đợt cấp thẻ, chính sách mượn |
| **Danh mục & kho** | Đầu mục sách, bản sao, phân loại, tủ/kệ, tác giả/NXB |
| **Mượn — trả** | Phiếu mượn, dòng chi tiết, yêu cầu mượn/gia hạn |
| **Tài liệu số & TT** | PDF, paywall, nộp duyệt, giỏ/đơn, SePay |

### 1. Người dùng & thẻ thư viện

<p align="center">
  <img src="readme/assets/erd-identity.svg" alt="ERD người dùng và thẻ" width="920"/>
</p>

### 2. Danh mục & kho vật lý

<p align="center">
  <img src="readme/assets/erd-catalog.svg" alt="ERD danh mục sách" width="920"/>
</p>

### 3. Mượn — trả sách in

<p align="center">
  <img src="readme/assets/erd-loans.svg" alt="ERD phiếu mượn" width="920"/>
</p>

### 4. Tài liệu số & thanh toán

<p align="center">
  <img src="readme/assets/erd-digital.svg" alt="ERD tài liệu số" width="920"/>
</p>

### Sơ đồ quan hệ tổng quan (rút gọn)

```mermaid
flowchart TB
    subgraph Identity["👤 Người dùng"]
        U[users] --> LC[library_cards]
        F[faculties] --> D[departments] --> U
    end
    subgraph Catalog["📚 Danh mục"]
        B[books] --> BC[book_copies]
        B --> DA[digital_assets]
    end
    subgraph Loans["📋 Mượn trả"]
        LC --> L[loans] --> LI[loan_items] --> BC
    end
    subgraph Digital["💳 Số & TT"]
        DA --> PW[paywall_settings]
        U --> C[carts] --> O[orders] --> PT[payment_transactions]
        U --> SUB[digital_document_submissions]
    end
    Identity --> Loans
    Catalog --> Loans
    Catalog --> Digital
```

### Nhóm bảng phụ trợ

| Nhóm | Bảng |
|------|------|
| **RBAC** | `roles`, `permissions`, `model_has_roles`, … (Spatie) |
| **Auth** | `email_otp`, `personal_access_tokens` |
| **Tin tức** | `news_posts`, `news_post_categories`, … |
| **Hệ thống** | `library_settings`, `site_contents`, `jobs`, `cache` |
| **Lưu** | `saved_books`, `user_profile_update_requests` |

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

## Deploy EC2 (Docker)

Trên server (ví dụ `~/utc-elibrary`):

```bash
cd ~/utc-elibrary
git pull origin main
bash scripts/ec2-deploy.sh
```

Script: pull → `ec2-prepare-build.sh` → `docker compose build app` → `up -d` → `migrate:existing-schema` → clear cache.

### Vận hành nền (Docker — cấu hình một lần trong `.env`)

`docker-compose.ec2.yml` đã chạy sẵn:

| Container | Lệnh | Mục đích |
|-----------|------|----------|
| `scheduler` | `php artisan schedule:work` | Nhắc sắp đến hạn, đồng bộ quá hạn, hết hạn đơn SePay, … |
| `queue` | `php artisan queue:work` | Preview PDF / job nền khi `DIGITAL_PREVIEW_DISPATCH_SYNC=false` |

**Không cần** thêm crontab `* * * * * php artisan schedule:run` trên EC2 Docker.

Sau khi sửa `.env` (giờ chạy, số ngày báo trước, chu kỳ poll UI):

```bash
docker compose -f docker-compose.ec2.yml exec app php artisan config:clear
docker compose -f docker-compose.ec2.yml up -d scheduler queue
```

Kiểm tra container: `docker compose -f docker-compose.ec2.yml ps` (phải thấy `scheduler`, `queue` **Up**).

**Bare-metal (không Docker):** crontab `* * * * * cd /path/to/UTC-eLibrary && php artisan schedule:run` và process supervisor cho `php artisan queue:work`.

### DB import từ backup SQL

Khi bảng đã có nhưng thiếu dòng trong `migrations`:

```bash
docker compose -f docker-compose.ec2.yml exec app php artisan migrate:existing-schema --force
```

### Sau deploy

- **Ctrl+F5** trình duyệt (JS mới trong image).
- Chỉ `git pull` **không đủ** — phải build lại image.

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
| `DIGITAL_ASSETS_DISK` | `local` hoặc `s3` / R2 |
| `DIGITAL_PREVIEW_DISPATCH_SYNC` | `true` khi dev không chạy queue |
| `NOTIFICATION_UI_POLL_INTERVAL_MS` | Poll UI thông báo (ms), mặc định `30000` |
| `LOAN_DUE_SOON_DAYS_BEFORE` | Báo trước N ngày (mặc định `2`) |
| `SCHEDULE_LOANS_*_AT` | Giờ chạy `loans:sync-overdue` / `loans:notify-due-soon` |

### EC2 (HTTP, ví dụ)

```env
DEPLOY_PROFILE=vps
APP_URL=http://<IP-EC2>
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=
SANCTUM_STATEFUL_DOMAINS=<IP-EC2>,localhost,127.0.0.1
API_ALLOWED_DOMAINS=http://<IP-EC2>,<IP-EC2>
DIGITAL_PREVIEW_DISPATCH_SYNC=true
QUEUE_CONNECTION=redis
NOTIFICATION_UI_POLL_INTERVAL_MS=30000
LOAN_DUE_SOON_DAYS_BEFORE=2
SCHEDULE_LOANS_SYNC_OVERDUE_AT=06:05
SCHEDULE_LOANS_NOTIFY_DUE_SOON_AT=07:00
```

**Admin / tài liệu số:** `/admin` dùng cookie session. Lỗi 401 khi Lưu → F5, đăng nhập lại; kiểm tra `SESSION_SECURE_COOKIE=false` trên HTTP.

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

- Không commit `.env`, key, credentials.
- Không log PII / mật khẩu.
- PDF tài liệu số: không lộ URL public khi disk `local`.
- `resource_type`: `textbook` | `reference` | `digital`.

---

<p align="center">
  <sub>Đại học Giao thông Vận tải (UTC) · MIT-compatible OSS components</sub>
</p>
