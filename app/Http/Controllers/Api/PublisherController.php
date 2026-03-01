<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\PublisherService;
use Illuminate\Http\JsonResponse;

/**
 * Chỉ điều hướng: gọi PublisherService, trả ApiResponse.
 */
class PublisherController extends Controller
{
    public function __construct(
        private PublisherService $publisherService
    ) {}

    public function index(): JsonResponse
    {
        $items = $this->publisherService->listForApi();
        return ApiResponse::success($items);
    }
}
