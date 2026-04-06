<?php

namespace App\Http\Controllers\Api\LibraryCard;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryCardApproveReviewRequest;
use App\Http\Requests\LibraryCardRejectReviewRequest;
use App\Http\Resources\LibraryCardResource;
use App\Models\LibraryCard;
use App\Services\LibraryCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Thao tác nội bộ thủ thư/admin trên hồ sơ thẻ.
 */
class LibraryCardStaffController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService
    ) {}

    public function approveReview(LibraryCardApproveReviewRequest $request, LibraryCard $library_card): JsonResponse
    {
        try {
            $card = $this->libraryCardService->approvePendingReviewAndActivate(
                $library_card,
                $request->user()
            );

            return ApiResponse::success(
                new LibraryCardResource($card->loadMissing([
                    'payment.collector',
                    'period',
                    'faculty',
                    'department',
                    'user',
                ])),
                __('Đã duyệt và kích hoạt thẻ.'),
                200
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }
    }

    public function rejectReview(LibraryCardRejectReviewRequest $request, LibraryCard $library_card): JsonResponse
    {
        try {
            $card = $this->libraryCardService->rejectPendingReview(
                $library_card,
                $request->input('notes'),
                $request->user()
            );

            return ApiResponse::success(
                new LibraryCardResource($card->loadMissing([
                    'payment.collector',
                    'period',
                    'faculty',
                    'department',
                    'user',
                ])),
                __('Đã từ chối hồ sơ.'),
                200
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }
    }
}
