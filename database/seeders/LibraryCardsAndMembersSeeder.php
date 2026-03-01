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

        // Sinh viên mẫu theo khóa K63–K66 (K66 khóa mới nhất 2025–2026)
        $faculty = Faculty::where('code', 'CNTT')->first();
        $department = $faculty ? Department::where('faculty_id', $faculty->id)->first() : null;

        $students = [
            ['code' => 'SV2021001', 'name' => 'Nguyễn Văn An', 'email' => 'sv.k63.01@example.com', 'phone' => '0911111001', 'card_number' => 'TV-K63-001', 'cohort' => 'K63'],
            ['code' => 'SV2021002', 'name' => 'Trần Thị Bình', 'email' => 'sv.k63.02@example.com', 'phone' => '0911111002', 'card_number' => 'TV-K63-002', 'cohort' => 'K63'],
            ['code' => 'SV2022001', 'name' => 'Lê Văn Cường', 'email' => 'sv.k64.01@example.com', 'phone' => '0911111003', 'card_number' => 'TV-K64-001', 'cohort' => 'K64'],
            ['code' => 'SV2022002', 'name' => 'Phạm Thị Dung', 'email' => 'sv.k64.02@example.com', 'phone' => '0911111004', 'card_number' => 'TV-K64-002', 'cohort' => 'K64'],
            ['code' => 'SV2023001', 'name' => 'Hoàng Văn Em', 'email' => 'sv.k65.01@example.com', 'phone' => '0911111005', 'card_number' => 'TV-K65-001', 'cohort' => 'K65'],
            ['code' => 'SV2023002', 'name' => 'Vũ Thị Phương', 'email' => 'sv.k65.02@example.com', 'phone' => '0911111006', 'card_number' => 'TV-K65-002', 'cohort' => 'K65'],
            ['code' => 'SV2024001', 'name' => 'Nguyễn Văn A', 'email' => 'sv.k66.01@example.com', 'phone' => '0911111007', 'card_number' => 'TV-K66-001', 'cohort' => 'K66'],
            ['code' => 'SV2024002', 'name' => 'Trần Thị B', 'email' => 'sv.k66.02@example.com', 'phone' => '0911111008', 'card_number' => 'TV-K66-002', 'cohort' => 'K66'],
        ];

        foreach ($students as $s) {
            $cohort = $s['cohort'] ?? null;
            unset($s['cohort']);
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
                    'cohort' => $cohort,
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
