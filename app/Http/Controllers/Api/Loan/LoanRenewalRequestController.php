<?php

namespace App\Http\Controllers\Api\Loan;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\LoanRenewalRequest;
use App\Services\LoanRenewalRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoanRenewalRequestController extends Controller
{
    public function __construct(
        private LoanRenewalRequestService $loanRenewalRequestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = min(max((int) ($validated['per_page'] ?? 20), 1), 100);

        $items = $this->loanRenewalRequestService->adminList($validated, $perPage);

        return ApiResponse::success($items->through(function (LoanRenewalRequest $r) {
            return [
                'id' => $r->id,
                'status' => $r->status,
                'current_due_date' => $r->current_due_date?->toDateString(),
                'requested_due_date' => $r->requested_due_date?->toDateString(),
                'request_note' => $r->request_note,
                'review_note' => $r->review_note,
                'reviewed_at' => $r->reviewed_at?->toIso8601String(),
                'created_at' => $r->created_at?->toIso8601String(),
                'loan' => [
                    'id' => $r->loan?->id,
                    'loan_code' => $r->loan?->loan_code,
                    'status' => $r->loan?->status,
                    'loan_date' => $r->loan?->loan_date?->toDateString(),
                    'due_date' => $r->loan?->due_date?->toDateString(),
                    'library_card_number' => $r->loan?->libraryCard?->card_number,
                    'library_card_name' => $r->loan?->libraryCard?->full_name,
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
            ];
        }));
    }

    public function approve(Request $request, LoanRenewalRequest $renewalRequest): JsonResponse
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $record = $this->loanRenewalRequestService->approve(
                $renewalRequest,
                $request->user(),
                $validated['review_note'] ?? null
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success([
            'id' => $record->id,
            'status' => $record->status,
            'review_note' => $record->review_note,
        ], __('Đã duyệt yêu cầu gia hạn.'));
    }

    public function reject(Request $request, LoanRenewalRequest $renewalRequest): JsonResponse
    {
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $record = $this->loanRenewalRequestService->reject(
                $renewalRequest,
                $request->user(),
                $validated['review_note'] ?? null
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success([
            'id' => $record->id,
            'status' => $record->status,
            'review_note' => $record->review_note,
        ], __('Đã từ chối yêu cầu gia hạn.'));
    }
}
