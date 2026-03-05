<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->categoryService->listForApi());
    }

    public function adminList(Request $request): JsonResponse
    {
        $payload = $this->categoryService->adminList($request->input('tab', 'category'));
        return ApiResponse::success($payload);
    }

    /**
     * Gợi ý thể loại theo từ khóa (autocomplete). Dùng cho ô tìm thể loại thủ thư.
     */
    public function search(Request $request): JsonResponse
    {
        $keyword = (string) $request->input('q', '');
        $items = $this->categoryService->searchCategory($keyword);
        return ApiResponse::success($items);
    }
}
