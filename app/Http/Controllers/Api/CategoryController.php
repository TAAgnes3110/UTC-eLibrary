<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chỉ điều hướng: gọi CategoryService, trả ApiResponse.
 */
class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->categoryService->listForApi());
    }

    public function adminPageData(Request $request): JsonResponse
    {
        $payload = $this->categoryService->adminPageData($request->input('tab', 'category'));
        return ApiResponse::success($payload);
    }
}
