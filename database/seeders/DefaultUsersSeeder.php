<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tạo 3 tài khoản mặc định: Admin, Thủ thư, Người dùng.
 * Mật khẩu mặc định: password (nên đổi sau khi đăng nhập lần đầu).
 *
 * @todo Đọc mật khẩu từ env (e.g. SEEDER_DEFAULT_PASSWORD) cho môi trường dev.
 */
class DefaultUsersSeeder extends Seeder
{
    /**
     * Chạy seeder: firstOrCreate theo email, bỏ qua nếu đã tồn tại.
     */
    public function run(): void
    {
        $defaultPassword = 'password';

        $accounts = [
            [
                'code' => '001000000001',
                'name' => 'Super Admin UTC',
                'email' => 'superadmin@utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::SUPER_ADMIN,
                'phone' => '0912345000',
            ],
            [
                'code' => '001000000002',
                'name' => 'Quản trị hệ thống UTC',
                'email' => 'admin@utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::ADMIN,
                'phone' => '0912345001',
            ],
            [
                'code' => '001000000003',
                'name' => 'Thủ thư Thư viện UTC',
                'email' => 'librarian@utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::LIBRARIAN,
                'phone' => '0912345002',
            ],
            [
                'code' => '001000000004',
                'name' => 'Giảng viên thử nghiệm UTC',
                'email' => 'teacher@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::TEACHER,
                'phone' => '0912345004',
            ],
            [
                'code' => '001000000005',
                'name' => 'Sinh viên thử nghiệm UTC',
                'email' => 'student@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::STUDENT,
                'phone' => '0912345003',
            ],
            [
                'code' => '001000000006',
                'name' => 'Bạn đọc thử nghiệm UTC',
                'email' => 'member@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::MEMBER,
                'phone' => '0912345005',
            ],
        ];

        foreach ($accounts as $data) {
            // Dùng updateOrCreate để cập nhật lại user_type/fields,
            // tránh tình trạng email đã tồn tại nhưng user_type cũ -> dẫn tới 403 RBAC.
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
