<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ReaderPageService;
use Illuminate\Http\JsonResponse;

/**
 * Chỉ điều hướng: gọi ReaderPageService, trả ApiResponse.
 */
class ReaderPageController extends Controller
{
    public function __construct(
        private ReaderPageService $readerPageService
    ) {}

    public function introContent(): JsonResponse
    {
        $content = $this->readerPageService->introContent();
        return ApiResponse::success(['content' => $content]);
    }

    public function rulesContent(): JsonResponse
    {
        $content = $this->readerPageService->rulesContent();
        return ApiResponse::success(['content' => $content]);
    }
}
