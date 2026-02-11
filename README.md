[text](README.md)# UTC-eLibrary - Đồ án Quản lý thư viện

Hệ thống quản lý thư viện trường Đại học Giao thông Vận tải (UTC).

---

## 👨‍💻 Thông tin tác giả

- **Tác giả:** Vũ Tuấn Kiệt
- **Bút danh:** TAAgnes
- **Email:** [taagnes3110@gmail.com](mailto:taagnes3110@gmail.com)
- **Số điện thoại:** 0936992346

---

## ℹ️ Giới thiệu

Dự án này là Đồ án Quản lý thư viện, được xây dựng nhằm mục đích quản lý sách, độc giả, và quy trình mượn trả sách một cách hiệu quả và hiện đại.

### Công nghệ sử dụng
- **Backend:** Laravel
- **Frontend:** Vue.js
- **Database:** MySQL
- **Styling:** TailwindCSS

---

## 🌟 Chức năng Hệ thống

Hệ thống được thiết kế với đầy đủ các nghiệp vụ quản lý thư viện chuyên nghiệp:

### 📚 Quản lý Sách (Tài nguyên)
- **Quản lý đa dạng:** Hỗ trợ quản lý cả sách bản cứng và tài liệu số (bản mềm).
- **Nghiệp vụ chi tiết:**
  - Nhập sách và phân loại sách khoa học.
  - Hỗ trợ in nhãn sách, in phích, và in sổ quản lý.
  - Quy trình thanh lý sách cũ/hỏng.

### 👤 Quản lý Độc giả
- Quản lý thông tin chi tiết của độc giả.
- Tích hợp chức năng **in thẻ thư viện**.

### 🔄 Quản lý Mượn - Trả
- Theo dõi chặt chẽ quy trình mượn trả tài liệu.
- Quản lý quá trình gia hạn, phạt quá hạn (nếu có).

### 📊 Báo cáo & Thống kê - Kiểm kê
- **Kiểm kê:** Chức năng kiểm kê tài sản định kỳ nhanh chóng chính xác.
- **Hệ thống báo cáo mạnh mẽ:**
  - Báo cáo tổng quan về số lượng sách và đầu sách hiện có.
  - Thống kê hoạt động mượn trả chi tiết theo thời gian (ngày, tháng, năm).
  - Phân tích dữ liệu mượn trả theo từng lớp học, từng nhóm độc giả cụ thể.

---

## 🚀 Cài đặt và Triển khai

1. **Clone repository:**
   ```bash
   git clone https://github.com/TAAgnes3110/UTC-eLibrary.git
   cd UTC-eLibrary
   ```

2. **Cài đặt dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Cấu hình môi trường:**
   - Copy file `.env.example` thành `.env`
   - Cấu hình database và các biến môi trường khác.

4. **Chạy migration và seeder:**
   ```bash
   php artisan migrate --seed
   ```

5. **Chạy ứng dụng:**
   ```bash
   npm run dev
   php artisan serve
   ```

6. **Chạy Ngrok (tùy chọn — để truy cập từ bên ngoài):**

   Ngrok tạo một đường hầm (tunnel) để expose localhost ra internet, hữu ích khi:
   - Test trên điện thoại / thiết bị khác
   - Demo cho người khác xem
   - Test callback từ bên thứ 3 (Microsoft OAuth, Webhook...)

   **Bước 1:** Đăng ký tài khoản tại [ngrok.com](https://ngrok.com) và lấy Authtoken.

   **Bước 2:** Thêm vào file `.env`:
   ```env
   NGROK_AUTHTOKEN=your_ngrok_authtoken_here
   ```

   **Bước 3:** Chạy ngrok:

   - **Windows (CMD / PowerShell):**
     ```cmd
     start-ngrok.bat
     ```

   - **Git Bash / WSL:**
     ```bash
     # Cách 1: Gọi trực tiếp file .bat
     cmd //c start-ngrok.bat

     # Cách 2: Chạy ngrok thủ công
     ngrok http 8000
     ```

   **Bước 4:** Copy URL ngrok (ví dụ: `https://xxxx.ngrok-free.dev`) và cập nhật `APP_URL` trong `.env`:
   ```env
   APP_URL=https://xxxx.ngrok-free.dev
   ```

   > ⚠️ **Lưu ý:** Mỗi lần restart ngrok sẽ tạo URL mới (trừ khi dùng gói trả phí với domain cố định). Nhớ cập nhật lại `APP_URL` sau mỗi lần restart.
