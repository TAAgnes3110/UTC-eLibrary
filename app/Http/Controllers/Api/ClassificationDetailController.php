<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Exports\ClassificationDetailImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassificationDetailRequest;
use App\Http\Resources\ClassificationDetailResource;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Services\ClassificationDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassificationDetailController extends Controller
{
    public function __construct(
        private ClassificationDetailService $classificationDetailService
    ) {}

    /**
     * Danh sách phân loại sách chi tiết
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $classificationId = $request->integer('classification_id') ?: null;
        if ($request->has('classification_id') && $request->input('classification_id') === '') {
            $classificationId = null;
        }
        $items = $this->classificationDetailService->index($keyword, $classificationId);
        return ApiResponse::success(ClassificationDetailResource::collection($items));
    }

    /**
     * Danh sách phân loại chi tiết theo phân loại chính (dùng cho dropdown)
     * @param Classification $classification
     * @return JsonResponse
     */
    public function listByClassification(Classification $classification): JsonResponse
    {
        $items = $this->classificationDetailService->listByClassification($classification->id);
        return ApiResponse::success(ClassificationDetailResource::collection($items));
    }

    /**
     * Hiển thị thông tin phân loại sách chi tiết
     * @param ClassificationDetail $classification_detail
     * @return JsonResponse
     */
    public function show(ClassificationDetail $classification_detail): JsonResponse
    {
        $classification_detail->load(['classification:id,code,name', 'parent:id,code,name']);
        return ApiResponse::success(new ClassificationDetailResource($classification_detail));
    }

    /**
     * Tạo mới phân loại sách chi tiết
     * @param ClassificationDetailRequest $request
     * @return JsonResponse
     */
    public function store(ClassificationDetailRequest $request): JsonResponse
    {
        $data = $request->validated();
        $detail = $this->classificationDetailService->create($data);
        $detail->load(['classification:id,code,name', 'parent:id,code,name']);
        return ApiResponse::success(new ClassificationDetailResource($detail), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin phân loại sách chi tiết
     * @param ClassificationDetailRequest $request
     * @param ClassificationDetail $classification_detail
     * @return JsonResponse
     */
    public function update(ClassificationDetailRequest $request, ClassificationDetail $classification_detail): JsonResponse
    {
        $data = $request->validated();
        $detail = $this->classificationDetailService->update($classification_detail, $data);
        $detail->load(['classification:id,code,name', 'parent:id,code,name']);
        return ApiResponse::success(new ClassificationDetailResource($detail), __('messages.success_update'));
    }

    /**
     * Xóa phân loại sách chi tiết
     * @param ClassificationDetail $classification_detail
     * @return JsonResponse
     */
    public function destroy(ClassificationDetail $classification_detail): JsonResponse
    {
        $this->classificationDetailService->destroy($classification_detail);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return ClassificationDetailImportTemplateExport::stream();
    }
}
