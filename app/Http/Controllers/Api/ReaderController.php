<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ReaderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chỉ điều hướng: gọi ReaderService, trả ApiResponse.
 */
class ReaderController extends Controller
{
    public function __construct(
        private ReaderService $readerService
    ) {}

    public function dashboardData(Request $request): JsonResponse
    {
        $payload = $this->readerService->dashboardData($request->user());
        return ApiResponse::success($payload);
    }

    public function loansData(Request $request): JsonResponse
    {
        $payload = $this->readerService->loansData($request->user());
        return ApiResponse::success($payload);
    }

    public function cardData(Request $request): JsonResponse
    {
        $payload = $this->readerService->cardData($request->user());
        return ApiResponse::success($payload);
    }
}
