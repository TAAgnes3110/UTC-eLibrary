<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassificationRequest;
use App\Http\Resources\ClassificationResource;
use App\Models\Classification;
use App\Services\ClassificationService;
use App\Exports\SimpleTableExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Maatwebsite\Excel\Facades\Excel;

class ClassificationController extends Controller
{
    public function __construct(
        private ClassificationService $classificationService
    ) {}

    /**
     * Danh sách phân loại sách
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $items = $this->classificationService->index($keyword);
        return ApiResponse::success(ClassificationResource::collection($items));
    }

    /**
     * Danh sách tất cả phân loại (dùng cho dropdown)
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $items = $this->classificationService->listAll();
        return ApiResponse::success(ClassificationResource::collection($items));
    }

    /**
     * Hiển thị thông tin phân loại sách
     * @param Classification $classification
     * @return JsonResponse
     */
    public function show(Classification $classification): JsonResponse
    {
        $classification->load(['parent:id,code,name', 'details:id,code,name,classification_id']);
        return ApiResponse::success(new ClassificationResource($classification));
    }

    /**
     * Tạo mới phân loại sách
     * @param ClassificationRequest $request
     * @return JsonResponse
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
     * @param ClassificationRequest $request
     * @param Classification $classification
     * @return JsonResponse
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
     * @param Classification $classification
     * @return JsonResponse
     */
    public function destroy(Classification $classification): JsonResponse
    {
        $this->classificationService->destroy($classification);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Import danh sách phân loại sách từ file Excel.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        $summary = $this->classificationService->importClassifications($request->file('file'));
        return ApiResponse::success($summary, __('messages.success_import'));
    }

    /**
     * Tải file mẫu nhập phân loại sách.
     *
     * @return BinaryFileResponse
     */
    public function downloadImportTemplate(): BinaryFileResponse
    {
        $disk = Storage::disk('public');
        $file = 'Mẫu nhập phân loại sách.xlsx';
        if ($disk->exists($file)) {
            return response()->download(storage_path('app/public/' . $file));
        }
        $export = new SimpleTableExport(collect(), ['Mã phân loại', 'Tên phân loại']);
        Excel::store($export, $file, 'public');
        return response()->download(storage_path('app/public/' . $file));
    }

    /**
     * Xuất danh sách phân loại sách ra file Excel.
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        } else {
            $ids = null;
        }
        return $this->classificationService->exportClassifications($ids);
    }
}
