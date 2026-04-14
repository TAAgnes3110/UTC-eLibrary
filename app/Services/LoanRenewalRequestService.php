<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanRenewalRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanRenewalRequestService
{
    public function createForReader(Loan $loan, User $user, ?string $requestNote = null): LoanRenewalRequest
    {
        return DB::transaction(function () use ($loan, $user, $requestNote) {
            $lockedLoan = Loan::query()
                ->with('libraryCard:id,user_id')
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) ($lockedLoan->libraryCard?->user_id ?? 0) !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'loan' => [__('Bạn không có quyền gửi yêu cầu cho phiếu này.')],
                ]);
            }

            if (! in_array((string) $lockedLoan->status, [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE], true)) {
                throw ValidationException::withMessages([
                    'loan' => [__('Chỉ gửi gia hạn cho phiếu đang mượn hoặc quá hạn.')],
                ]);
            }

            $hasPending = LoanRenewalRequest::query()
                ->where('loan_id', $lockedLoan->id)
                ->where('status', LoanRenewalRequest::STATUS_PENDING)
                ->exists();
            if ($hasPending) {
                throw ValidationException::withMessages([
                    'loan' => [__('Phiếu này đã có yêu cầu gia hạn đang chờ xử lý.')],
                ]);
            }

            $dueDate = $lockedLoan->due_date ? Carbon::parse($lockedLoan->due_date) : now();
            $desiredDate = $dueDate->copy()->addDays(7);

            return LoanRenewalRequest::query()->create([
                'loan_id' => $lockedLoan->id,
                'requested_by' => $user->id,
                'current_due_date' => $dueDate->toDateString(),
                'requested_due_date' => $desiredDate->toDateString(),
                'status' => LoanRenewalRequest::STATUS_PENDING,
                'request_note' => $requestNote !== null && trim($requestNote) !== '' ? trim($requestNote) : null,
            ]);
        });
    }

    public function adminList(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = LoanRenewalRequest::query()
            ->with([
                'loan:id,loan_code,status,loan_date,due_date,library_card_id',
                'loan.libraryCard:id,card_number,full_name',
                'requester:id,name,code,email',
                'reviewer:id,name',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }
        if (! empty($filters['search'])) {
            $kw = trim((string) $filters['search']);
            $query->where(function ($q) use ($kw) {
                $q->whereHas('loan', fn ($x) => $x->where('loan_code', 'like', "%{$kw}%"))
                    ->orWhereHas('loan.libraryCard', fn ($x) => $x->where('card_number', 'like', "%{$kw}%")->orWhere('full_name', 'like', "%{$kw}%"))
                    ->orWhereHas('requester', fn ($x) => $x->where('name', 'like', "%{$kw}%")->orWhere('code', 'like', "%{$kw}%"));
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function approve(LoanRenewalRequest $request, User $reviewer, ?string $reviewNote = null): LoanRenewalRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reviewNote) {
            $locked = LoanRenewalRequest::query()
                ->with('loan')
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== LoanRenewalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => [__('Yêu cầu này đã được xử lý trước đó.')],
                ]);
            }

            $loan = Loan::query()->whereKey($locked->loan_id)->lockForUpdate()->firstOrFail();
            $newDue = $locked->requested_due_date ?: $loan->due_date;
            $loan->due_date = $newDue;
            if ($loan->return_date === null) {
                $loan->status = now()->toDateString() > $newDue->toDateString()
                    ? Loan::STATUS_OVERDUE
                    : Loan::STATUS_BORROWED;
            }
            $loan->save();

            $locked->status = LoanRenewalRequest::STATUS_APPROVED;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = $reviewNote;
            $locked->save();

            return $locked->fresh(['loan.libraryCard', 'requester', 'reviewer']);
        });
    }

    public function reject(LoanRenewalRequest $request, User $reviewer, ?string $reviewNote = null): LoanRenewalRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reviewNote) {
            $locked = LoanRenewalRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== LoanRenewalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => [__('Yêu cầu này đã được xử lý trước đó.')],
                ]);
            }

            $locked->status = LoanRenewalRequest::STATUS_REJECTED;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = $reviewNote;
            $locked->save();

            return $locked->fresh(['loan.libraryCard', 'requester', 'reviewer']);
        });
    }
}
