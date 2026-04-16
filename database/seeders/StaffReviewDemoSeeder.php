<?php

namespace Database\Seeders;

use App\Enums\LoanItemCondition;
use App\Models\Book;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\LoanRenewalRequest;
use App\Models\Period;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;
use App\Services\LoanPoliciesService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StaffReviewDemoSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::query()->where('email', 'student@st.utc.edu.vn')->first();
        $librarian = User::query()->where('email', 'librarian@utc.edu.vn')->first();
        $faculty = Faculty::query()->where('code', 'CNTT')->first() ?? Faculty::query()->orderBy('id')->first();
        $period = Period::query()->orderBy('id')->first();

        if ($student === null || ! $faculty instanceof Faculty || ! $period instanceof Period) {
            $this->command?->warn('StaffReviewDemoSeeder: bỏ qua (thiếu user sinh viên / faculty / period).');

            return;
        }

        if (Book::query()->where('quantity', '>', 0)->doesntExist()) {
            $this->call(BookSampleSeeder::class);
        }

        if (LibraryCard::query()
            ->where('user_id', $student->id)
            ->where('workflow_status', LibraryCard::WORKFLOW_ACTIVE)
            ->doesntExist()) {
            $this->call(LibraryCardSampleSeeder::class);
        }

        $card = LibraryCard::query()
            ->where('user_id', $student->id)
            ->where('workflow_status', LibraryCard::WORKFLOW_ACTIVE)
            ->first();

        if ($card === null) {
            $this->command?->warn('StaffReviewDemoSeeder: bỏ qua (không tạo được thẻ active cho sinh viên).');

            return;
        }

        $staffId = $librarian?->id;

        $this->seedPendingProfileUpdate($student, $faculty, $period);
        $this->seedPendingRenewal($student, $card, $staffId);
    }

    private function seedPendingProfileUpdate(User $student, Faculty $faculty, Period $period): void
    {
        $markerClass = 'CNTT-K70-DEMO-SEED';
        $requestedCode = '112200000099';
        while (User::query()->where('code', $requestedCode)->where('id', '!=', $student->id)->exists()) {
            $requestedCode = (string) ((int) $requestedCode - 1);
        }

        UserProfileUpdateRequest::query()
            ->where('user_id', $student->id)
            ->where('requested_class_code', $markerClass)
            ->delete();

        UserProfileUpdateRequest::query()->create([
            'user_id' => $student->id,
            'requested_code' => $requestedCode,
            'requested_faculty_id' => $faculty->id,
            'requested_period_id' => $period->id,
            'requested_class_code' => $markerClass,
            'proof_image_path' => 'seed/demo/minh-chung-cap-nhat-ho-so.png',
            'status' => UserProfileUpdateRequest::STATUS_PENDING,
            'reason' => 'Đổi lớp hành chính (dữ liệu mẫu cho màn duyệt cấp thông tin).',
            'review_note' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'applied_at' => null,
            'created_by' => $student->id,
            'updated_by' => $student->id,
        ]);
    }

    private function seedPendingRenewal(User $student, LibraryCard $card, ?int $staffId): void
    {
        $book = Book::query()->where('quantity', '>', 0)->orderBy('id')->first();
        if ($book === null) {
            $this->command?->warn('StaffReviewDemoSeeder: không tạo gia hạn (thiếu sách mẫu — chạy BookSampleSeeder).');

            return;
        }

        $loanDate = Carbon::today()->subDays(7);
        $dueDate = Carbon::today()->addDays(3);

        $loan = Loan::query()->updateOrCreate(
            ['loan_code' => 'LDM0000SEEDRENEW'],
            [
                'library_card_id' => $card->id,
                'loan_type' => Loan::TYPE_HOME,
                'loan_date' => $loanDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'return_date' => null,
                'status' => Loan::STATUS_BORROWED,
                'created_by' => $staffId,
                'updated_by' => $staffId,
            ]
        );

        LoanItem::query()->where('loan_id', $loan->id)->delete();
        LoanItem::query()->create([
            'loan_id' => $loan->id,
            'book_id' => $book->id,
            'quantity' => 1,
            'condition_on_loan' => LoanItemCondition::GOOD,
            'condition_on_return' => null,
            'fine_amount' => 0,
            'notes' => 'Phiếu mẫu cho duyệt gia hạn (seed).',
        ]);

        LoanRenewalRequest::query()->where('loan_id', $loan->id)->delete();

        $limits = app(LoanPoliciesService::class)->getRenewalLimitsForCard($card);
        $extensionDays = max(1, (int) $limits['max_days']);
        $requestedDue = $dueDate->copy()->addDays($extensionDays);

        LoanRenewalRequest::query()->create([
            'loan_id' => $loan->id,
            'requested_by' => $student->id,
            'current_due_date' => $dueDate->toDateString(),
            'requested_due_date' => $requestedDue->toDateString(),
            'status' => LoanRenewalRequest::STATUS_PENDING,
            'request_note' => 'Xin gia hạn thêm vì chưa học xong (dữ liệu mẫu cho màn duyệt gia hạn).',
        ]);
    }
}
