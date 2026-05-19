<?php

namespace App\Http\Controllers\Api\Loan;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\LoanBorrowRequest;
use App\Models\LoanBorrowRequestItem;
use App\Services\BookService;
use App\Services\LoanBorrowRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoanBorrowRequestController extends Controller
{
    public function __construct(
        private readonly LoanBorrowRequestService $loanBorrowRequestService,
        private readonly BookService $bookService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected,cancelled'],
            'search' => ['nullable', 'string', 'max:100'],
            'search_in' => ['nullable', 'array'],
            'search_in.*' => ['string', 'in:request_code,card,reader,book'],
            'sort' => ['nullable', 'string', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = min(max((int) ($validated['per_page'] ?? 20), 1), 100);
        $items = $this->loanBorrowRequestService->adminList($validated, $perPage);

        return ApiResponse::success($items->through(fn (LoanBorrowRequest $r): array => $this->mapBorrowRequest($r)));
    }

    public function approve(Request $request, LoanBorrowRequest $borrowRequest): JsonResponse
    {
        $validated = $request->validate([
            'loan_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'loan_type' => ['sometimes', 'string', 'in:home,onsite'],
            'review_note' => ['nullable', 'string', 'max:1000'],
            'book_ids' => ['sometimes', 'array', 'min:1'],
            'book_ids.*' => ['integer', 'exists:books,id'],
            'quantity' => ['sometimes', 'array'],
            'quantity.*' => ['integer', 'min:1'],
            'condition_on_loan' => ['nullable', 'array'],
            'condition_on_loan.*' => ['nullable', 'string', 'in:tot,hong,mat'],
        ]);

        try {
            $record = $this->loanBorrowRequestService->approve($borrowRequest, $request->user(), $validated);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($this->mapBorrowRequest($record), __('Đã duyệt yêu cầu mượn và tạo phiếu mượn.'));
    }

    public function reject(Request $request, LoanBorrowRequest $borrowRequest): JsonResponse
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $record = $this->loanBorrowRequestService->reject(
                $borrowRequest,
                $request->user(),
                $validated['review_note'] ?? null
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($this->mapBorrowRequest($record), __('Đã từ chối yêu cầu mượn.'));
    }

    public function bulkReject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:loan_borrow_requests,id'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $count = $this->loanBorrowRequestService->bulkReject(
                $request->user(),
                $validated['ids'],
                $validated['review_note'] ?? null
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        if ($count === 0) {
            return ApiResponse::error(__('Không có yêu cầu nào ở trạng thái chờ duyệt trong danh sách đã gửi.'), 422);
        }

        return ApiResponse::success(
            ['rejected_count' => $count],
            __('Đã từ chối :count yêu cầu mượn.', ['count' => $count])
        );
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
            'suggested_due_date' => $this->loanBorrowRequestService->suggestedDueDateForRequest($r),
            'request_note' => $r->request_note,
            'review_note' => $r->review_note,
            'reviewed_at' => $r->reviewed_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
            'library_card' => [
                'id' => $r->libraryCard?->id,
                'card_number' => $r->libraryCard?->card_number,
                'full_name' => $r->libraryCard?->full_name,
                'holder_type' => $r->libraryCard?->holder_type,
            ],
            'requester' => [
                'id' => $r->requester?->id,
                'name' => $r->requester?->name,
                'code' => $r->requester?->code,
                'email' => $r->requester?->email,
            ],
            'reviewer' => $r->reviewer ? [
                'id' => $r->reviewer->id,
                'name' => $r->reviewer->name,
            ] : null,
            'approved_loan' => $r->approvedLoan ? [
                'id' => $r->approvedLoan->id,
                'loan_code' => $r->approvedLoan->loan_code,
                'status' => $r->approvedLoan->status,
            ] : null,
            'items' => $r->items->map(fn (LoanBorrowRequestItem $item): array => $this->mapBorrowRequestItem($item))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBorrowRequestItem(LoanBorrowRequestItem $item): array
    {
        $book = $item->book;
        $stats = $book ? $this->bookService->readerCopyStats($book) : ['available' => 0];

        return [
            'id' => $item->id,
            'book_id' => $item->book_id,
            'book_title' => $book?->title,
            'book_code' => $book?->book_code,
            'resource_type' => $book?->resource_type?->value ?? $book?->resource_type,
            'warehouse_name' => $book?->warehouse?->name,
            'warehouse_code' => $book?->warehouse?->code,
            'cabinet' => $book?->cabinet,
            'book_total_quantity' => $book?->quantity,
            'available_for_borrow' => (int) ($stats['available'] ?? 0),
            'quantity' => $item->quantity,
            'condition_on_loan' => $item->condition_on_loan?->value ?? $item->condition_on_loan,
            'notes' => $item->notes,
        ];
    }
}
