<?php

namespace App\Http\Controllers\Api\LibraryCard;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryCardApproveReviewRequest;
use App\Http\Requests\LibraryCardRejectReviewRequest;
use App\Http\Resources\LibraryCardResource;
use App\Models\LibraryCard;
use App\Services\LibraryCard\LibraryCardService;
use App\Services\Notifications\LibraryCardNotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Thao tác nội bộ thủ thư/admin trên hồ sơ thẻ.
 */
class LibraryCardStaffController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService,
        private LibraryCardNotificationDispatcher $libraryCardNotificationDispatcher
    ) {}

    public function approveReview(LibraryCardApproveReviewRequest $request, LibraryCard $library_card): JsonResponse
    {
        try {
            $card = $this->libraryCardService->approvePendingReviewAndActivate(
                $library_card,
                $request->user()
            );

            $ws = (string) $card->workflow_status;
            if ($ws === LibraryCard::WORKFLOW_PENDING_PICKUP) {
                $this->libraryCardNotificationDispatcher->notifyReaderCardApproved($card);
            }

            $message = $ws === LibraryCard::WORKFLOW_PENDING_PAYMENT
                ? __('Đã duyệt hồ sơ. Bạn đọc cần thanh toán lệ phí trước khi nhận thẻ.')
                : __('Đã duyệt hồ sơ. Bạn đọc chờ lấy thẻ tại quầy.');

            return ApiResponse::success(
                new LibraryCardResource($card->loadMissing([
                    'payment.collector',
                    'period',
                    'faculty',
                    'department',
                    'user',
                ])),
                $message,
                200
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }
    }

    public function confirmPickup(LibraryCardApproveReviewRequest $request, LibraryCard $library_card): JsonResponse
    {
        try {
            $card = $this->libraryCardService->confirmPickupAndActivate(
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
                __('Đã xác nhận giao thẻ — thẻ đang hiệu lực.'),
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

            $this->libraryCardNotificationDispatcher->notifyReaderCardRejected(
                $card,
                $request->input('notes') ? (string) $request->input('notes') : null
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
