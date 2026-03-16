## 📚 API UTC-eLibrary — Kế hoạch triển khai

Tài liệu này liệt kê **các nhóm API đã có**, **các API cần xây dựng thêm** cho đồ án, và **tài liệu chuẩn nên học** để triển khai backend Laravel một cách bài bản.

---

## 1. Tổng quan phiên bản

- **Base URL (local)**: `http://localhost:8000`
- **API prefix**: `/api/v1`
- **Chuẩn trả về**:
  - Body JSON dạng: `{ "success": true|false, "data": ..., "message": "...", "errors": ... }` (qua `ApiResponse`)
- **Bảo mật**:
  - JWT Token (hoặc tương đương) cho nhóm `/api/v1/auth/*`, `/api/v1/me/*`, các route có middleware `init`
  - Phân quyền dựa trên `roles` & `permissions` (enum `RoleType`, middleware `role_or_permission`)

---

## 2. Nhóm API đã có (hiện tại)

### 2.1. Health check (không cần auth)

- **GET** `/api/health`
  - **Mục đích**: Kiểm tra nhanh trạng thái hệ thống (DB, cache, Redis).
  - **Output chính**:
    - `status`: `"ok"` hoặc `"degraded"`
    - `checks`: `{ database: bool, cache: bool, redis: bool|null }`
    - `timestamp`: thời gian hiện tại.

### 2.2. Auth (đăng nhập / đăng ký / OTP)

Prefix: `/api/v1/auth`  
Middleware: `throttle:auth` (chống spam)

- **POST** `/api/v1/auth/login`
- **POST** `/api/v1/auth/register`
- **POST** `/api/v1/auth/verify-otp`
- **POST** `/api/v1/auth/resend-otp`
- **POST** `/api/v1/auth/reset-password`

Ngoài ra:

- **POST** `/api/v1/auth/refresh`  
  - Middleware: `throttle:refresh`
  - Mục đích: cấp lại access token mới từ refresh token.

### 2.3. Auth sau khi đăng nhập (user hiện tại)

Prefix: `/api/v1/auth`  
Middleware: `init` (khởi tạo ngữ cảnh user, locale, v.v.)

- **POST** `/api/v1/auth/logout`
- **GET** `/api/v1/auth/user`

### 2.4. Hồ sơ cá nhân (Me)

Prefix: `/api/v1/me`  
Middleware: `init`

- **GET** `/api/v1/me/profile`
- **PUT** `/api/v1/me/profile`

### 2.5. Master data (dữ liệu dùng chung)

- **GET** `/api/v1/master-data`  
  - Middleware: `init`
  - **Trả về** (qua `MasterDataService`):
    - `faculties`: danh sách khoa đang active.
    - `departments`: danh sách bộ môn đang active.
    - `cohorts`: danh sách khóa (K60, K61, …).
    - `role_types`: danh sách loại vai trò (theo `RoleType`).
  - **Cache**: key `api:master-data`, TTL 3600s.

### 2.6. Nhóm quản trị (Admin / Librarian / Super Admin)

Middleware:

- `init`
- `role_or_permission:SUPER_ADMIN|role_prefix_ADMIN|role_prefix_LIBRARIAN`

#### 2.6.1. Khoa (Faculty)

Prefix: `/api/v1/faculties`

- `GET /faculties` – danh sách
- `GET /faculties/{id}` – chi tiết
- `POST /faculties` – tạo mới
- `PUT /faculties/{id}` – cập nhật
- `DELETE /faculties/{id}` – xóa (mềm/hard tùy Controller)

#### 2.6.2. Người dùng (User)

Prefix: `/api/v1/users`

- `GET /users`
- `GET /users/trash`
- `POST /users`
- `POST /users/{id}/toggle-status`
- `POST /users/{id}/avatar`
- `GET /users/{user}`
- `PUT /users/{user}`
- `DELETE /users/{user}`
- `POST /users/restore/{id}`
- `DELETE /users/force/{id}`

#### 2.6.3. Vai trò (Role)

Prefix: `/api/v1/roles`

- `GET /roles`
- `POST /roles`
- `GET /roles/{id}`
- `PUT /roles/{id}`
- `DELETE /roles/{id}`
- `POST /roles/{id}/permissions`
- `DELETE /roles/{id}/permissions`

#### 2.6.4. Quyền (Permission)

Prefix: `/api/v1/permissions`

- `GET /permissions`
- `POST /permissions`

---

## 3. Các nhóm API cần triển khai thêm (kế hoạch đồ án)

Các nhóm dưới đây là **đề cương cụ thể** cho phần API bạn có thể trình bày trong báo cáo đồ án và lần lượt hiện thực trong code.  
Trong mỗi nhóm, mình đều ghi rõ **API để FE lấy dữ liệu (GET)** và **API để FE “đưa dữ liệu lên” (POST/PUT/DELETE)**.

### 3.1. Quản lý Sách & Tài liệu (`books`)

Prefix gợi ý: `/api/v1/books`

- **GET** `/books`
  - Tìm kiếm, lọc theo: từ khóa, tác giả, thể loại, năm xuất bản, ngôn ngữ.
  - Phân trang (`page`, `per_page`).
- **GET** `/books/{id}`
  - Chi tiết 1 tài liệu (metadata + thông tin kho).
- **POST** `/books`  👉 *FE gửi dữ liệu tạo sách*
  - Body (ví dụ): `{ title, author_ids[], publisher_id, year, language_id, category_ids[], isbn, ... }`
  - Dùng cho màn hình **Thêm sách mới** (Admin/Thủ thư).
- **PUT** `/books/{id}` 👉 *FE gửi dữ liệu cập nhật sách*
  - Body: các trường muốn sửa.
  - Dùng cho màn hình **Sửa thông tin sách**.
- **DELETE** `/books/{id}` 👉 *FE yêu cầu xóa sách*
  - Xóa (mềm) một đầu sách từ màn hình **Danh sách sách**.
- **GET** `/books/{id}/copies`
  - Danh sách bản in / bản số tương ứng (nếu tách bảng).

### 3.2. Danh mục / Phân loại (`categories`, `languages`, `classifications`)

Prefix gợi ý:

- `/api/v1/categories`
- `/api/v1/languages`
- `/api/v1/classifications`

Các API cơ bản cho từng loại danh mục (FE dùng để **lấy danh sách lên combobox** và **gửi dữ liệu khi thêm/sửa danh mục**):

- `GET` danh sách (có filter, phân trang) 👉 FE load bảng/combobox.
- `POST` tạo mới 👉 FE gửi dữ liệu form tạo danh mục.
- `PUT` cập nhật 👉 FE gửi dữ liệu form sửa.
- `DELETE` xóa 👉 FE gửi yêu cầu xóa bản ghi.

### 3.3. Tác giả (`authors`)

Prefix gợi ý: `/api/v1/authors`

- **GET** `/authors`
- **GET** `/authors/{id}`
- **POST** `/authors` 👉 FE tạo tác giả mới từ form.
- **PUT** `/authors/{id}` 👉 FE cập nhật thông tin tác giả.
- **DELETE** `/authors/{id}` 👉 FE xóa/ẩn tác giả (nếu cho phép).

### 3.4. Nhà xuất bản (`publishers`)

Prefix gợi ý: `/api/v1/publishers`

- **GET** `/publishers`
- **GET** `/publishers/{id}`
- **POST** `/publishers` 👉 FE tạo NXB mới.
- **PUT** `/publishers/{id}` 👉 FE cập nhật NXB.
- **DELETE** `/publishers/{id}` 👉 FE xóa/ẩn NXB.

### 3.5. Mượn – Trả tài liệu (`loans`)

Prefix gợi ý: `/api/v1/loans`

- **GET** `/loans`
  - Danh sách phiếu mượn (lọc theo trạng thái: đang mượn, đã trả, quá hạn; theo bạn đọc).
- **GET** `/loans/{id}`
- **POST** `/loans` 👉 *FE tạo phiếu mượn*
  - Body gợi ý: `{ reader_id, items: [{ book_id, copy_id }], expected_return_date, note }`.
  - Dùng cho màn hình **Lập phiếu mượn** ở quầy thủ thư.
- **POST** `/loans/{id}/return` 👉 *FE gửi yêu cầu trả sách*
  - Dùng cho nút **Trả sách** trong chi tiết phiếu.
- **POST** `/loans/{id}/renew` 👉 *FE gửi yêu cầu gia hạn*
  - Gia hạn dựa trên `loan_policies`, dùng cho nút **Gia hạn**.

### 3.6. Bạn đọc / Thẻ thư viện (`readers`)

Nếu tách riêng khỏi `users`:

Prefix gợi ý: `/api/v1/readers`

- `GET` danh sách bạn đọc.
- `GET` chi tiết.
- `POST` tạo thẻ / kích hoạt 👉 FE gửi form tạo bạn đọc / phát hành thẻ.
- `PUT` cập nhật thông tin 👉 FE gửi form chỉnh sửa thông tin bạn đọc.
- `POST` khóa / mở thẻ 👉 FE gửi hành động toggle trạng thái thẻ.

### 3.7. Lưu tài liệu / danh sách yêu thích (`saved-items`)

Prefix gợi ý: `/api/v1/saved-items`

- **GET** `/saved-items`
  - Danh sách tài liệu đã lưu của user hiện tại.
- **POST** `/saved-items`
  - Body: `{ book_id: number }`
- **DELETE** `/saved-items/{id}`

### 3.8. Thống kê / Báo cáo (`stats`, `reports`)

Prefix gợi ý:

- `/api/v1/stats`
- `/api/v1/reports`

Ví dụ API:

- **GET** `/stats/overview`
  - Tổng số sách, số bản in, số bạn đọc, số phiếu mượn đang mở.
- **GET** `/stats/top-books`
- **GET** `/stats/top-readers`
- **GET** `/reports/loans`
  - Báo cáo mượn trả theo khoảng thời gian.

---

## 4. Gợi ý trình bày trong báo cáo đồ án

Khi viết tài liệu, bạn có thể thêm **phần “Danh sách API hệ thống”** với các cột:

- **Tên nhóm API** (Auth, Master Data, Books, Loans, …)
- **Endpoint** (Method + URL)
- **Mô tả**
- **Input (request body/query)** – tóm tắt
- **Output (response)** – tóm tắt
- **Quyền / Middleware** (vd: `init`, `role:ADMIN`)

Phần này có thể lấy trực tiếp từ các mục 2 và 3 trong file này.

---

## 5. Tài liệu chuẩn nên học

### 5.1. Laravel (Backend)

- **Routing**: tài liệu chính thức Laravel – phần Routing  
  Link: [`https://laravel.com/docs/routing`](https://laravel.com/docs/routing)
- **Controllers**: cách viết controller RESTful, resource controller  
  Link: [`https://laravel.com/docs/controllers`](https://laravel.com/docs/controllers)
- **Eloquent ORM**: truy vấn dữ liệu, quan hệ, pagination  
  Link: [`https://laravel.com/docs/eloquent`](https://laravel.com/docs/eloquent)
- **Validation** (Form Request)  
  Link: [`https://laravel.com/docs/validation`](https://laravel.com/docs/validation)
- **Authentication**  
  Link: [`https://laravel.com/docs/authentication`](https://laravel.com/docs/authentication)
- **Authorization (Gate, Policy, Roles)**  
  Link: [`https://laravel.com/docs/authorization`](https://laravel.com/docs/authorization)
- **API Resources** (định dạng JSON trả về)  
  Link: [`https://laravel.com/docs/eloquent-resources`](https://laravel.com/docs/eloquent-resources)
- **Caching** (dùng cho `MasterDataService`)  
  Link: [`https://laravel.com/docs/cache`](https://laravel.com/docs/cache)

### 5.2. Thiết kế RESTful API

- Nguyên tắc đặt **URL, HTTP method, status code, error handling**:
  - Tài liệu tham khảo:  
    [`https://restfulapi.net`](https://restfulapi.net)  
    [`https://martinfowler.com/articles/richardsonMaturityModel.html`](https://martinfowler.com/articles/richardsonMaturityModel.html)

### 5.3. Công cụ hỗ trợ test API

- **Postman** hoặc **Insomnia**:
  - Dùng để test từng endpoint, lưu collection API phục vụ báo cáo.
  - Có thể xuất file collection đính kèm trong phụ lục đồ án.

---

## 6. Lộ trình triển khai (gợi ý ngắn)

1. **Hoàn thiện Auth + User + Role + Permission** (đã có phần lớn trong project).
2. **Xây dựng module Books (tài liệu)**: model, migration, service, controller, API.
3. **Xây dựng module Authors, Publishers, Categories, Languages**.
4. **Xây dựng module Loans (mượn – trả)** + tích hợp `loan_policies`.
5. **Bổ sung Saved Items, Stats** cho hoàn thiện trải nghiệm người dùng và admin.
6. **Viết test cơ bản** cho một vài API quan trọng (Auth, Books, Loans).

Tài liệu này có thể được đính kèm trực tiếp trong **phần Phụ lục API** của đồ án, hoặc trích các bảng/endpoint quan trọng vào các chương Phân tích – Thiết kế – Cài đặt.

