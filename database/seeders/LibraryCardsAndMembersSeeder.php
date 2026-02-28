<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tạo thẻ thư viện cho user có sẵn và thêm vài bạn đọc (sinh viên) có khoa/lớp để test API.
 */
class LibraryCardsAndMembersSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'password';

        // Thẻ cho 3 user mặc định (admin, librarian, user)
        $emails = ['admin@example.com', 'librarian@example.com', 'user@example.com'];
        $cardNumbers = ['TV-ADMIN-001', 'TV-LIB-001', 'TV-2024001'];
        foreach ($emails as $i => $email) {
            $user = User::where('email', $email)->first();
            if ($user && !$user->libraryCard) {
                LibraryCard::create([
                    'user_id' => $user->id,
                    'card_number' => $cardNumbers[$i],
                    'issue_date' => now(),
                    'expiry_date' => now()->addYears(2),
                    'status' => 'active',
                    'is_active' => true,
                    'card_type' => 'STANDARD',
                ]);
            }
        }

        // Thêm 2 sinh viên có khoa/lớp + thẻ
        $faculty = Faculty::where('code', 'CNTT')->first();
        $department = $faculty ? Department::where('faculty_id', $faculty->id)->first() : null;

        $students = [
            [
                'code' => 'SV2024001',
                'name' => 'Nguyễn Văn A',
                'email' => 'sv001@example.com',
                'phone' => '0911111001',
                'card_number' => 'TV-2024002',
            ],
            [
                'code' => 'SV2024002',
                'name' => 'Trần Thị B',
                'email' => 'sv002@example.com',
                'phone' => '0911111002',
                'card_number' => 'TV-2024003',
            ],
        ];

        foreach ($students as $s) {
            $user = User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'code' => $s['code'],
                    'name' => $s['name'],
                    'email' => $s['email'],
                    'password' => $password,
                    'phone' => $s['phone'],
                    'user_type' => RoleType::MEMBER,
                    'is_active' => true,
                    'faculty_id' => $faculty?->id,
                    'department_id' => $department?->id,
                ]
            );
            if (!$user->libraryCard) {
                LibraryCard::create([
                    'user_id' => $user->id,
                    'card_number' => $s['card_number'],
                    'issue_date' => now(),
                    'expiry_date' => now()->addYears(2),
                    'status' => 'active',
                    'is_active' => true,
                    'card_type' => 'STANDARD',
                ]);
            }
        }
    }
}
