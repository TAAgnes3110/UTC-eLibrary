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
        $defaultPassword = 'password'; // Model User cast 'password' => 'hashed'

        $accounts = [
            [
                'code' => 'ADMIN001',
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => $defaultPassword,
                'user_type' => RoleType::ADMIN,
                'phone' => '0900000001',
            ],
            [
                'code' => 'LIBRARIAN001',
                'name' => 'Thủ thư',
                'email' => 'librarian@example.com',
                'password' => $defaultPassword,
                'user_type' => RoleType::LIBRARIAN,
                'phone' => '0900000002',
            ],
            [
                'code' => 'MEMBER001',
                'name' => 'Người dùng',
                'email' => 'user@example.com',
                'password' => $defaultPassword,
                'user_type' => RoleType::MEMBER,
                'phone' => '0900000003',
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
