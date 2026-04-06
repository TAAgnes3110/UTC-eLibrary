<?php

namespace App\Services;

use App\Enums\BookPhysicalCondition;
use App\Enums\BookStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Models\BookCopy;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanPolicy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public const LOAN_STATUS_ACTIVE = 'active';

    public const LOAN_STATUS_RETURNED = 'returned';

    /**
     * Tiền phạt quá hạn: (ngày trễ) × overdue_fine_per_day. Ngày trễ = max(0, end − due_date), end = return_date hoặc $asOf.
     */
    public function calculateOverdueFine(Loan $loan, ?Carbon $asOf = null): string
    {
        if (! $loan->relationLoaded('policy')) {
            $loan->load('policy');
        }
        $perDay = $loan->policy ? (string) $loan->policy->overdue_fine_per_day : '0';

        $due = Carbon::parse($loan->due_date)->startOfDay();
        $end = $loan->return_date !== null
            ? Carbon::parse($loan->return_date)->startOfDay()
            : ($asOf ?? Carbon::today())->startOfDay();

        if ($end->lte($due)) {
            return '0.00';
        }

        $days = (int) $due->diffInDays($end);

        return bcmul((string) $days, $perDay, 2);
    }

    /**
     * Cập nhật overdue_days và overdue_fine cho phiếu đang active (chưa trả).
     */
    public function refreshOverdueFields(Loan $loan, ?Carbon $asOf = null): Loan
    {
        if ($loan->return_date !== null || $loan->status !== self::LOAN_STATUS_ACTIVE) {
            return $loan;
        }
        $asOf = ($asOf ?? Carbon::today())->startOfDay();
        $due = Carbon::parse($loan->due_date)->startOfDay();
        $days = $asOf->gt($due) ? (int) $due->diffInDays($asOf) : 0;
        $loan->overdue_days = $days;
        $loan->overdue_fine = $this->calculateOverdueFine($loan, $asOf);
        $loan->save();

        return $loan;
    }

    /**
     * Mượn sách về nhà. Toàn bộ trong transaction.
     *
     * @throws AuthorizationException
     */
    public function createHomeBorrow(User $borrower, BookCopy $copy, User $librarian, ?string $notes = null): Loan
    {
        return DB::transaction(function () use ($borrower, $copy, $librarian, $notes) {
            $this->assertCanBorrowForHome($borrower);
            $this->assertReaderCardForLoan($borrower);
            $policy = $this->resolveLoanPolicyOrFail($borrower);
            if (! $policy->allow_home) {
                throw new AuthorizationException(__('Đối tượng này không được phép mượn sách về nhà theo quy định thư viện.'));
            }
            $this->assertWithinMaxBooks($borrower, $policy);
            $this->assertNoActiveOverdueLoans($borrower);
            $this->assertNoOutstandingFines($borrower);
            $this->assertCopyAvailableForBorrow($copy);

            $loanDate = Carbon::today();
            $dueDate = $loanDate->copy()->addDays(max(1, (int) $policy->max_days));

            $copy->status = BookStatus::BORROWED;
            $copy->save();

            /** @var Loan $loan */
            $loan = Loan::query()->create([
                'user_id' => $borrower->id,
                'book_copy_id' => $copy->id,
                'loan_policy_id' => $policy->id,
                'librarian_id' => $librarian->id,
                'loan_date' => $loanDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'return_date' => null,
                'overdue_days' => 0,
                'overdue_fine' => '0.00',
                'status' => self::LOAN_STATUS_ACTIVE,
                'notes' => $notes,
            ]);

            return $loan->fresh(['policy', 'bookCopy', 'user']);
        });
    }

    /**
     * Trả sách (kết thúc mượn về nhà).
     */
    public function returnHomeLoan(Loan $loan, ?string $conditionOnReturn = null): Loan
    {
        return DB::transaction(function () use ($loan, $conditionOnReturn) {
            if ($loan->status !== self::LOAN_STATUS_ACTIVE || $loan->return_date !== null) {
                throw ValidationException::withMessages([
                    'loan' => [__('Phiếu mượn không ở trạng thái đang mượn.')],
                ]);
            }
            $loan->load('policy');
            $returnDate = Carbon::today();
            $loan->return_date = $returnDate->toDateString();
            $loan->condition_on_return = $conditionOnReturn;

            $due = Carbon::parse($loan->due_date)->startOfDay();
            $ret = $returnDate->copy()->startOfDay();
            $loan->overdue_days = $ret->gt($due) ? (int) $due->diffInDays($ret) : 0;
            $loan->overdue_fine = $this->calculateOverdueFine($loan, $returnDate);

            $loan->status = self::LOAN_STATUS_RETURNED;
            $loan->save();

            $copy = $loan->bookCopy;
            if ($copy) {
                $copy->status = BookStatus::AVAILABLE;
                $copy->save();
            }

            return $loan->fresh(['policy', 'bookCopy', 'user']);
        });
    }

    public function assertCanBorrowForHome(User $user): void
    {
        if ($this->isExternalReader($user)) {
            throw new AuthorizationException(__('Người ngoài / đối tượng không có tài khoản nội bộ không được mượn sách về nhà. Chỉ đọc tại chỗ theo quy định UTC.'));
        }
    }

    /**
     * Thẻ: workflow active, hạn thẻ. {@see LibraryCard::$code} là mã trên hồ sơ thẻ, không bắt buộc trùng {@see User::$code}
     * (bạn đọc ngoài / không tài khoản vẫn có thẻ chỉ với dữ liệu thẻ).
     *
     * @throws ValidationException
     */
    public function assertReaderCardForLoan(User $user): void
    {
        $card = $user->libraryCard;
        if ($card === null) {
            throw ValidationException::withMessages([
                'library_card' => [__('Người mượn chưa có thẻ thư viện hợp lệ.')],
            ]);
        }
        if ($card->workflow_status !== LibraryCard::WORKFLOW_ACTIVE) {
            throw ValidationException::withMessages([
                'library_card' => [__('Thẻ chưa được kích hoạt hoặc không còn hiệu lực (workflow).')],
            ]);
        }
        if ($card->status !== LibraryCardStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'library_card' => [__('Trạng thái thẻ không cho phép mượn (hết hạn, bị khóa hoặc chưa kích hoạt).')],
            ]);
        }
        if ($card->expiry_date !== null && Carbon::parse($card->expiry_date)->lt(Carbon::today())) {
            throw ValidationException::withMessages([
                'library_card' => [__('Thẻ thư viện đã hết hạn.')],
            ]);
        }
    }

    public function resolveLoanPolicyOrFail(User $user): LoanPolicy
    {
        $policyUserType = $this->normalizeLoanPolicyUserType($user);

        $policy = LoanPolicy::query()
            ->where('user_type', $policyUserType)
            ->orderBy('id')
            ->first();

        if ($policy === null) {
            $policy = LoanPolicy::query()
                ->whereNull('user_type')
                ->orderBy('id')
                ->first();
        }

        if ($policy === null) {
            $policy = LoanPolicy::query()->orderBy('id')->first();
        }

        if ($policy === null) {
            throw ValidationException::withMessages([
                'loan_policy' => [__('Chưa cấu hình loan_policies trong hệ thống.')],
            ]);
        }

        return $policy;
    }

    private function normalizeLoanPolicyUserType(User $user): string
    {
        $type = $user->user_type->value;

        // Teacher/Student được coi như nhóm "MEMBER" cho loan policy hiện tại.
        if ($type === RoleType::TEACHER->value || $type === RoleType::STUDENT->value || $type === RoleType::MEMBER->value) {
            return RoleType::MEMBER->value;
        }

        return $type;
    }

    public function countActiveHomeLoans(User $user): int
    {
        return Loan::query()
            ->where('user_id', $user->id)
            ->where('status', self::LOAN_STATUS_ACTIVE)
            ->whereNull('return_date')
            ->count();
    }

    protected function assertWithinMaxBooks(User $user, LoanPolicy $policy): void
    {
        $current = $this->countActiveHomeLoans($user);
        if ($current >= (int) $policy->max_books) {
            throw ValidationException::withMessages([
                'loan' => [__('Đã đạt số đầu sách mượn tối đa cho phép.')],
            ]);
        }
    }

    protected function assertNoActiveOverdueLoans(User $user): void
    {
        $today = Carbon::today()->toDateString();
        $exists = Loan::query()
            ->where('user_id', $user->id)
            ->where('status', self::LOAN_STATUS_ACTIVE)
            ->whereNull('return_date')
            ->whereDate('due_date', '<', $today)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'loan' => [__('Còn tài liệu mượn quá hạn chưa trả. Vui lòng trả hoặc gia hạn trước khi mượn thêm.')],
            ]);
        }
    }

    protected function assertNoOutstandingFines(User $user): void
    {
        $sum = Loan::query()
            ->where('user_id', $user->id)
            ->where('status', self::LOAN_STATUS_ACTIVE)
            ->whereNull('return_date')
            ->sum('overdue_fine');
        if (bccomp((string) $sum, '0', 2) === 1) {
            throw ValidationException::withMessages([
                'loan' => [__('Còn khoản phạt quá hạn chưa xử lý. Vui lòng thanh toán trước khi mượn mới.')],
            ]);
        }
    }

    protected function assertCopyAvailableForBorrow(BookCopy $copy): void
    {
        if ($copy->status !== BookStatus::AVAILABLE) {
            throw ValidationException::withMessages([
                'book_copy' => [__('Bản sao không khả dụng để mượn (trạng thái lưu thông).')],
            ]);
        }
        $physical = $copy->physical_condition instanceof BookPhysicalCondition
            ? $copy->physical_condition
            : BookPhysicalCondition::tryFrom((string) ($copy->getRawOriginal('physical_condition') ?? 'good'));
        if ($physical === null || ! $physical->allowsBorrowing()) {
            throw ValidationException::withMessages([
                'book_copy' => [__('Bản sao không đủ điều kiện mượn theo tình trạng vật lý.')],
            ]);
        }
    }

    protected function isExternalReader(User $user): bool
    {
        if (strtoupper($user->user_type->value) === 'EXTERNAL') {
            return true;
        }
        $card = $user->libraryCard;
        if ($card !== null && $card->holder_type === LibraryCard::HOLDER_TYPE_EXTERNAL) {
            return true;
        }

        return false;
    }
}
