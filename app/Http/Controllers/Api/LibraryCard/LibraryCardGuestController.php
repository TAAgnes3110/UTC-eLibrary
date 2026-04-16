<?php

namespace App\Http\Controllers\Api\LibraryCard;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryCardRequest;
use App\Http\Resources\LibraryCardResource;
use App\Models\LibraryCard;
use App\Services\LibraryCard\LibraryCardService;
use App\Services\Notifications\LibraryCardNotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Đăng ký thẻ không tài khoản — validate {@see LibraryCardRequest}, gọi {@see LibraryCardService}.
 */
class LibraryCardGuestController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService,
        private LibraryCardNotificationDispatcher $libraryCardNotificationDispatcher
    ) {}

    public function store(LibraryCardRequest $request): JsonResponse
    {
        try {
            $card = $this->libraryCardService->create($request->validated());

            if ($card instanceof LibraryCard) {
                $this->libraryCardNotificationDispatcher->notifyStaffOnNewCardApplication($card);
            }

            return ApiResponse::success(
                new LibraryCardResource($card->loadMissing([
                    'payment.collector',
                    'period',
                    'faculty',
                    'department',
                ])),
                __('Đã gửi hồ sơ đăng ký thẻ thư viện.'),
                201
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }
    }
}
