<?php

namespace Database\Seeders;

use App\Models\LoanPolicy;
use Illuminate\Database\Seeder;

/**
 * Ba chính sách mặc định: người học + CBGV (thẻ đa năng) + bạn đọc ngoài (thẻ thư viện).
 * Giới hạn số cuốn theo Nội quy — Điều 5.2 (QĐ 2706/QĐ-ĐHGTVT); hạn ngày / gia hạn / phạt: chỉnh khi có quyết định chính thức.
 *
 * @see https://lib.utc.edu.vn/noi-quy/quy-%C4%91%E1%BB%8Bnh-s%E1%BB%AD-d%E1%BB%A5ng-th%C6%B0-vi%E1%BB%87n
 */
class LoanPoliciesSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'code' => 'LOAN_STUDENT',
                'name' => 'Người học (thẻ đa năng)',
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
                'name' => 'CBGV (thẻ đa năng)',
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
                'name' => 'Bạn đọc ngoài (thẻ thư viện)',
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
