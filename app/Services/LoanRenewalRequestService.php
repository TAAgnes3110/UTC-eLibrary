<?php

namespace App\Services;

use App\Enums\LoanStatus;

use App\Models\Loan;
use App\Models\LoanRenewalRequest;
use App\Models\User;
use App\Services\Notifications\LoanRenewalNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanRenewalRequestService
{
    public function __construct(
        private readonly LoanPoliciesService $loanPoliciesService,
        private readonly LoanRenewalNotificationService $loanRenewalNotificationService
    ) {}

    /**
     * @return array{
     *     eligible:bool,
     *     reason?:string,
     *     message?:string,
     *     max_renewals?:int,
     *     used_renewals?:int,
     *     remaining_renewals?:int,
     *     extension_days?:int,
     *     proposed_due_date?:string|null
     * }
     */
    public function renewalEligibilityForReaderLoan(Loan $loan, User $user): array
    {
        $loan->loadMissing('libraryCard:id,user_id,holder_type');

        if ((int) ($loan->libraryCard?->user_id ?? 0) !== (int) $user->id) {
            return [
                'eligible' => false,
                'reason' => 'not_owner',
                'message' => __('Bạn không có quyền thao tác trên phiếu này.'),
            ];
        }

        if ($loan->return_date !== null) {
            return [
                'eligible' => false,
                'reason' => 'returned',
                'message' => __('Phiếu đã trả, không thể gia hạn.'),
            ];
        }

        if (! in_array($loan->status, [LoanStatus::BORROWED, LoanStatus::OVERDUE], true)) {
            return [
                'eligible' => false,
                'reason' => 'invalid_status',
                'message' => __('Chỉ gia hạn cho phiếu đang mượn hoặc quá hạn.'),
            ];
        }

        $hasPending = LoanRenewalRequest::query()
            ->where('loan_id', $loan->id)
            ->where('status', LoanRenewalRequest::STATUS_PENDING)
            ->exists();
        if ($hasPending) {
            return [
                'eligible' => false,
                'reason' => 'pending_request',
                'message' => __('Phiếu này đã có yêu cầu gia hạn đang chờ xử lý.'),
            ];
        }

        $card = $loan->libraryCard;
        if ($card === null) {
            return [
                'eligible' => false,
                'reason' => 'no_card',
                'message' => __('Không xác định được thẻ mượn.'),
            ];
        }

        $limits = $this->loanPoliciesService->getRenewalLimitsForCard($card);
        $approvedCount = LoanRenewalRequest::query()
            ->where('loan_id', $loan->id)
            ->where('status', LoanRenewalRequest::STATUS_APPROVED)
            ->count();

        if ($limits['max_days'] <= 0) {
            return [
                'eligible' => false,
                'reason' => 'policy_no_extension',
                'message' => __('Chính sách mượn theo loại thẻ của bạn không cho phép gia hạn.'),
                'max_renewals' => $limits['max_renewals'],
                'used_renewals' => $approvedCount,
                'remaining_renewals' => max(0, $limits['max_renewals'] - $approvedCount),
                'extension_days' => $limits['max_days'],
            ];
        }

        if ($limits['max_renewals'] <= 0 || $approvedCount >= $limits['max_renewals']) {
            return [
                'eligible' => false,
                'reason' => 'renewals_exhausted',
                'message' => __('Bạn đã dùng hết số lần gia hạn theo chính sách thẻ.'),
                'max_renewals' => $limits['max_renewals'],
                'used_renewals' => $approvedCount,
                'remaining_renewals' => max(0, $limits['max_renewals'] - $approvedCount),
                'extension_days' => $limits['max_days'],
            ];
        }

        $dueDate = $loan->due_date ? Carbon::parse($loan->due_date)->startOfDay() : now()->startOfDay();
        $proposed = $dueDate->copy()->addDays($limits['max_days']);

        return [
            'eligible' => true,
            'max_renewals' => $limits['max_renewals'],
            'used_renewals' => $approvedCount,
            'remaining_renewals' => $limits['max_renewals'] - $approvedCount,
            'extension_days' => $limits['max_days'],
            'proposed_due_date' => $proposed->toDateString(),
        ];
    }

    public function createForReader(Loan $loan, User $user, ?string $requestNote = null): LoanRenewalRequest
    {
        $check = $this->renewalEligibilityForReaderLoan($loan, $user);
        if (($check['eligible'] ?? false) !== true) {
            throw ValidationException::withMessages([
                'loan' => [$check['message'] ?? __('Không thể gửi yêu cầu gia hạn.')],
            ]);
        }

        return DB::transaction(function () use ($loan, $user, $requestNote, $check): LoanRenewalRequest {
            $lockedLoan = Loan::query()
                ->with('libraryCard:id,user_id,holder_type')
                ->whereKey($loan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $recheck = $this->renewalEligibilityForReaderLoan($lockedLoan, $user);
            if (($recheck['eligible'] ?? false) !== true) {
                throw ValidationException::withMessages([
                    'loan' => [$recheck['message'] ?? __('Không thể gửi yêu cầu gia hạn.')],
                ]);
            }

            $dueDate = $lockedLoan->due_date ? Carbon::parse($lockedLoan->due_date)->startOfDay() : now()->startOfDay();
            $proposed = (string) ($recheck['proposed_due_date'] ?? $dueDate->copy()->addDays((int) ($recheck['extension_days'] ?? 0))->toDateString());

            $created = LoanRenewalRequest::query()->create([
                'loan_id' => $lockedLoan->id,
                'requested_by' => $user->id,
                'current_due_date' => $dueDate->toDateString(),
                'requested_due_date' => $proposed,
                'status' => LoanRenewalRequest::STATUS_PENDING,
                'request_note' => $requestNote !== null && trim($requestNote) !== '' ? trim($requestNote) : null,
            ]);

            $this->loanRenewalNotificationService->notifyStaffRenewalSubmitted($created->fresh(['loan', 'requester']));

            return $created;
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
            $searchIn = array_values(array_filter((array) ($filters['search_in'] ?? []), static fn ($v): bool => is_string($v) && $v !== ''));
            if ($searchIn === []) {
                $searchIn = ['loan_code', 'card', 'reader'];
            }
            $query->where(function ($q) use ($kw, $searchIn): void {
                $applied = false;
                if (in_array('loan_code', $searchIn, true)) {
                    $q->whereHas('loan', fn ($x) => $x->where('loan_code', 'like', "%{$kw}%"));
                    $applied = true;
                }
                if (in_array('card', $searchIn, true)) {
                    $method = $applied ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('loan.libraryCard', fn ($x) => $x->where('card_number', 'like', "%{$kw}%")->orWhere('full_name', 'like', "%{$kw}%"));
                    $applied = true;
                }
                if (in_array('reader', $searchIn, true)) {
                    $method = $applied ? 'orWhereHas' : 'whereHas';
                    $q->{$method}('requester', fn ($x) => $x->where('name', 'like', "%{$kw}%")->orWhere('code', 'like', "%{$kw}%"));
                }
            });
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function approve(LoanRenewalRequest $request, User $reviewer, ?string $reviewNote = null): LoanRenewalRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reviewNote): LoanRenewalRequest {
            $locked = LoanRenewalRequest::query()
                ->with(['loan.libraryCard'])
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
                $loan->status = now()->toDateString() > Carbon::parse($loan->due_date)->toDateString()
                    ? LoanStatus::OVERDUE
                    : LoanStatus::BORROWED;
            }
            $loan->save();

            $locked->status = LoanRenewalRequest::STATUS_APPROVED;
            $locked->reviewed_by = $reviewer->id;
            $locked->reviewed_at = now();
            $locked->review_note = $reviewNote;
            $locked->save();

            $fresh = $locked->fresh(['loan.libraryCard', 'requester', 'reviewer']);
            $this->loanRenewalNotificationService->notifyRequesterRenewalResult($fresh, approved: true);

            return $fresh;
        });
    }

    public function reject(LoanRenewalRequest $request, User $reviewer, ?string $reviewNote = null): LoanRenewalRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $reviewNote): LoanRenewalRequest {
            $locked = LoanRenewalRequest::query()
                ->with(['loan.libraryCard', 'requester'])
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

            $fresh = $locked->fresh(['loan.libraryCard', 'requester', 'reviewer']);
            $this->loanRenewalNotificationService->notifyRequesterRenewalResult($fresh, approved: false);

            return $fresh;
        });
    }

}
