<?php

namespace App\Http\Controllers\Api\Loan;

use App\Exports\LoanExport;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Models\LoanRenewalRequest;
use App\Services\LoanRenewalRequestService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeLoanController extends Controller
{
    public function __construct(
        private LoanRenewalRequestService $loanRenewalRequestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'string'],
            'sort' => ['nullable', 'string', 'in:due_asc,due_desc,loan_asc,loan_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $searchColumns = $this->parseSearchInFilter($request);

        $query = $this->baseReaderLoanQuery($user?->id)
            ->when(isset($validated['search']) && trim((string) $validated['search']) !== '', function ($query) use ($validated, $searchColumns) {
                $search = trim((string) $validated['search']);
                $effectiveSearchColumns = ! empty($searchColumns)
                    ? $searchColumns
                    : ['loan_code', 'card_number', 'reader_name', 'created_by_name'];

                $query->where(function ($sub) use ($search, $effectiveSearchColumns) {
                    $applied = false;
                    if (in_array('loan_code', $effectiveSearchColumns, true)) {
                        $sub->where('loan_code', 'like', "%{$search}%");
                        $applied = true;
                    }
                    if (in_array('card_number', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('libraryCard', fn ($q) => $q->where('card_number', 'like', "%{$search}%"));
                        $applied = true;
                    }
                    if (in_array('reader_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('libraryCard', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
                        $applied = true;
                    }
                    if (in_array('created_by_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('createdBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    }
                });
            })
            ->when(isset($validated['status']) && $validated['status'] !== '', function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            });

        $this->applyLoanSortToQuery($query, $validated);

        $items = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success($items->through(function (Loan $loan) use ($user) {
            $latestRequest = $loan->renewalRequests->first();
            $renewalEligibility = $this->loanRenewalRequestService->renewalEligibilityForReaderLoan($loan, $user);

            return array_merge((new LoanResource($loan))->resolve(), [
                'latest_renewal_request' => $latestRequest ? [
                    'id' => $latestRequest->id,
                    'status' => $latestRequest->status,
                    'requested_due_date' => $latestRequest->requested_due_date?->toDateString(),
                    'request_note' => $latestRequest->request_note,
                    'review_note' => $latestRequest->review_note,
                    'created_at' => $latestRequest->created_at?->toIso8601String(),
                ] : null,
                'renewal_eligibility' => $renewalEligibility,
            ]);
        }));
    }

    public function show(Request $request, Loan $loan): JsonResponse
    {
        /** @var \App\Models\User $reader */
        $reader = $request->user();
        $userId = (int) ($reader?->id ?? 0);
        $loan->load([
            'libraryCard:id,card_number,full_name,user_id',
            'createdBy:id,name',
            'items.book:id,title',
            'renewalRequests' => fn ($q) => $q->latest('id'),
        ]);
        if ((int) ($loan->libraryCard?->user_id ?? 0) !== $userId) {
            return ApiResponse::error(__('Không có quyền xem phiếu này.'), 403);
        }

        $renewalEligibility = $this->loanRenewalRequestService->renewalEligibilityForReaderLoan($loan, $reader);

        $resource = array_merge((new LoanResource($loan))->resolve(), [
            'renewal_requests' => $loan->renewalRequests->map(fn (LoanRenewalRequest $r) => [
                'id' => $r->id,
                'status' => $r->status,
                'current_due_date' => $r->current_due_date?->toDateString(),
                'requested_due_date' => $r->requested_due_date?->toDateString(),
                'request_note' => $r->request_note,
                'review_note' => $r->review_note,
                'reviewed_at' => $r->reviewed_at?->toIso8601String(),
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
            'renewal_eligibility' => $renewalEligibility,
        ]);

        return ApiResponse::success($resource);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'string'],
            'sort' => ['nullable', 'string', 'in:due_asc,due_desc,loan_asc,loan_desc'],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer', 'exists:loans,id'],
        ]);

        $query = $this->baseReaderLoanQuery((int) ($request->user()?->id ?? 0));
        $ids = isset($validated['ids']) ? array_values(array_unique(array_map('intval', $validated['ids']))) : [];
        if ($ids !== []) {
            $query->whereIn('id', $ids);

            return LoanExport::stream($query, $ids);
        }

        $searchColumns = $this->parseSearchInFilter($request);
        $query
            ->when(isset($validated['search']) && trim((string) $validated['search']) !== '', function ($query) use ($validated, $searchColumns) {
                $search = trim((string) $validated['search']);
                $effectiveSearchColumns = ! empty($searchColumns)
                    ? $searchColumns
                    : ['loan_code', 'card_number', 'reader_name', 'created_by_name'];
                $query->where(function ($sub) use ($search, $effectiveSearchColumns) {
                    $applied = false;
                    if (in_array('loan_code', $effectiveSearchColumns, true)) {
                        $sub->where('loan_code', 'like', "%{$search}%");
                        $applied = true;
                    }
                    if (in_array('card_number', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('libraryCard', fn ($q) => $q->where('card_number', 'like', "%{$search}%"));
                        $applied = true;
                    }
                    if (in_array('reader_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('libraryCard', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
                        $applied = true;
                    }
                    if (in_array('created_by_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('createdBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    }
                });
            })
            ->when(isset($validated['status']) && $validated['status'] !== '', function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            });

        $this->applyLoanSortToQuery($query, $validated);

        return LoanExport::stream($query);
    }

    public function requestRenewal(Request $request, Loan $loan): JsonResponse
    {
        $validated = $request->validate([
            'request_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $record = $this->loanRenewalRequestService->createForReader(
                $loan,
                $request->user(),
                $validated['request_note'] ?? null
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success([
            'id' => $record->id,
            'status' => $record->status,
            'current_due_date' => $record->current_due_date?->toDateString(),
            'requested_due_date' => $record->requested_due_date?->toDateString(),
            'request_note' => $record->request_note,
        ], __('Đã gửi yêu cầu gia hạn tới quản trị viên.'), 201);
    }

    /**
     * @return Builder<Loan>
     */
    private function baseReaderLoanQuery(int $userId): Builder
    {
        return Loan::query()
            ->with([
                'libraryCard:id,card_number,full_name,user_id',
                'createdBy:id,name',
                'items.book:id,title',
                'renewalRequests' => fn ($q) => $q->latest('id')->limit(1),
            ])
            ->whereHas('libraryCard', fn ($q) => $q->where('user_id', $userId));
    }

    /**
     * @param  Builder<Loan>  $query
     * @param  array<string, mixed>  $validated
     */
    private function applyLoanSortToQuery($query, array $validated): void
    {
        $sort = $validated['sort'] ?? null;
        match ($sort) {
            'due_asc' => $query->orderBy('due_date', 'asc'),
            'due_desc' => $query->orderBy('due_date', 'desc'),
            'loan_asc' => $query->orderBy('loan_date', 'asc'),
            'loan_desc' => $query->orderBy('loan_date', 'desc'),
            default => $query->orderByDesc('id'),
        };
    }

    /**
     * @return list<string>|null
     */
    private function parseSearchInFilter(Request $request): ?array
    {
        if (! $request->filled('search_in')) {
            return null;
        }
        $raw = $request->input('search_in');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $allowed = ['loan_code', 'card_number', 'reader_name', 'created_by_name'];
        $filtered = array_values(array_intersect($candidates, $allowed));

        return $filtered === [] ? null : $filtered;
    }
}
