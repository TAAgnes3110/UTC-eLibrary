<?php

namespace App\Http\Controllers\Api;

use App\Exports\LoanExport;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanRequest;
use App\Http\Resources\LoanResource;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loanService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'library_card_id' => ['nullable', 'integer', 'exists:library_cards,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'string'],
            'sort_due_date' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $searchColumns = $this->parseSearchInFilter($request);

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = Loan::query()
            ->with(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title'])
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
            })
            ->when(isset($validated['sort_due_date']), function ($query) use ($validated) {
                $query->orderBy('due_date', $validated['sort_due_date']);
            }, function ($query) {
                $query->orderByDesc('id');
            });

        $items = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success(LoanResource::collection($items));
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'library_card_id' => ['nullable', 'integer', 'exists:library_cards,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'string'],
            'sort_due_date' => ['nullable', 'in:asc,desc'],
        ]);
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
            })
            ->when(isset($validated['sort_due_date']), function ($query) use ($validated) {
                $query->orderBy('due_date', $validated['sort_due_date']);
            }, function ($query) {
                $query->orderByDesc('id');
            });

        return LoanExport::stream($query);
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
        $loan->load(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title']);

        return ApiResponse::success(new LoanResource($loan));
    }

    public function store(LoanRequest $request): JsonResponse
    {
        $loan = $this->loanService->create($request->validated());
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
        $this->loanService->destroy($loan);

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
            'returns.*.fine_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $loan = $this->loanService->processReturnBook($validated, $loan);
        $loan->load(['libraryCard:id,card_number,full_name', 'createdBy:id,name', 'items.book:id,title']);

        return ApiResponse::success(new LoanResource($loan), 'Trả sách thành công.');
    }
}
