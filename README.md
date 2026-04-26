# UTC-eLibrary

He thong quan ly thu vien so cho Dai hoc Giao thong Van tai (UTC), su dung Laravel 12 + Vue 3 + Inertia.

## Stack

- Backend: Laravel 12, PHP 8.2+, JWT + session auth
- Frontend: Vue 3, Inertia, Tailwind CSS
- Database: MySQL/SQLite
- Cache/Queue: Redis (khuyen nghi)

## Tinh nang chinh

- **Reader**
  - Trang cong khai: home, gioi thieu, quy dinh, tra cuu sach
  - Dich vu: the thu vien, sach da luu, quan ly phieu muon cua toi
  - Tai khoan: cap nhat thong tin, doi mat khau, lich su yeu cau cap nhat
- **Admin/Librarian**
  - Dashboard + thong ke
  - Quan ly users, books, warehouses, library cards, loans
  - Duyet yeu cau cap nhat ho so
  - Import/export excel cho cac module quan tri

## Cau truc thu muc (rut gon)

```text
app/
  Http/Controllers/
    Api/
    Frontend/
  Http/Requests/
  Http/Resources/
  Services/
resources/js/
  Pages/Admin/
  Pages/Reader/
  Layouts/
  Components/
routes/
  web.php
  api.php
database/
  migrations/
  seeders/
```

## Cai dat nhanh

```bash
git clone https://github.com/TAAgnes3110/UTC-eLibrary.git
cd UTC-eLibrary
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Chay local

Mo 2 terminal:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Truy cap: `http://localhost:8000`

## Tai khoan seed mac dinh

| Vai tro | Email | Mat khau |
|---|---|---|
| Super Admin | `superadmin@utc.edu.vn` | `password` |
| Admin | `admin@utc.edu.vn` | `password` |
| Librarian | `librarian@utc.edu.vn` | `password` |
| Student | `student@st.utc.edu.vn` | `password` |
| Teacher | `teacher@st.utc.edu.vn` | `password` |
| Member | `member@st.utc.edu.vn` | `password` |

## API luu y

- Prefix API: `/api/v1`
- Nhieu endpoint dung middleware `init` + role check
- Header domain can cau hinh theo `API_ALLOWED_DOMAINS` (xem `.env.example`)
- Route health check: `GET /api/health`

## Quy uoc du lieu sach

- He thong su dung 3 gia tri `resource_type`: `textbook`, `reference`, `digital`.
- Tren UI admin:
  - `Sach in` = nhom gom `textbook` + `reference`.
  - `Tai lieu so` = `digital`.
- Validation nam:
  - `published_year` phai trong khoang `1900..nam_hien_tai`.
  - Import Excel va API deu ap dung cung quy tac tren.

## Test / Quality check

```bash
# Build frontend
npm run build

# Kiem tra route nhanh
php artisan route:list
```

Neu muon chay test tu dong, can cai day du dev dependencies (composer install khong --no-dev) va cau hinh moi truong test phu hop.

## Ghi chu quan trong

- Khong commit file nhay cam nhu `.env`, key, credentials.
- `public/build` va file upload local da duoc ignore trong `.gitignore`.
- Neu thay doi migration/schema, uu tien giu create migration day du, tranh tao chuoi migration add/update/drop khong can thiet.

## Tai lieu them

- `docs/API.md`
- `ARCHITECTURE.md`
- `tests/README.md`

