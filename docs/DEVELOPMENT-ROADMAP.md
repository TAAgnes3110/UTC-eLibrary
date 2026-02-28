# Lộ trình triển khai – UTC eLibrary

Kế hoạch làm **theo thành phần** (User → Thẻ thư viện → Sách → Mượn/Trả...) và **theo thứ tự phụ thuộc** để không bị thiếu dữ liệu khi làm tính năng sau.

---

## Đã làm gần đây

| Việc | Ghi chú |
|------|--------|
| **Routes gọn** | Gộp `auth.php`, `reader.php`, `admin.php` vào `web.php` → chỉ còn `web.php`, `api.php`, `console.php` |
| **Avatar 1 user** | Admin chọn 1 user → "Cập nhật ảnh đại diện" → kéo thả ảnh (tên file tùy ý). Backend tự đặt tên, lưu `storage/app/public/avatars` |
| **ImageUploadHelper** | `storeImage()`, `deleteIfExists()` – dùng chung cho avatar, sau này ảnh thẻ/zip |
| **API** | `POST /admin/users/{id}/avatar` (web, auth) |
| **UI** | Hiển thị avatar trong danh sách user + header; modal upload rõ ràng; hướng dẫn zip ảnh thẻ (Readers/Cards) |

**Push lên Git:** Đã commit local. Khi có mạng chạy: `git push origin main`.

---

## Trạng thái hiện tại (đã có)

| Thành phần | API Backend | Ghi chú |
|------------|-------------|--------|
| **Auth** | ✅ Login, Register, OTP, Reset password, Logout, User | JWT + Microsoft OAuth (web) |
| **Users** | ✅ CRUD, trash, restore, forceDelete, updateAvatar (web) | Avatar URL đầy đủ; ImageUploadHelper |
| **Authors** | ✅ CRUD, import, trash | |
| **Books** | ✅ CRUD, import, trash | Đã có isbn, language, edition |
| **Roles/Permissions** | ✅ CRUD roles, CRUD permissions, gán/thu hồi permission cho role | Spatie, route API đã expose |
| **Faculties, Departments** | ❌ Chưa có API | Chỉ có bảng + model |
| **Categories** | ❌ Chưa có API | Chỉ có bảng + model |
| **Publishers** | ❌ Chưa có API riêng | Dùng trong Book (resolve tên → id) |
| **Library cards** | ❌ Chưa có API | Tạo thẻ khi store User (card_number), chưa CRUD thẻ độc lập |
| **Library settings** | ❌ Chưa có API | Có model + seedDefaults(), chưa GET/PUT |
| **Book copies** | ❌ Chưa có API riêng | Tạo khi thêm sách (quantity), chưa list/sửa từng bản in |
| **Loans** | ❌ Chưa có API | |
| **Fines** | ❌ Chưa có API | |
| **Reservations** | ❌ Chưa có API | |

---

## Thứ tự nên làm (theo dependency)

Luồng phụ thuộc: **Nền tảng** → **User** → **Thẻ TV** → **Sách/Bản in** → **Quy định** → **Mượn/Trả** → **Phạt & Đặt chỗ**.

```
[Faculties, Departments, Categories, Publishers]  ← Master data (form User/Book cần)
           ↓
[Library settings: seed + API đọc/sửa]             ← Quy định mượn (Loans cần)
           ↓
[Users hoàn thiện]                                 ← Đã có CRUD; thêm filter, form faculty/department
           ↓
[Library cards CRUD]                                ← Tạo/sửa thẻ, gắn user; kiểm tra canBorrow
           ↓
[Books + Book copies]                              ← Đã có Books; thêm API quản lý bản in (list/sửa status)
           ↓
[Loans: tạo phiếu, trả, gia hạn]                   ← Lõi nghiệp vụ
           ↓
[Fines, Reservations]                               ← Phạt khi trả; đặt chỗ sách
           ↓
[Dashboard, báo cáo, export]                        ← Hoàn thiện
```

---

## Kế hoạch chi tiết từng bước

### Bước 0 – Đã xong (không cần làm lại)

- Database, migrations, models đã đồng bộ (users.faculty_id, department_id; books.isbn, language, edition; indexes).
- Auth, Users, Authors, Books API đang chạy.

---

### Bước 1 – Nền tảng (Master data)

**Mục đích:** Form User cần chọn Khoa/Lớp; form Book cần chọn Danh mục/NXB. Settings cần đọc khi tạo phiếu mượn.

| # | Công việc | API / Việc cần làm | Ưu tiên |
|---|-----------|---------------------|--------|
| 1.1 | **Faculties** | CRUD API: `GET/POST/PUT/DELETE /api/v1/faculties` (admin/librarian). | Cao |
| 1.2 | **Departments** | CRUD API: `GET/POST/PUT/DELETE /api/v1/departments` (filter theo faculty_id). | Cao |
| 1.3 | **Categories** | CRUD API: `GET/POST/PUT/DELETE /api/v1/categories` (cây parent_id). | Cao |
| 1.4 | **Publishers** | CRUD API: `GET/POST/PUT/DELETE /api/v1/publishers` (để chọn NXB khi thêm sách). | Trung bình |
| 1.5 | **Library settings** | Seed `LibrarySetting::seedDefaults()` (nếu chưa chạy). API: `GET /api/v1/settings`, `GET /api/v1/settings/{group}`, `PUT /api/v1/settings` (admin). | Cao |

**Kết quả:** Có dropdown Khoa/Lớp cho User, Danh mục/NXB cho Book; đọc được quy định mượn (số ngày, số sách tối đa, gia hạn, phạt).

---

### Bước 2 – Hoàn thiện User

**Mục đích:** Form thêm/sửa bạn đọc có Khoa/Lớp; danh sách lọc theo user_type, khoa.

| # | Công việc | Chi tiết |
|---|-----------|----------|
| 2.1 | Form User | Dropdown faculty_id, department_id (gọi API faculties, departments). UserRequest đã có rule. |
| 2.2 | Danh sách User | Filter theo user_type, faculty_id (query params). API đã có with faculty, department. |
| 2.3 | (Tùy chọn) Export Excel | Xuất danh sách bạn đọc (có thẻ) ra Excel. |

**Kết quả:** Quản lý bạn đọc đầy đủ theo khoa/lớp, sẵn sàng cho mượn sách.

---

### Bước 3 – Thẻ thư viện (Library cards)

**Mục đích:** Mỗi bạn đọc cần thẻ để mượn; thủ thư cần sửa thẻ (gia hạn, khóa, đổi trạng thái).

| # | Công việc | API / Logic |
|---|-----------|-------------|
| 3.1 | **API Library cards** | `GET /api/v1/library-cards` (list, filter user_id). `GET /api/v1/library-cards/{id}`. `POST /api/v1/library-cards` (tạo thẻ, gắn user_id). `PUT /api/v1/library-cards/{id}` (sửa expiry_date, status, is_active). `DELETE` (soft delete). |
| 3.2 | **Validate thẻ** | Khi tạo phiếu mượn: kiểm tra user có thẻ active, chưa hết hạn, chưa vượt số sách, chưa có phạt chưa thanh toán (dùng `LibraryCard::canBorrow()`). |
| 3.3 | **Màn hình Admin** | Danh sách thẻ, form thêm/sửa thẻ (chọn user hoặc nhập số thẻ). |

**Kết quả:** Có CRUD thẻ; logic “được mượn hay không” dựa trên thẻ + settings.

---

### Bước 4 – Sách & Bản in (Book copies)

**Mục đích:** Mượn/trả theo **bản in** (book_copy), không chỉ đầu sách.

| # | Công việc | API / Logic |
|---|-----------|-------------|
| 4.1 | **API Book copies** | `GET /api/v1/books/{book}/copies` (danh sách bản in của 1 sách; filter status: available, borrowed, maintenance, lost). `PATCH /api/v1/books/{book}/copies/{copy}` (sửa condition, status, location). Có thể thêm `POST` tạo bản in lẻ (barcode), `DELETE` soft delete. |
| 4.2 | **Trạng thái bản in** | Khi tạo phiếu mượn: copy chuyển borrowed; khi trả: copy chuyển available. |
| 4.3 | **Màn hình Admin** | Trong trang sách: tab “Bản in”, bảng barcode / condition / status. |

**Kết quả:** Biết bản in nào đang có sẵn để chọn khi tạo phiếu mượn.

---

### Bước 5 – Mượn / Trả / Gia hạn (Loans)

**Mục đích:** Lõi nghiệp vụ: tạo phiếu mượn, trả sách, gia hạn.

| # | Công việc | API / Logic |
|---|-----------|-------------|
| 5.1 | **Đọc quy định** | Lấy từ LibrarySetting: loan_duration_days, max_books_per_reader, max_renewals, overdue_fine_per_day. Dùng khi tạo phiếu và gia hạn. |
| 5.2 | **Tạo phiếu mượn** | `POST /api/v1/loans`: body user_id (hoặc library_card_id), book_copy_id. Validate: user có thẻ, canBorrow, copy available. Ghi loan_date, due_date = loan_date + loan_duration_days, status active, copy → borrowed. LoanHistory::log('created'). |
| 5.3 | **Trả sách** | `PATCH /api/v1/loans/{id}/return`: body condition_on_return. Cập nhật return_date, status returned, condition_on_return; copy → available. Tính overdue_days, overdue_fine nếu trễ; tạo bản ghi fines (reason overdue) nếu có. LoanHistory::log('returned'). |
| 5.4 | **Gia hạn** | `POST /api/v1/loans/{id}/renew`: kiểm tra canRenew (active, chưa quá max_renewals, chưa quá hạn). Cập nhật due_date += loan_duration_days, renewal_count++, last_renewal_date. LoanHistory::log('renewed'). |
| 5.5 | **Danh sách phiếu** | `GET /api/v1/loans` (filter user_id, status, from/to). Admin: tất cả; Reader: chỉ user_id = current. |

**Kết quả:** Luồng mượn–trả–gia hạn hoàn chỉnh.

---

### Bước 6 – Phạt & Đặt chỗ

| # | Công việc | Ghi chú |
|---|-----------|--------|
| 6.1 | **Fines** | `GET /api/v1/fines` (filter user_id, status). `PATCH /api/v1/fines/{id}` (đánh dấu paid, paid_date, payment_method). Tạo fine khi trả trễ (bước 5.3) hoặc mất/hỏng sách. |
| 6.2 | **Reservations** | `POST /api/v1/reservations` (user_id, book_id, expiry_date). `GET /api/v1/reservations` (của user hoặc theo book). Hủy / fulfilled khi reader đến mượn. |

---

### Bước 7 – Dashboard & Hoàn thiện

- Dashboard: số phiếu đang mượn, quá hạn, số bạn đọc, số đầu sách (hoặc dùng library_statistics).
- Export bạn đọc, báo cáo đơn giản.
- Roles/Permissions: expose API role/permission (gán role cho user) nếu cần.
- README, tài liệu nộp đồ án.

---

## Bắt đầu từ đâu – Gợi ý

- **Nếu muốn “làm hết User rồi đến Sách rồi Thẻ”:**  
  Làm **Bước 1 (Nền tảng)** trước (Faculties, Departments, Categories, Settings) → **Bước 2 (User)** → **Bước 3 (Thẻ)** → **Bước 4 (Sách/Bản in)** → **Bước 5 (Mượn/Trả)**. Đúng thứ tự trong bảng trên.

- **Nếu muốn “ra nghiệp vụ mượn/trả nhanh nhất”:**  
  Làm tối thiểu: **1.5 (Library settings API + seed)** → **3 (Library cards API)** → **4.1 (Book copies API list)** → **5 (Loans)**. User và Books đã có; chỉ cần thẻ + bản in + quy định là đủ để tạo phiếu mượn.

**Tóm tắt:** Nên bắt đầu từ **Bước 1 – Nền tảng** (Faculties, Departments, Categories, Library settings), sau đó **User** → **Thẻ thư viện** → **Sách/Bản in** → **Mượn/Trả** → Phạt & Đặt chỗ. Làm tuần tự như vậy vừa đúng dependency vừa rõ từng thành phần (user → thẻ → sách → mượn).

---

## Kế hoạch làm tiếp (gợi ý thứ tự)

| Ưu tiên | Bước | Công việc chính | Kết quả |
|--------|------|-----------------|---------|
| 1 | **Bước 1** | Faculties, Departments, Categories, Publishers, Library settings API | Dropdown Khoa/Lớp, Danh mục/NXB; đọc quy định mượn |
| 2 | **Bước 2** | Form User có faculty/department; filter danh sách | Quản lý bạn đọc đầy đủ |
| 3 | **Bước 3** | API Library cards CRUD; canBorrow; màn Admin thẻ | Tạo/sửa thẻ, sẵn sàng mượn |
| 4 | **Bước 4** | API Book copies (list/sửa bản in); Admin tab Bản in | Chọn bản in khi tạo phiếu |
| 5 | **Bước 5** | API Loans: tạo phiếu, trả, gia hạn; đọc settings | Luồng mượn–trả hoàn chỉnh |
| 6 | **Bước 6** | Fines, Reservations API | Phạt trễ; đặt chỗ sách |
| 7 | **Bước 7** | Dashboard, export, README nộp đồ án | Hoàn thiện |
