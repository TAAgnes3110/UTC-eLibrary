<?php

namespace Database\Seeders;

use App\Enums\LibraryCardStatus;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\LibraryCardPayment;
use App\Models\Period;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu mẫu thẻ thư viện (dev/demo) — chạy sau Faculty, Period, DefaultUsers.
 */
class LibraryCardSampleSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = Faculty::query()->where('code', 'CNTT')->first() ?? Faculty::query()->first();
        $period = Period::query()->orderBy('id')->first();
        $librarian = User::query()->where('email', 'librarian@utc.edu.vn')->first();
        $student = User::query()->where('email', 'student@st.utc.edu.vn')->first();
        $teacher = User::query()->where('email', 'teacher@st.utc.edu.vn')->first();
        $member = User::query()->where('email', 'member@st.utc.edu.vn')->first();

        if ($faculty === null || $period === null) {
            $this->command?->warn('LibraryCardSampleSeeder: bỏ qua (thiếu faculty hoặc period).');

            return;
        }

        $issuerId = $librarian?->id;

        // Mã thẻ (card_number) = mã định danh (code), giống LibraryCardAccountService / LibraryCardGuestService.
        $samples = [
            [
                'code' => 'DEMO-SV-001',
                'user_id' => $student?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
                'full_name' => $student?->name ?? 'Sinh viên demo',
                'email' => 'the-demo-sv@utc.local',
                'phone' => '0901000001',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CNTT-K67',
                'date_of_birth' => '2005-03-15',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
                'status' => LibraryCardStatus::PENDING->value,
                'payment' => null,
            ],
            [
                'code' => 'DEMO-GV-001',
                'user_id' => $teacher?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_TEACHER,
                'full_name' => $teacher?->name ?? 'Giảng viên demo',
                'email' => 'the-demo-gv@utc.local',
                'phone' => '0901000002',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1985-07-20',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_PAYMENT,
                'status' => LibraryCardStatus::PENDING->value,
                'payment' => [
                    'payment_status' => LibraryCard::PAYMENT_PENDING,
                    'payment_amount' => 40000,
                    'payment_method' => 'walk_in',
                ],
            ],
            [
                'code' => 'DEMO-NGOAI-001',
                'user_id' => null,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => 'Người đọc ngoài demo',
                'email' => 'docgia.ngoai@example.com',
                'phone' => '0901000003',
                'address' => 'Cầu Giấy, Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1990-11-01',
                'external_organization' => 'Công ty TNHH Demo',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->toDateString(),
                'payment' => [
                    'payment_status' => LibraryCard::PAYMENT_PAID,
                    'payment_amount' => 40000,
                    'paid_at' => now()->subDay(),
                    'payment_method' => 'walk_in',
                ],
            ],
            [
                'code' => 'DEMO-NGOAI-002',
                'user_id' => null,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => 'Chờ nhận thẻ (demo)',
                'email' => 'cho.nhan.the@example.com',
                'phone' => '0901000004',
                'address' => 'Đống Đa, Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1992-04-22',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_PICKUP,
                'status' => LibraryCardStatus::ACTIVE->value,
                'payment' => [
                    'payment_status' => LibraryCard::PAYMENT_PAID,
                    'payment_amount' => 40000,
                    'paid_at' => now()->subHours(3),
                    'payment_method' => 'walk_in',
                ],
            ],
            [
                'code' => 'DEMO-TC-001',
                'user_id' => null,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => 'Hồ sơ đăng ký bị từ chối (demo)',
                'email' => 'tu.choi.demo@example.com',
                'phone' => '0901000006',
                'address' => 'Ba Đình, Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1995-02-28',
                'workflow_status' => LibraryCard::WORKFLOW_REJECTED,
                'status' => LibraryCardStatus::LOCKED->value,
                'notes' => 'Dữ liệu mẫu: từ chối hồ sơ.',
                'payment' => null,
            ],
        ];

        if ($member !== null) {
            $samples[] = [
                'code' => 'DEMO-MEMBER-001',
                'user_id' => $member->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => $member->name,
                'email' => 'member.demo.the@utc.local',
                'phone' => '0901000005',
                'address' => 'Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1988-06-10',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
                'status' => LibraryCardStatus::PENDING->value,
                'payment' => null,
            ];
        }

        foreach ($samples as $row) {
            $payment = $row['payment'] ?? null;
            unset($row['payment']);

            $row['card_number'] = $row['code'];

            $row['issued_by'] = $issuerId;
            $row['created_by'] = $issuerId;
            $row['updated_by'] = $issuerId;

            $card = LibraryCard::query()->updateOrCreate(
                ['code' => $row['code']],
                $row
            );

            if ($payment !== null) {
                LibraryCardPayment::query()->updateOrCreate(
                    ['library_card_id' => $card->id],
                    array_merge($payment, ['library_card_id' => $card->id])
                );
            }

            if (($row['workflow_status'] ?? null) === LibraryCard::WORKFLOW_REJECTED) {
                $card->delete();
            }
        }
    }
}
