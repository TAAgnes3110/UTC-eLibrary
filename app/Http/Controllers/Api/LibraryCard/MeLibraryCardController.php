<?php

namespace App\Http\Controllers\Api\LibraryCard;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\MeLibraryCardStoreRequest;
use App\Http\Resources\LibraryCardResource;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Xin cấp thẻ khi đã đăng nhập — validate {@see MeLibraryCardStoreRequest}, gọi {@see LibraryCardService}.
 */
class MeLibraryCardController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService
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
}
