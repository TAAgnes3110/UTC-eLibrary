<?php

namespace App\Http\Controllers\Api\LibraryCard;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MeLibraryCardStoreRequest;
use App\Http\Resources\LibraryCardResource;
use App\Models\LibraryCard;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardService;
use App\Services\Notifications\LibraryCardNotificationDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Xin cấp thẻ khi đã đăng nhập — validate {@see MeLibraryCardStoreRequest}, gọi {@see LibraryCardService}.
 */
class MeLibraryCardController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService,
        private LibraryCardNotificationDispatcher $libraryCardNotificationDispatcher
    ) {}

    public function store(MeLibraryCardStoreRequest $request): JsonResponse
    {
        try {
            $photoFile = $request->file('photo_file');
            $card = $this->libraryCardService->createForUserHaveAccount(
                $request->user(),
                $request->validated(),
                $photoFile
            );

            if ($card instanceof LibraryCard) {
                $this->libraryCardNotificationDispatcher->notifyStaffOnNewCardApplication($card);
            }

            return ApiResponse::success(
                new LibraryCardResource($card->loadMissing([
                    'payment.collector',
                    'period',
                    'faculty',
                    'department',
                    'user',
                ])),
                __('Đã gửi hồ sơ đăng ký thẻ thư viện.'),
                201
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }
    }

    /**
     * Gửi lại hồ sơ khi đang chờ duyệt: hủy bản chờ duyệt hiện tại rồi tạo bản mới (atomic, khóa hàng).
     */
    public function replace(MeLibraryCardStoreRequest $request): JsonResponse
    {
        try {
            $photoFile = $request->file('photo_file');
            $card = $this->libraryCardService->replaceOwnPendingReviewApplication(
                $request->user(),
                $request->validated(),
                $photoFile
            );

            if ($card instanceof LibraryCard) {
                $this->libraryCardNotificationDispatcher->notifyStaffOnNewCardApplication($card);
            }

            return ApiResponse::success(
                new LibraryCardResource($card->loadMissing([
                    'payment.collector',
                    'period',
                    'faculty',
                    'department',
                    'user',
                ])),
                __('Đã gửi lại hồ sơ cấp thẻ thư viện.'),
                201
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }
    }

    /** Hủy yêu cầu cấp thẻ khi hồ sơ còn ở bước chờ duyệt / chờ thanh toán. */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->libraryCardService->cancelOwnPendingApplication($user);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success(null, __('Đã hủy yêu cầu cấp thẻ thư viện.'));
    }
}
