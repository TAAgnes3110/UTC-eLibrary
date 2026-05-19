# UTC eLibrary

<p align="center">
  <strong>Hệ thống quản lý thư viện số — Đại học Giao thông Vận tải (UTC)</strong><br>
  Laravel 12 · Vue 3 (Inertia) · MySQL · Redis
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

### Giao diện (screenshot)

<table>
  <tr>
    <td align="center"><strong>Đăng nhập</strong><br><img src="readme/assets/screenshots/login.png" width="280" alt="Đăng nhập"/></td>
    <td align="center"><strong>Đăng ký</strong><br><img src="readme/assets/screenshots/register.png" width="280" alt="Đăng ký"/></td>
    <td align="center"><strong>Xác minh OTP</strong><br><img src="readme/assets/screenshots/verify-otp-register.png" width="280" alt="OTP"/></td>
  </tr>
  <tr>
    <td align="center"><strong>Quên mật khẩu</strong><br><img src="readme/assets/screenshots/forgot-password.png" width="280" alt="Quên MK"/></td>
    <td align="center"><strong>Email OTP</strong><br><img src="readme/assets/screenshots/otp-email.png" width="280" alt="Email OTP"/></td>
    <td align="center"><strong>Đặt lại mật khẩu</strong><br><img src="readme/assets/screenshots/set-password.png" width="280" alt="Đặt MK"/></td>
  </tr>
  <tr>
    <td align="center"><strong>Dashboard admin</strong><br><img src="readme/assets/screenshots/dashboard.png" width="280" alt="Dashboard"/></td>
    <td align="center"><strong>Danh mục sách</strong><br><img src="readme/assets/screenshots/books-index.png" width="280" alt="Sách"/></td>
    <td align="center"><strong>Bạn đọc</strong><br><img src="readme/assets/screenshots/readers.png" width="280" alt="Bạn đọc"/></td>
  </tr>
  <tr>
    <td align="center"><strong>Tác giả</strong><br><img src="readme/assets/screenshots/authors.png" width="280" alt="Tác giả"/></td>
    <td align="center"><strong>Nhà xuất bản</strong><br><img src="readme/assets/screenshots/publishers.png" width="280" alt="NXB"/></td>
    <td align="center"><strong>Phân loại</strong><br><img src="readme/assets/screenshots/categories.png" width="280" alt="Phân loại"/></td>
  </tr>
  <tr>
    <td align="center"><strong>Tài khoản</strong><br><img src="readme/assets/screenshots/accounts.png" width="280" alt="Tài khoản"/></td>
    <td align="center"><strong>Ngôn ngữ</strong><br><img src="readme/assets/screenshots/languages.png" width="280" alt="Ngôn ngữ"/></td>
    <td align="center"><strong>Hóa đơn / thanh toán</strong><br><img src="readme/assets/screenshots/bills.png" width="280" alt="Hóa đơn"/></td>
  </tr>
</table>

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
├── readme/assets/               # SVG minh họa README
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

Sơ đồ tổng hợp các bảng nghiệp vụ chính (MySQL). Chi tiết cột xem `database/migrations/`.

```mermaid
erDiagram
    users ||--o{ library_cards : "sở hữu"
    users ||--o{ loans : "qua thẻ"
    users ||--o{ carts : "giỏ"
    users ||--o{ orders : "đơn"
    users ||--o{ digital_document_submissions : "nộp"
    users ||--o{ notifications : "nhận"

    faculties ||--o{ departments : "khoa"
    departments ||--o{ users : "đơn vị"
    faculties ||--o{ library_cards : "SV"

  periods ||--o{ library_cards : "đợt cấp thẻ"

    library_cards ||--o{ loans : "mượn"
    loan_policies ||--o{ library_cards : "policy holder"

    loans ||--|{ loan_items : "chi tiết"
    loan_items }o--|| book_copies : "bản sao"
    book_copies }o--|| books : "đầu mục"
    books }o--o| classifications : "phân loại"
    books }o--o| warehouses : "kho"
    books ||--o{ digital_assets : "PDF"
    books ||--o| thesis_metadata : "luận văn"
    books }o--o{ authors : "book_authors"
    books }o--o{ publishers : "book_publishers"

    digital_assets ||--o| digital_asset_paywall_settings : "giá tải"
    digital_assets ||--o{ cart_items : "giỏ mua"
    digital_assets ||--o{ order_items : "đơn"

    carts ||--|{ cart_items : "items"
    orders ||--|{ order_items : "items"
    orders ||--o{ payment_transactions : "SePay"

    digital_document_submissions }o--o| users : "submitter"
    digital_document_submissions }o--o| books : "sau duyệt"

    loan_borrow_requests }o--|| users : "độc giả"
    loan_renewal_requests }o--|| loans : "gia hạn"

    warehouses ||--o{ storage_cabinets : "tủ"
    storage_cabinets ||--o{ storage_slots : "ngăn"

    users {
        bigint id PK
        string email
        string user_type
        int faculty_id FK
        int department_id FK
    }

    library_cards {
        bigint id PK
        int user_id FK
        string card_number
        enum holder_type
        string workflow_status
        date expiry_date
    }

    books {
        bigint id PK
        string title
        string resource_type
        string access_mode
        int classification_id FK
        int warehouse_id FK
        int quantity
    }

    book_copies {
        bigint id PK
        bigint book_id FK
        string barcode
        tinyint status
    }

    loans {
        bigint id PK
        bigint library_card_id FK
        enum loan_type
        date due_date
        enum status
    }

    loan_items {
        bigint id PK
        bigint loan_id FK
        bigint book_copy_id FK
    }

    digital_assets {
        bigint id PK
        bigint book_id FK
        int version
        bool is_primary
        string path
        string visibility
        string preview_status
    }

    digital_document_submissions {
        bigint id PK
        int user_id FK
        string status
        string storage_path
    }

    orders {
        bigint id PK
        uuid public_id
        int user_id FK
        string status
        bigint total_vnd_snapshot
    }

    loan_policies {
        bigint id PK
        string holder_type
        int max_books
        int loan_days
    }
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
