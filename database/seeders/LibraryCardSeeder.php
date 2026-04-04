<?php

namespace Database\Seeders;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\Period;
use App\Models\User;
use Illuminate\Database\Seeder;

class LibraryCardSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $expiry = $now->copy()->addYear();
        $studentPeriod = Period::query()->where('code', 'NK2024')->first();

        $users = User::query()
            ->whereIn('email', [
                'admin@utc.edu.vn',
                'librarian@utc.edu.vn',
                'student@st.utc.edu.vn',
            ])
            ->get();

        foreach ($users as $user) {
            $holderType = $user->user_type === RoleType::STUDENT
                ? LibraryCard::HOLDER_TYPE_STUDENT
                : LibraryCard::HOLDER_TYPE_TEACHER;

            $card = LibraryCard::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'card_number' => sprintf('UTC-%06d', $user->id),
                    'holder_type' => $holderType,
                    'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                    'status' => LibraryCardStatus::ACTIVE,
                    'is_active' => true,
                    'issue_date' => $now->toDateString(),
                    'expiry_date' => $expiry->toDateString(),
                    'full_name' => $user->name,
                    'code' => $user->code,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'faculty_id' => $user->faculty_id,
                    'department_id' => $user->department_id,
                    'period_id' => $holderType === LibraryCard::HOLDER_TYPE_STUDENT && $studentPeriod
                        ? $studentPeriod->id
                        : null,
                    'reviewed_at' => $now,
                    'reviewed_by' => $user->id,
                    'issued_by' => $user->id,
                ]
            );

            $card->payment()->updateOrCreate(
                ['library_card_id' => $card->id],
                [
                    'payment_status' => LibraryCard::PAYMENT_PAID,
                    'payment_amount' => 50000,
                    'paid_at' => $now,
                    'payment_method' => 'cash',
                ]
            );
        }
    }
}
