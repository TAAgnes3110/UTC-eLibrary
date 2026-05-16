<?php

namespace App\Http\Controllers\Api\Loan;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReaderBorrowCartPreviewResource;
use App\Models\LoanBorrowRequest;
use App\Services\BookService;
use App\Services\LoanBorrowRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MeLoanBorrowRequestController extends Controller
{
    public function __construct(
        private readonly LoanBorrowRequestService $loanBorrowRequestService,
        private readonly BookService $bookService
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_ids' => ['required', 'array', 'min:1', 'max:100'],
            'book_ids.*' => ['required', 'integer', 'exists:books,id'],
        ]);

        $books = $this->bookService->readerBorrowPreview($validated['book_ids']);

        return ApiResponse::success([
            'items' => ReaderBorrowCartPreviewResource::collection($books)->resolve(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $statuses = implode(',', LoanBorrowRequest::statuses());
        $validated = $request->validate([
            'status' => ['nullable', 'string', "in:{$statuses}"],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = min(max((int) ($validated['per_page'] ?? 20), 1), 100);
        $items = $this->loanBorrowRequestService->readerList($request->user(), $validated, $perPage);

        return ApiResponse::success($items->through(fn (LoanBorrowRequest $r): array => $this->mapBorrowRequest($r)));
    }

    public function store(Request $request): JsonResponse
    {
        $maxRequestedDueDate = Carbon::today()->addYear()->toDateString();
        $loanTypes = implode(',', ['home', 'onsite']);

        $validated = $request->validate([
            'loan_type' => ['required', 'string', "in:{$loanTypes}"],
            'book_ids' => ['required', 'array', 'min:1', 'max:50'],
            'book_ids.*' => ['required', 'integer', 'exists:books,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
            'requested_loan_date' => ['nullable', 'date', 'after_or_equal:today'],
            'requested_due_date' => [
                'required_if:loan_type,home',
                'nullable',
                'date',
                'after_or_equal:today',
                'after_or_equal:requested_loan_date',
                'before_or_equal:'.$maxRequestedDueDate,
            ],
            'request_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $record = $this->loanBorrowRequestService->createForReader($request->user(), $validated);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success($this->mapBorrowRequest($record), __('Đã gửi yêu cầu mượn tới thủ thư.'), 201);
    }

    private function mapBorrowRequest(LoanBorrowRequest $r): array
    {
        return [
            'id' => $r->id,
            'request_code' => $r->request_code,
            'status' => $r->status,
            'loan_type' => $r->loan_type,
            'requested_loan_date' => $r->requested_loan_date?->toDateString(),
            'requested_due_date' => $r->requested_due_date?->toDateString(),
            'request_note' => $r->request_note,
            'review_note' => $r->review_note,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
            'library_card' => [
                'id' => $r->libraryCard?->id,
                'card_number' => $r->libraryCard?->card_number,
                'full_name' => $r->libraryCard?->full_name,
            ],
            'approved_loan' => $r->approvedLoan ? [
                'id' => $r->approvedLoan->id,
                'loan_code' => $r->approvedLoan->loan_code,
                'status' => $r->approvedLoan->status,
                'loan_date' => $r->approvedLoan->loan_date?->toDateString(),
                'due_date' => $r->approvedLoan->due_date?->toDateString(),
            ] : null,
            'items' => $r->items->map(fn ($item): array => [
                'id' => $item->id,
                'book_id' => $item->book_id,
                'book_title' => $item->book?->title,
                'book_code' => $item->book?->book_code,
                'quantity' => $item->quantity,
                'condition_on_loan' => $item->condition_on_loan?->value ?? $item->condition_on_loan,
                'notes' => $item->notes,
            ])->values()->all(),
        ];
    }
}
