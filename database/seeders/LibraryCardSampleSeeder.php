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
 * Dữ liệu mẫu thẻ thư viện gọn nhẹ:
 * - Tổng 10 thẻ
 * - Student ~4, Teacher ~3, External ~3
 */
class LibraryCardSampleSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = Faculty::query()->where('code', 'CNTT')->first() ?? Faculty::query()->first();
        $period = Period::query()->orderBy('id')->first();
        $librarian = User::query()->where('email', 'librarian@utc.edu.vn')->first();
        $student = User::query()->where('email', 'student@st.utc.edu.vn')->first();
        $student2 = User::query()->where('email', 'student2@st.utc.edu.vn')->first();
        $student3 = User::query()->where('email', 'student3@st.utc.edu.vn')->first();
        $teacher = User::query()->where('email', 'teacher@st.utc.edu.vn')->first();
        $teacher2 = User::query()->where('email', 'teacher2@st.utc.edu.vn')->first();
        $member = User::query()->where('email', 'member@st.utc.edu.vn')->first();
        $member2 = User::query()->where('email', 'member2@st.utc.edu.vn')->first();

        if ($faculty === null || $period === null) {
            $this->command?->warn('LibraryCardSampleSeeder: bỏ qua (thiếu faculty hoặc period).');

            return;
        }

        $issuerId = $librarian?->id;

        $samples = [
            // Student cards (4)
            [
                'code' => 'CARD-STU-001',
                'user_id' => $student?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
                'full_name' => $student?->name ?? 'Sinh viên demo',
                'email' => 'student@st.utc.edu.vn',
                'phone' => '0912345003',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CNTT-K67',
                'date_of_birth' => '2004-03-15',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(10)->toDateString(),
                'payment' => null,
            ],
            [
                'code' => 'CARD-STU-002',
                'user_id' => $student2?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
                'full_name' => $student2?->name ?? 'Sinh viên demo 2',
                'email' => 'student2@st.utc.edu.vn',
                'phone' => '0912345007',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CNTT-K68',
                'date_of_birth' => '2004-06-20',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(8)->toDateString(),
                'payment' => null,
            ],
            [
                'code' => 'CARD-STU-003',
                'user_id' => $student3?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
                'full_name' => $student3?->name ?? 'Sinh viên demo 3',
                'email' => 'student3@st.utc.edu.vn',
                'phone' => '0912345009',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CTGT-K68',
                'date_of_birth' => '2005-11-01',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(6)->toDateString(),
                'payment' => null,
            ],
            [
                'code' => 'CARD-STU-004',
                'user_id' => null,
                'holder_type' => LibraryCard::HOLDER_TYPE_STUDENT,
                'full_name' => 'Sinh viên đăng ký tại quầy',
                'email' => 'student.local.1@example.com',
                'phone' => '0901000011',
                'address' => 'Thanh Xuân, Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => $period->id,
                'class_code' => 'CNTT-K69',
                'date_of_birth' => '2004-01-10',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
                'status' => LibraryCardStatus::PENDING->value,
                'payment' => null,
            ],

            // Teacher cards (3)
            [
                'code' => 'CARD-TCH-001',
                'user_id' => $teacher?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_TEACHER,
                'full_name' => $teacher?->name ?? 'Giảng viên demo',
                'email' => 'teacher@st.utc.edu.vn',
                'phone' => '0912345004',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1985-07-20',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(15)->toDateString(),
                'payment' => null,
            ],
            [
                'code' => 'CARD-TCH-002',
                'user_id' => $teacher2?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_TEACHER,
                'full_name' => $teacher2?->name ?? 'Giảng viên demo 2',
                'email' => 'teacher2@st.utc.edu.vn',
                'phone' => '0912345006',
                'address' => 'Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1987-04-22',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_PAYMENT,
                'status' => LibraryCardStatus::PENDING->value,
                'payment' => [
                    'payment_status' => LibraryCard::PAYMENT_PENDING,
                    'payment_amount' => 40000,
                    'payment_method' => 'walk_in',
                ],
            ],
            [
                'code' => 'CARD-TCH-003',
                'user_id' => null,
                'holder_type' => LibraryCard::HOLDER_TYPE_TEACHER,
                'full_name' => 'Giảng viên thỉnh giảng',
                'email' => 'teacher.guest@example.com',
                'phone' => '0901000012',
                'address' => 'Cầu Giấy, Hà Nội',
                'faculty_id' => $faculty->id,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1983-02-14',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(4)->toDateString(),
                'payment' => null,
            ],

            // External cards (3)
            [
                'code' => 'CARD-EXT-001',
                'user_id' => $member?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => $member?->name ?? 'Bạn đọc ngoài',
                'email' => 'member@st.utc.edu.vn',
                'phone' => '0912345005',
                'address' => 'Cầu Giấy, Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1990-11-01',
                'external_organization' => 'Doanh nghiệp tự do',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(5)->toDateString(),
                'payment' => [
                    'payment_status' => LibraryCard::PAYMENT_PAID,
                    'payment_amount' => 40000,
                    'paid_at' => now()->subDay(),
                    'payment_method' => 'walk_in',
                ],
            ],
            [
                'code' => 'CARD-EXT-002',
                'user_id' => $member2?->id,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => $member2?->name ?? 'Bạn đọc demo 2',
                'email' => 'member2@st.utc.edu.vn',
                'phone' => '0912345008',
                'address' => 'Đống Đa, Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1992-04-22',
                'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
                'status' => LibraryCardStatus::PENDING->value,
                'payment' => null,
            ],
            [
                'code' => 'CARD-EXT-003',
                'user_id' => null,
                'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
                'full_name' => 'Bạn đọc doanh nghiệp',
                'email' => 'external.reader@example.com',
                'phone' => '0901000013',
                'address' => 'Ba Đình, Hà Nội',
                'faculty_id' => null,
                'period_id' => null,
                'class_code' => null,
                'date_of_birth' => '1995-02-28',
                'external_organization' => 'Công ty CP Cầu đường',
                'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
                'status' => LibraryCardStatus::ACTIVE->value,
                'issue_date' => now()->subDays(3)->toDateString(),
                'payment' => [
                    'payment_status' => LibraryCard::PAYMENT_PAID,
                    'payment_amount' => 40000,
                    'paid_at' => now()->subHours(5),
                    'payment_method' => 'walk_in',
                ],
            ],
        ];

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

        }
    }
}
