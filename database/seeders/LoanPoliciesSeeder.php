<?php

namespace Database\Seeders;

use App\Models\LoanPolicy;
use Illuminate\Database\Seeder;

/**
 * Ba chính sách mặc định theo loại thẻ: sinh viên, giảng viên, bạn đọc (khớp user_type / holder thẻ).
 * Giới hạn số cuốn theo Nội quy — Điều 5.2 (QĐ 2706/QĐ-ĐHGTVT); hạn ngày / gia hạn / phạt: chỉnh khi có quyết định chính thức.
 */
class LoanPoliciesSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'code' => 'LOAN_STUDENT',
                'name' => 'Thẻ sinh viên',
                'user_type' => 'STUDENT',
                'max_books' => 12,
                'max_days' => 30,
                'max_renewals' => 2,
                'overdue_fine_per_day' => 0,
                'allow_home' => true,
                'allow_onsite' => true,
                'params' => [
                    'max_textbooks' => 10,
                    'max_reference' => 2,
                ],
            ],
            [
                'code' => 'LOAN_TEACHER',
                'name' => 'Thẻ giáo viên',
                'user_type' => 'TEACHER',
                'max_books' => 10,
                'max_days' => 60,
                'max_renewals' => 2,
                'overdue_fine_per_day' => 0,
                'allow_home' => true,
                'allow_onsite' => true,
                'params' => [
                    'max_textbooks' => 7,
                    'max_reference' => 3,
                ],
            ],
            [
                'code' => 'LOAN_EXTERNAL_READER',
                'name' => 'Thẻ bạn đọc',
                'user_type' => 'MEMBER',
                'max_books' => 5,
                'max_days' => 0,
                'max_renewals' => 0,
                'overdue_fine_per_day' => 0,
                'allow_home' => false,
                'allow_onsite' => true,
                'params' => [
                    'max_textbooks' => 5,
                    'max_reference' => 5,
                ],
            ],
        ];

        foreach ($policies as $row) {
            LoanPolicy::query()->updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
