# UTC eLibrary - Kế hoạch Triển khai Frontend Vue 3

Tài liệu này hướng dẫn cách chuyển đổi bản mockup HTML thành một ứng dụng Vue 3 hoàn chỉnh, sử dụng **Inertia.js**, **Tailwind CSS v4**, và **Shadcn-Vue**.

---

## 1. Kiến trúc Dự án (Vue)

### Layouts (`resources/js/Layouts`)
*   **`AppLayout.vue`**: Wrapper chính cho tất cả các trang sau khi đăng nhập.
    *   Sidebar linh hoạt (Phân quyền: Admin, Thủ thư, Sinh viên).
    *   Thanh Header với hiệu ứng Glassmorphism, tìm kiếm và hồ sơ người dùng.
    *   Sử dụng `slot` cho nội dung chính với hiệu ứng chuyển trang `animate-enter`.
*   **`AuthLayout.vue`**: Dành riêng cho các trang Đăng nhập/Đăng ký (tương ứng với bố cục 2 cột trong mockup).

### Các Component Cốt lõi (`resources/js/Components/Library`)
*   **`SidebarLink.vue`**: Wrapper cho Link của Inertia với kiểu dáng trạng thái active và icon Lucide.
*   **`StatCard.vue`**: Thẻ hiển thị các chỉ số dashboard (Tổng số sách, Quá hạn, v.v.).
*   **`BookGridCard.vue`**: Thẻ hiển thị sách tập trung vào ảnh bìa dành cho Cổng Sinh viên.
*   **`Badge.vue`**: Các chỉ báo trạng thái (Sẵn có, Đang mượn, Quá hạn).
*   **`SearchOverlay.vue`**: Công cụ tìm kiếm chuyên dụng cho quy trình tạo phiếu mượn.

---

## 2. Ánh xạ Component (Mockup → Vue)

| Thành phần Mockup | File/Component Vue | Logic / Props |
| :--- | :--- | :--- |
| **Sidebar** | `Layouts/Sidebar.vue` | Prop `role` (admin/thủ thư), `activeTab` dựa trên route. |
| **Form Đăng nhập** | `Pages/Auth/Login.vue` | `useForm` từ Inertia, xử lý validation. |
| **Loại tài liệu**| `Components/Library/MaterialFilter.vue` | Emit sự kiện `filter-change` lên trang cha. |
| **Bảng Sách** | `Components/Library/BookTable.vue` | Sử dụng `DataTable` của Shadcn, prop mảng `books`. |
| **Modal Phiếu mượn** | `Components/Library/LoanModal.vue` | Logic tìm kiếm sách và người dùng theo thời gian thực. |

---

## 3. Triển khai Thiết kế Cao cấp (Ví dụ Vue)

### A. Logic Layout Linh hoạt
Trong `AppLayout.vue`, sử dụng vai trò (role) từ props của Inertia để xác định cấu trúc giao diện.

```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const userRole = computed(() => page.props.auth.user.role || 'student');
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-[#F1F5F9]" :class="{ 'flex-col': userRole === 'student' }">
    <!-- Sidebar tùy biến theo vai trò -->
    <AdminSidebar v-if="userRole !== 'student'" />
    <StudentHeader v-else />

    <main class="flex-1 overflow-y-auto animate-enter">
      <slot />
    </main>
  </div>
</template>
```

### B. Cấu hình Style Tailwind (`app.css`)
Thêm các định nghĩa này để giữ được độ "chất" từ bản mockup:

```css
/* resources/css/app.css */
@layer base {
  :root {
    --utc-primary: #1e3a8a;
    --utc-secondary: #0ea5e9;
  }
}

@layer components {
  .premium-shadow {
    @apply shadow-[0_20px_25px_-5px_rgba(0,0,0,0.05),0_8px_10px_-6px_rgba(0,0,0,0.05)];
  }

  .animate-enter {
    @apply animate-[enter_0.5s_ease-out];
  }
}

@keyframes enter {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
```

---

## 4. Chiến lược Triển khai Tính năng

### 1. Dashboard (Trang Tổng quan Chung)
Tạo một trang `Pages/Dashboard.vue` duy nhất render nội dung khác nhau dựa trên `user.role`.
*   **AdminView**: Biểu đồ + Chỉ số KPI cấp cao.
*   **LibrarianView**: Các tác vụ nhanh (Trả sách, Kiểm kho).
*   **StudentView**: Danh mục tài liệu + Sách mới/nổi bật.

### 2. Quản lý Phiếu mượn (Logic Phức tạp)
*   Sử dụng `ref` cho mảng `selectedBooks` (sách đã chọn).
*   Triển khai hàm `watch` tìm kiếm sách, gọi API backend khi người dùng nhập (có debounce).
*   Sử dụng Modal từ `shadcn-vue` cho giao diện giao dịch.

### 3. State & Props
*   Sử dụng **Inertia Props** cho dữ liệu ít thay đổi (Danh mục, Loại tài liệu).
*   Sử dụng **Vue Refs/Reactive** cho trạng thái UI (Ẩn hiện Modal, bộ lọc đang chọn).
*   Sử dụng **Ziggy** để gọi các route Laravel theo tên trong file JS.

---

## 5. Các Bước Tiếp theo
1.  **Tái cấu trúc `AuthenticatedLayout.vue`**: Chia nhỏ thành `Sidebar.vue` và `TopHeader.vue`.
2.  **Khởi tạo Design Tokens**: Cập nhật màu sắc UTC vào hệ thống CSS.
3.  **Tạo các Module theo Nghiệp vụ**:
    *   `resources/js/Pages/Books`: Danh sách, Thêm/Sửa, Chi tiết.
    *   `resources/js/Pages/Loans`: Quản lý mượn trả và tích hợp QR.
    *   `resources/js/Pages/Readers`: Thông tin độc giả và Thẻ thư viện.
