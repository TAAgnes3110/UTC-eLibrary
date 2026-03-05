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

    public function dashboard(Request $request): JsonResponse
    {
        $payload = $this->readerService->dashboard($request->user());
        return ApiResponse::success($payload);
    }

    public function loans(Request $request): JsonResponse
    {
        $payload = $this->readerService->loans($request->user());
        return ApiResponse::success($payload);
    }

    public function card(Request $request): JsonResponse
    {
        $payload = $this->readerService->card($request->user());
        return ApiResponse::success($payload);
    }
}
