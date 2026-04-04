# 📚 UTC-eLibrary

> Hệ thống quản lý thư viện trường **Đại học Giao thông Vận tải (UTC)** — Đồ án Quản lý thư viện.

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vue.js)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-38B2AC?logo=tailwind-css)](https://tailwindcss.com)

---

## 📋 Mục lục

- [Giới thiệu](#-giới-thiệu)
- [Ảnh màn hình](#-ảnh-màn-hình)
- [Chức năng hệ thống](#-chức-năng-hệ-thống)
- [Công nghệ sử dụng](#-công-nghệ-sử-dụng)
- [Cấu trúc dự án](#-cấu-trúc-dự-án)
- [Import/Export Excel & File mẫu](#-importexport-excel--file-mẫu)
- [Thùng rác (xóa mềm)](#-thùng-rác-xóa-mềm)
- [Cài đặt & Chạy](#-cài-đặt--chạy)
- [Test](#-test)
- [Tác giả](#-tác-giả)

---

## ℹ️ Giới thiệu

Dự án **UTC-eLibrary** là Đồ án Quản lý thư viện, xây dựng nhằm quản lý sách, độc giả và quy trình mượn–trả sách một cách hiệu quả và hiện đại cho **Thư viện Trường Đại học Giao thông Vận tải (UTC)**.  
Hiện tại repository đang tập trung refactor lại **backend (database + models)** cho tài nguyên thư viện (sách, tác giả, nhà xuất bản, phân loại, kho, quy định mượn trả). Phần giao diện mượn–trả và các nghiệp vụ nâng cao sẽ được xây dựng dần dựa trên nền tảng này.

---

## 📚 Thư viện UTC (mô hình trong hệ thống)

### 2. Tài nguyên thư viện

Trong schema hiện tại, tài nguyên được mô hình hóa chủ yếu qua các bảng:

- `books`: đầu sách (tên, phụ đề, năm XB, số trang, khổ sách, giá bìa, mã sách, số ĐKCB, tủ/ngăn lưu trữ…).
- `authors`, `book_authors`: tác giả và quan hệ nhiều–nhiều giữa sách – tác giả.
- `publishers`, `book_publishers`: nhà xuất bản và quan hệ nhiều–nhiều.
- `classifications`, `classification_details`: phân loại sách và phân loại chi tiết (phục vụ kiểm kê, báo cáo, in phích).
- `warehouses`: kho sách và phân nhóm kho.

Tài liệu in (giáo trình, tham khảo, báo – tạp chí, luận văn…) và tài liệu số đều có thể được lưu trong `books`, các thuộc tính bổ sung (ví dụ đường dẫn file số, định dạng…) sẽ được mở rộng dần thông qua cột `params` (JSON) và các bảng nghiệp vụ phía sau.

### 3. Đối tượng phục vụ

Sinh viên đại học, Học viên cao học, Nghiên cứu sinh, Giảng viên, Cán bộ nhà trường. Trong hệ thống: `users.cohort` lưu khóa (K60–K66); vai trò qua `user_type` (MEMBER, LIBRARIAN, ADMIN…).

### 4. Quy định mượn – trả (mô hình chung)

Quy định mượn–trả được lưu ở mức cấu hình qua bảng `loan_policies`:

- `max_books`: số sách tối đa mỗi bạn đọc được mượn.
- `max_days`: số ngày mượn tối đa.
- `max_renewals`: số lần gia hạn cho phép.
- `overdue_fine_per_day`: tiền phạt mỗi ngày quá hạn.
- `user_type`: gắn với loại người dùng (sinh viên, giảng viên, cán bộ…).  

Các chính sách này được áp dụng cho từng phiếu mượn thông qua bảng `loans` và có thể tuỳ biến theo yêu cầu thực tế của thư viện UTC.

### 5. Dịch vụ thư viện

Mượn – trả sách, Gia hạn mượn, Đặt trước tài liệu, Tra cứu tài liệu, Hướng dẫn sử dụng thư viện, Sao chụp / scan tài liệu (theo quy định). Hệ thống hỗ trợ nghiệp vụ mượn/trả, gia hạn, đặt trước qua API và giao diện quản lý.

### 6. Thời gian hoạt động (tham khảo)

Thứ 2 – Thứ 6: giờ hành chính. Giai đoạn thi, làm đồ án: có thể mở thêm ca. Giờ cụ thể lưu trong cấu hình (`library_opening_time`, `library_closing_time`, `library_hours_notes`).

## 📥 Import/Export Excel & File mẫu

Project dùng `PhpOffice/PhpSpreadsheet` (thông qua `app/Helpers/FileHelpers.php`) để đọc/ghi Excel và tạo workbook nhiều sheet.

### Sách (`/api/v1/books`)

- **Export**: `GET /books/export` (query `?ids[]=1&ids[]=2...` tùy chọn)  
  - Xuất workbook 4 sheet: `Sheet1_Sach` (dữ liệu đầy đủ), `Sheet2_PhanLoaiSach`, `Sheet3_PhanLoaiSachChiTiet`, `Sheet4_KhoSach`
- **File mẫu nhập**: `GET /books/import-template`  
  - Workbook 4 sheet, `Sheet1_Sach` có dropdown chọn **phân loại**, **phân loại chi tiết**, **kho sách**  
  - Cột có `(*)` là **bắt buộc**
- **Import**: `POST /books/import` (multipart `file`, mimes: xlsx/xls/csv)  
  - Bắt buộc: **Tên sách (*)**, **Kho sách (*)**, **Số lượng (*) > 0**  
  - Tác giả/NXB hỗ trợ nhiều giá trị, ngăn cách bằng `,` hoặc `;`  
  - Nếu để trống **Số ĐKCB** / **Mã sách** sẽ tự sinh theo quy tắc trong hệ thống

### Kho sách (`/api/v1/warehouses`)

- **Export**: `GET /warehouses/export` (query `?ids[]=` tùy chọn)
- **File mẫu nhập**: `GET /warehouses/import-template`
- **Import**: `POST /warehouses/import` (multipart `file`)

### Người dùng (`/api/v1/users`)

- **Export**: `GET /users/export` (query `?ids[]=` tùy chọn)  
  - Xuất đầy đủ thông tin user + khoa/bộ môn + thẻ thư viện (nếu có) + audit fields

### Phân loại & Phân loại chi tiết

- **File mẫu**:
  - `GET /classifications/import-template`
  - `GET /classification-details/import-template`

> Ghi chú: API hiện hành nằm trong `routes/api.php` (prefix `/api/v1`) và được bảo vệ bởi middleware `init` + `role_or_permission`.

## 🗑️ Thùng rác (xóa mềm)

Các màn quản trị có thùng rác hỗ trợ:

- **Chọn nhiều / chọn tất cả**
- **Xóa vĩnh viễn**: từng mục, đã chọn, hoặc tất cả (đang hiển thị)
- **Khôi phục**: từng mục, đã chọn, hoặc tất cả (đang hiển thị)

### API bulk (ví dụ)

- **Books**
  - `POST /books/restore` body `{ ids: [1,2,3] }`
  - `POST /books/force` body `{ ids: [1,2,3] }`
- **Warehouses**
  - `POST /warehouses/restore` body `{ ids: [...] }`
  - `POST /warehouses/force` body `{ ids: [...] }`
- **Users**
  - `POST /users/restore` body `{ ids: [...] }`
  - `POST /users/force` body `{ ids: [...] }`

### Tự động xóa sau 30 ngày

- Command: `php artisan trash:purge --days=30` (`app/Console/Commands/PurgeTrashedCommand.php`)
- Schedule: `routes/console.php` chạy hằng ngày 02:00

---

## 🖼️ Ảnh màn hình

Ảnh chụp màn hình đặt trong **`docs/screenshots/`** với đúng tên file (xem danh sách trong [`docs/screenshots/README.txt`](docs/screenshots/README.txt)). Nếu bạn có một thư mục ảnh theo thứ tự, đặt vào `docs/screenshots/incoming/` rồi chạy `copy-screenshots.bat` (Windows) hoặc `copy-screenshots.sh` (Bash) để tự động đặt tên.

### Đăng nhập, Đăng ký & Xác thực OTP

| Màn hình | Mô tả |
|----------|--------|
| Đăng nhập | Trang đăng nhập — tài khoản/mật khẩu, Ghi nhớ, Quên mật khẩu, Đăng nhập Microsoft 365 |
| Đăng ký | Form đăng ký — họ tên, email, CCCD/CMND, SĐT, giới tính, ngày sinh, địa chỉ, mật khẩu |
| Xác thực OTP (đăng ký) | Nhập mã 6 số gửi tới email sau khi đăng ký, có nút gửi lại và quay lại đăng ký |
| Email OTP | Mẫu email gửi mã xác thực OTP từ UTC-eLibrary (hiệu lực 5 phút) |
| Quên mật khẩu | Nhập email đăng ký để nhận mã OTP khôi phục tài khoản |
| Xác thực OTP (đặt lại MK) | Bước xác minh OTP trước khi thiết lập mật khẩu mới |
| Đặt mật khẩu | Bước 2: Thiết lập mật khẩu mới và xác nhận, nút Quay lại / Hoàn tất |

| |
|:--:|
| ![Đăng nhập](docs/screenshots/login.png) |
| *Đăng nhập — Cổng thông tin UTC eLibrary* |

| |
|:--:|
| ![Đăng ký](docs/screenshots/register.png) |
| *Đăng ký — Hệ thống thư viện điện tử UTC* |

| |
|:--:|
| ![Xác thực OTP đăng ký](docs/screenshots/verify-otp-register.png) |
| *Xác thực OTP sau đăng ký* |

| |
|:--:|
| ![Email OTP](docs/screenshots/otp-email.png) |
| *Email gửi mã OTP* |

| |
|:--:|
| ![Quên mật khẩu](docs/screenshots/forgot-password.png) |
| *Quên mật khẩu — Khôi phục tài khoản* |

| |
|:--:|
| ![Xác thực OTP đặt lại mật khẩu](docs/screenshots/verify-otp-reset.png) |
| *Xác thực OTP — Bước 1: Xác minh (đặt lại mật khẩu)* |

| |
|:--:|
| ![Đặt mật khẩu](docs/screenshots/set-password.png) |
| *Đặt mật khẩu — Bước 2: Bảo mật* |

**Tên file gợi ý:** `login.png`, `register.png`, `verify-otp-register.png`, `otp-email.png`, `forgot-password.png`, `verify-otp-reset.png`, `set-password.png`

---

### Trang chủ & Bảng điều khiển

| Màn hình | Mô tả |
|----------|--------|
| Dashboard | Tổng quan: chào mừng, phiếu mượn cần xử lý, thống kê sách/người mượn/sách quá hạn, biểu đồ mượn-trả, hoạt động gần đây |

| |
|:--:|
| ![Dashboard](docs/screenshots/dashboard.png) |
| *Bảng điều khiển — Tổng quan UTC eLibrary* |

### Quản lý người dùng

| Màn hình | Mô tả |
|----------|--------|
| Danh sách Bạn đọc | Tab Học sinh/Sinh viên: mã thẻ, ngày cấp/hết hạn, lớp/đơn vị, trạng thái; Thêm mới, Xuất/Nhập excel, Cập nhật ảnh thẻ |
| Danh sách Tài khoản | Danh sách tài khoản theo phân quyền (Admin, Thủ thư, Sinh viên...), trạng thái Hoạt động/Tạm khóa, Thêm mới, Xuất/Nhập excel |

| |
|:--:|
| ![Danh sách Bạn đọc](docs/screenshots/readers.png) |
| *Quản lý người dùng — Danh sách Học sinh / Sinh viên* |

| |
|:--:|
| ![Danh sách Tài khoản](docs/screenshots/accounts.png) |
| *Quản lý người dùng — Danh sách tài khoản* |

### Quản lý Danh mục (Thể loại, Ngôn ngữ)

| Màn hình | Mô tả |
|----------|--------|
| Quản lý Thể loại | Danh sách thể loại: ID, tên, mô tả chi tiết, số lượng sách; Tìm kiếm, Nhập/Xuất excel, Thêm thể loại |
| Quản lý Ngôn ngữ | Danh sách ngôn ngữ: ID, tên ngôn ngữ, mô tả, số lượng sách; Tìm kiếm, Nhập/Xuất excel, Thêm ngôn ngữ |

| |
|:--:|
| ![Quản lý Thể loại](docs/screenshots/categories.png) |
| *Quản lý Phân loại — Thể loại* |

| |
|:--:|
| ![Quản lý Ngôn ngữ](docs/screenshots/languages.png) |
| *Quản lý Phân loại — Ngôn ngữ* |

### Quản lý Tác giả

| Màn hình | Mô tả |
|----------|--------|
| Quản lý Tác giả | Danh sách tác giả: mã, họ tên, quốc tịch, ngày sinh, tiểu sử, số tác phẩm; Tìm kiếm, Nhập/Xuất excel, Thêm tác giả, Thùng rác |

| |
|:--:|
| ![Quản lý Tác giả](docs/screenshots/authors.png) |
| *Quản lý Tác giả — Dữ liệu thư viện* |

### Quản lý Nhà xuất bản

| Màn hình | Mô tả |
|----------|--------|
| Quản lý Nhà xuất bản | Danh sách NXB: ID, tên, địa chỉ trụ sở, SĐT, email, số sách; Tìm kiếm, Nhập/Xuất excel, Thêm Nhà xuất bản |

| |
|:--:|
| ![Quản lý Nhà xuất bản](docs/screenshots/publishers.png) |
| *Quản lý Nhà xuất bản — Dữ liệu thư viện* |

### Quản lý Sách & Tài liệu

| Màn hình | Mô tả |
|----------|--------|
| Danh sách sách / tài liệu | STT, mã sách, tên sách (tác giả), thông tin xuất bản, số lượng, trạng thái; Thêm mới, Xuất/Nhập excel, Cập nhật ảnh bìa, Thùng rác |
| Form sách | Thêm/sửa sách, tác giả, NXB, bản in (có thể bổ sung ảnh sau) |

| |
|:--:|
| ![Danh sách sách](docs/screenshots/books-index.png) |
| *Quản lý Sách & Tài liệu — Danh sách sách / tài liệu* |

### Quản lý phiếu

| Màn hình | Mô tả |
|----------|--------|
| Phiếu nhập | Tab Phiếu nhập: số phiếu, nguồn nhập, ngày lập, đầu sách, số lượng, giá trị, trạng thái; Tạo phiếu mới, Xuất excel |
| Phiếu xuất | Tab Phiếu xuất (phiếu xuất kho) |

| |
|:--:|
| ![Quản lý phiếu](docs/screenshots/bills.png) |
| *Quản lý phiếu — Phiếu nhập* |

### Mượn trả & Báo cáo

| Màn hình        | Mô tả                    |
|-----------------|---------------------------|
| Mượn / Trả      | Giao diện mượn sách, trả sách, gia hạn |
| Thống kê        | Báo cáo mượn trả, tổng quan kho       |

<!-- ![Mượn trả](docs/screenshots/loans.png) -->
<!-- ![Thống kê](docs/screenshots/stats.png) -->

> **Cách thêm ảnh:** Đặt file ảnh vào `docs/screenshots/` với đúng tên file (xem gợi ý trong từng mục hoặc `docs/screenshots/README.txt`).

---

## 🌟 Chức năng Hệ thống

### 📚 Quản lý Sách (Tài nguyên)

- **Đa dạng loại tài liệu:** Sách bản cứng và tài liệu số (bản mềm).
- **Nghiệp vụ:** Nhập sách, phân loại khoa học, in nhãn sách, in phích, in sổ quản lý, thanh lý sách cũ/hỏng.

### 👤 Quản lý Độc giả

- Quản lý thông tin độc giả.
- In thẻ thư viện.

### 🔄 Mượn – Trả

- Theo dõi mượn/trả tài liệu.
- Gia hạn, xử lý phạt quá hạn.

### 📊 Báo cáo & Kiểm kê

- Kiểm kê tài sản định kỳ.
- Báo cáo tổng quan sách, đầu sách.
- Thống kê mượn trả theo thời gian (ngày/tháng/năm), theo lớp, nhóm độc giả.

---

## 🛠️ Công nghệ sử dụng

| Thành phần | Công nghệ |
|------------|-----------|
| **Backend** | Laravel 12, PHP 8.x |
| **Frontend** | Vue 3, Inertia.js |
| **Styling** | Tailwind CSS |
| **Database** | MySQL / SQLite |
| **Cache** | Redis (tùy chọn) / Database |
| **Auth** | JWT (API), Session (Web), OAuth (Microsoft) |

### 📱 Responsive & thiết bị di động

Giao diện hỗ trợ **điện thoại, tablet và desktop**:

- **Viewport & theme:** Meta viewport, theme-color theo dark/light, hỗ trợ thêm vào màn hình chính (PWA-style).
- **Safe area:** Padding theo `env(safe-area-inset-*)` cho máy có notch / tai thỏ.
- **Touch:** Các liên kết chính (Tra cứu sách, Đăng nhập, …) có vùng chạm ~44px; nút submit đủ cao.
- **Layout:** Sidebar ẩn trên mobile (menu hamburger), bảng có `overflow-x-auto`, grid chuyển 1 cột trên màn hẹp.
- **Ảnh / media:** `max-width: 100%` trong nội dung để không tràn màn hình.

---

## 📁 Cấu trúc dự án

### Backend (Laravel) — `app/`

| Thư mục | Vai trò |
|--------|--------|
| **Helpers/** | Hàm tiện ích (Upload, String, Date, ApiResponse) |
| **Http/Controllers/Api/** | Chỉ điều hướng và gọi Service — không chứa logic nghiệp vụ |
| **Http/Middleware/** | Bộ lọc (CheckRole, ForceJson, Init) |
| **Http/Requests/** | Validation dữ liệu đầu vào |
| **Http/Resources/** | Định dạng JSON đầu ra |
| **Services/** | **Logic nghiệp vụ** (AuthService, UserService, BookService, ClassificationService, ClassificationDetailService, WarehouseService, OtpService, MasterDataService) |
| **Imports/** | Nhập Excel (BookImport, WarehouseImport, ...) |
| **Exports/** | Xuất Excel & file mẫu (BooksWorkbookExport, UserExport, WarehouseExport, BookImportTemplateExport, ...) |
| **Enums/** | Hằng số (RoleType, BookType) |
| **Models/** | Đại diện bảng DB |

Laravel mặc định: Mail/, Observers/, Providers/. Trang web Inertia: **Http/Controllers/Frontend/** (Admin, Reader, Auth) — chỉ render và gọi Api.

### Frontend (Vue 3 + Inertia) — `resources/js/`

| Thư mục | Vai trò |
|--------|--------|
| **api/** | Axios + gọi API theo module (users, books, auth, axios) |
| **components/** | UI dùng chung (Table, Modal, Button, Admin/*) |
| **composables/** | Logic tái sử dụng (useTable, các trang Admin/Auth) |
| **config/** | Nav, enums (adminNavigation, readerNavigation, enums) |
| **Layouts/** | Layout Inertia (Admin, Reader, Auth) |
| **Pages/** | Màn hình (Admin/*, Reader/*, Auth/*) |
| **store/** | Trạng thái (toast) |
| **utils/** | cn(), format, hằng số FE |

**API:** `routes/api.php` → prefix `/api/v1`, controller `Api\*`. **Web:** `routes/web.php` → Frontend controllers.

---

## 🚀 Cài đặt & Chạy

### Yêu cầu

- PHP 8.2+
- Composer, Node.js & npm
- MySQL hoặc SQLite

### Các bước

**1. Clone repository**

```bash
git clone https://github.com/TAAgnes3110/UTC-eLibrary.git
cd UTC-eLibrary
```

**2. Cài đặt dependencies**

```bash
composer install
npm install
```

**3. Cấu hình môi trường**

- Copy `.env.example` thành `.env`
- Cấu hình database và các biến môi trường (xem mục Redis/OAuth trong `.env.example` nếu cần)

**4. Chạy migration và seeder**

```bash
php artisan key:generate
php artisan migrate --seed
```

**Dữ liệu mẫu:** `BookSampleSeeder` tạo vài đầu sách **in** (kho `KHO-GT`), **tài liệu số** và **hybrid** (kho `KHO-SO` / kết hợp), một bản ghi **thesis_metadata** (đồ án mẫu). Chạy lại: `php artisan db:seed` (hoặc chỉ `php artisan db:seed --class=BookSampleSeeder`).

**Tài khoản mặc định (sau khi chạy seed):**

| Vai trò      | Email                 | Mật khẩu  |
|-------------|------------------------|-----------|
| Admin       | `admin@utc.edu.vn`     | `password` |
| Thủ thư     | `librarian@utc.edu.vn` | `password` |
| Người dùng  | `student@st.utc.edu.vn`| `password` |

Chỉ tạo tài khoản nếu chưa tồn tại (theo email). Nên đổi mật khẩu sau lần đăng nhập đầu.

**5. Chạy ứng dụng**

```bash
# Terminal 1: frontend
npm run dev

# Terminal 2: backend
php artisan serve
```

Truy cập: **http://localhost:8000**

---

## 🧪 Test

Chạy PHPUnit:

```bash
composer test
# hoặc: php artisan test
```

- **Yêu cầu:** PHP có extension `pdo_sqlite` (mặc định dùng SQLite in-memory)
- Nếu không có SQLite, tạo DB MySQL `elibrary_test` — bootstrap tự chuyển sang MySQL
- Chi tiết: xem [`tests/README.md`](tests/README.md)

## 📁 Tài liệu thêm
 
- **Postman collection:** [`UTC-eLibrary.postman_collection.json`](UTC-eLibrary.postman_collection.json) — import để test nhanh API v1 (tự set header `domain` và `Authorization: Bearer <token>` nếu đã khai báo `token`).

- **API:** [docs/API.md](docs/API.md) — Mô tả API v1, endpoint, rate limit.
- **Kiến trúc:** [ARCHITECTURE.md](ARCHITECTURE.md) — REST API, Auth, Cache, Health.
- **ERD (chốt thiết kế DB):** [docs/ERD.md](docs/ERD.md) — Quan hệ bảng hiện tại + mở rộng tài liệu số / luận–đồ án (Mermaid).
- **Tài liệu số (PDF):** `POST /api/v1/books/{book}/digital-assets` (multipart `file`, PDF tối đa 50MB; tùy chọn `is_primary`, `visibility`, `embargo_until`), `DELETE /api/v1/books/{book}/digital-assets/{digital_asset}` — cần quyền admin/thủ thư như các API sách khác.

### Dùng Postman collection

1. Import file `UTC-eLibrary.postman_collection.json` vào Postman.
2. Mở `Variables` trong collection và cấu hình:
   - `BASE_URL` (ví dụ: `http://localhost:8000`)
   - `DOMAIN` (collection sẽ tự đẩy vào header)
   - `token` (JWT) cho các request cần xác thực
   - `otp` cho luồng xác thực OTP (nếu cần)
3. Chạy request mong muốn; script `prerequest` của collection sẽ tự gắn header cần thiết trước khi gửi.

---

## 👨‍💻 Tác giả

| | |
|---|---|
| **Tác giả** | Vũ Tuấn Kiệt |
| **Bút danh** | TAAgnes |
| **Email** | [taagnes3110@gmail.com](mailto:taagnes3110@gmail.com) |
| **SĐT** | 0936992346 |

---

*Đồ án Quản lý thư viện — Trường Đại học Giao thông Vận tải.*
