<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClassificationImportTemplateExport;
use App\Exports\ClassificationExport;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassificationRequest;
use App\Http\Resources\ClassificationResource;
use App\Models\Classification;
use App\Services\ClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassificationController extends Controller
{
    public function __construct(
        private ClassificationService $classificationService
    ) {}

    /**
     * Danh sách phân loại sách
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $items = $this->classificationService->index($keyword);

        return ApiResponse::success(ClassificationResource::collection($items));
    }

    /**
     * Danh sách tất cả phân loại (dùng cho dropdown)
     */
    public function list(Request $request): JsonResponse
    {
        $items = $this->classificationService->listAll();

        return ApiResponse::success(ClassificationResource::collection($items));
    }

    /**
     * Hiển thị thông tin phân loại sách
     */
    public function show(Classification $classification): JsonResponse
    {
        $classification->load(['parent:id,code,name']);

        return ApiResponse::success(new ClassificationResource($classification));
    }

    /**
     * Tạo mới phân loại sách
     */
    public function store(ClassificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $classification = $this->classificationService->create($data);
        $classification->load('parent:id,code,name');

        return ApiResponse::success(new ClassificationResource($classification), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin phân loại sách
     */
    public function update(ClassificationRequest $request, Classification $classification): JsonResponse
    {
        $data = $request->validated();
        $classification = $this->classificationService->update($classification, $data);
        $classification->load('parent:id,code,name');

        return ApiResponse::success(new ClassificationResource($classification), __('messages.success_update'));
    }

    /**
     * Xóa phân loại sách
     */
    public function destroy(Classification $classification): JsonResponse
    {
        $this->classificationService->destroy($classification);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return ClassificationImportTemplateExport::stream();
    }

    public function export(Request $request): StreamedResponse
    {
        $ids = $request->input('ids');
        $ids = is_array($ids)
            ? array_values(array_filter(array_map('intval', $ids), static fn ($v) => $v > 0))
            : null;

        return ClassificationExport::stream($ids);
    }
}
