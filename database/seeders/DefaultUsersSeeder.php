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
     *
     * @return void
     */
    public function run(): void
    {
        $defaultPassword = 'password';

        $accounts = [
            [
                'code' => 'UTCADMIN',
                'name' => 'Quản trị hệ thống UTC',
                'email' => 'admin@utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::ADMIN,
                'phone' => '0912345001',
            ],
            [
                'code' => 'UTCLIB001',
                'name' => 'Thủ thư Thư viện UTC',
                'email' => 'librarian@utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::LIBRARIAN,
                'phone' => '0912345002',
            ],
            [
                'code' => 'UTCSTU001',
                'name' => 'Sinh viên thử nghiệm UTC',
                'email' => 'student@st.utc.edu.vn',
                'password' => $defaultPassword,
                'user_type' => RoleType::MEMBER,
                'phone' => '0912345003',
            ],
        ];

        foreach ($accounts as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
