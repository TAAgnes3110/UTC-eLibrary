<?php

namespace App\Http\Controllers\Api\LibraryCard;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryCardRequest;
use App\Http\Resources\LibraryCardResource;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Đăng ký thẻ không tài khoản — validate {@see LibraryCardRequest}, gọi {@see LibraryCardService}.
 */
class LibraryCardGuestController extends Controller
{
    public function __construct(
        private LibraryCardService $libraryCardService
    ) {}

    public function store(LibraryCardRequest $request): JsonResponse
    {
        try {
            $card = $this->libraryCardService->create($request->validated());

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
