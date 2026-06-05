<?php

namespace Tests\Concerns;

use App\Models\LoanPolicy;

trait SeedsLoanPolicies
{
    protected function seedLoanPolicies(): void
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
                    'damage_fine_percent' => 0.1,
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
                    'damage_fine_percent' => 0.1,
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
                    'damage_fine_percent' => 0.1,
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
