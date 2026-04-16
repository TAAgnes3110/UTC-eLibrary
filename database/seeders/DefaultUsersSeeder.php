<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tạo nhóm tài khoản cốt lõi + vài tài khoản demo nhẹ.
 * Mật khẩu mặc định: password (nên đổi sau khi đăng nhập lần đầu).
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
            [
                'code' => '001000000007',
                'name' => 'Giảng viên demo 2',
                'email' => 'teacher2@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::TEACHER,
                'phone' => '0912345006',
            ],
            [
                'code' => '001000000008',
                'name' => 'Sinh viên demo 2',
                'email' => 'student2@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::STUDENT,
                'phone' => '0912345007',
            ],
            [
                'code' => '001000000009',
                'name' => 'Bạn đọc demo 2',
                'email' => 'member2@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::MEMBER,
                'phone' => '0912345008',
            ],
            [
                'code' => '001000000010',
                'name' => 'Sinh viên demo 3',
                'email' => 'student3@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::STUDENT,
                'phone' => '0912345009',
            ],
        ];

        foreach ($accounts as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
