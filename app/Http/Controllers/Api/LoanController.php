<?php

namespace App\Http\Controllers\Api;

use App\Enums\LoanItemCondition;
use App\Exports\LoanExport;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\LoanService;
use App\Services\StatisticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loanService,
        private StatisticsService $statisticsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'library_card_id' => ['nullable', 'integer', 'exists:library_cards,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'string'],
            'sort' => ['nullable', 'string', 'in:newest,oldest,due_asc,due_desc,loan_asc,loan_desc'],
            'sort_due_date' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $searchColumns = $this->parseSearchInFilter($request);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = Loan::query()
            ->with(['libraryCard:id,card_number,full_name', 'createdBy:id,name'])
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
                        $sub->{$method}('libraryCard', function ($q) use ($search) {
                            $q->where('card_number', 'like', "%{$search}%");
                        });
                        $applied = true;
                    }
                    if (in_array('reader_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('libraryCard', function ($q) use ($search) {
                            $q->where('full_name', 'like', "%{$search}%");
                        });
                        $applied = true;
                    }
                    if (in_array('created_by_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('createdBy', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                });
            })
            ->when(isset($validated['status']) && $validated['status'] !== '', function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->when(isset($validated['library_card_id']), function ($query) use ($validated) {
                $query->where('library_card_id', (int) $validated['library_card_id']);
            });

        $this->applyLoanSortToQuery($query, $validated);

        $items = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success(LoanResource::collection($items));
    }

    /**
     * Dashboard thống kê phiếu mượn/trả theo chu kỳ ngày/tháng/năm.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'granularity' => ['nullable', Rule::in(['day', 'month', 'year'])],
            'digital_granularity' => ['nullable', Rule::in(['day', 'month', 'year'])],
            'parts' => ['nullable', 'string', 'max:120'],
        ]);

        $loanGranularity = (string) ($validated['granularity'] ?? 'month');
        $digitalGranularity = isset($validated['digital_granularity'])
            ? (string) $validated['digital_granularity']
            : null;

        $partsRaw = trim((string) ($validated['parts'] ?? ''));
        if ($partsRaw !== '') {
            $parts = array_values(array_filter(array_map('trim', explode(',', $partsRaw))));
            $allowed = ['summary', 'series', 'digital_series', 'forecast'];
            $parts = array_values(array_intersect($parts, $allowed));
            $payload = $this->statisticsService->dashboardStatisticsParts(
                $loanGranularity,
                $digitalGranularity,
                $parts !== [] ? $parts : $allowed,
            );
        } else {
            $payload = $this->statisticsService->dashboardStatistics($loanGranularity, $digitalGranularity);
        }

        return ApiResponse::success($payload);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'library_card_id' => ['nullable', 'integer', 'exists:library_cards,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'string'],
            'sort' => ['nullable', 'string', 'in:newest,oldest,due_asc,due_desc,loan_asc,loan_desc'],
            'sort_due_date' => ['nullable', 'in:asc,desc'],
            'ids' => ['nullable', 'array', 'max:500'],
            'ids.*' => ['integer', 'exists:loans,id'],
        ]);
        $ids = isset($validated['ids']) ? array_values(array_unique(array_map('intval', $validated['ids']))) : [];
        if ($ids !== []) {
            $query = Loan::query()->whereIn('id', $ids);

            return LoanExport::stream($query, $ids);
        }

        $searchColumns = $this->parseSearchInFilter($request);

        $query = Loan::query()
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
                        $sub->{$method}('libraryCard', function ($q) use ($search) {
                            $q->where('card_number', 'like', "%{$search}%");
                        });
                        $applied = true;
                    }
                    if (in_array('reader_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('libraryCard', function ($q) use ($search) {
                            $q->where('full_name', 'like', "%{$search}%");
                        });
                        $applied = true;
                    }
                    if (in_array('created_by_name', $effectiveSearchColumns, true)) {
                        $method = $applied ? 'orWhereHas' : 'whereHas';
                        $sub->{$method}('createdBy', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }
                });
            })
            ->when(isset($validated['status']) && $validated['status'] !== '', function ($query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->when(isset($validated['library_card_id']), function ($query) use ($validated) {
                $query->where('library_card_id', (int) $validated['library_card_id']);
            });

        $this->applyLoanSortToQuery($query, $validated);

        return LoanExport::stream($query);
    }

    /**
     * @param  Builder<Loan>  $query
     * @param  array<string, mixed>  $validated
     */
    private function applyLoanSortToQuery($query, array $validated): void
    {
        $sort = $validated['sort'] ?? null;
        if (($sort === null || $sort === '') && ! empty($validated['sort_due_date'] ?? null)) {
            $sort = $validated['sort_due_date'] === 'desc' ? 'due_desc' : 'due_asc';
        }

        match ($sort) {
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'due_asc' => $query->orderBy('due_date', 'asc'),
            'due_desc' => $query->orderBy('due_date', 'desc'),
            'loan_asc' => $query->orderBy('loan_date', 'asc'),
            'loan_desc' => $query->orderBy('loan_date', 'desc'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
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

    public function show(Loan $loan): JsonResponse
    {
        $loan->load(['libraryCard:id,card_number,full_name,holder_type', 'createdBy:id,name', 'items.book:id,title,price']);

        return ApiResponse::success(new LoanResource($loan));
    }

    public function store(LoanRequest $request): JsonResponse
    {
        try {
            $loan = $this->loanService->create($request->validated());
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
        $loan->load(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title']);

        return ApiResponse::success(new LoanResource($loan), __('messages.success_create'), 201);
    }

    public function update(LoanRequest $request, Loan $loan): JsonResponse
    {
        $loan = $this->loanService->update($request->validated(), $loan);
        $loan->load(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title']);

        return ApiResponse::success(new LoanResource($loan), __('messages.success_update'));
    }

    public function destroy(Loan $loan): JsonResponse
    {
        try {
            $this->loanService->destroy($loan);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function return(Request $request, Loan $loan): JsonResponse
    {
        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'condition_on_return' => ['nullable', 'string'],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
            'returns' => ['nullable', 'array'],
            'returns.*.condition_on_return' => ['nullable', 'string'],
            'returns.*.damage_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'returns.*.fine_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $loan = $this->loanService->processReturnBook($validated, $loan);
        $loan->load(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title']);

        return ApiResponse::success(new LoanResource($loan), 'Trả sách thành công.');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:loans,id'],
        ]);

        try {
            $this->loanService->bulkDestroy($validated['ids']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function bulkReturn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_ids' => ['required', 'array', 'min:1', 'max:50'],
            'loan_ids.*' => ['integer', 'exists:loans,id'],
            'return_date' => ['required', 'date'],
            'condition_on_return' => ['nullable', 'string', Rule::in(LoanItemCondition::values())],
        ]);

        try {
            $this->loanService->bulkProcessReturnBooks(
                $validated['loan_ids'],
                $validated['return_date'],
                $validated['condition_on_return'] ?? LoanItemCondition::GOOD->value
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(null, 'Đã trả sách cho các phiếu đã chọn.');
    }
}
