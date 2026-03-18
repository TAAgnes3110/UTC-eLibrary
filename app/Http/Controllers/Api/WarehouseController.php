<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResoure;
use App\Exports\WarehouseImportTemplateExport;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}
    /**
     * Danh sách kho
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $items = $this->warehouseService->index($keyword);
        return ApiResponse::success(WarehouseResoure::collection($items));
    }

    /**
     * Hiển thị thông tin kho
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return ApiResponse::success(new WarehouseResoure($warehouse));
    }

    /**
     * Tạo mới kho
     * @param WarehouseRequest $request
     * @return JsonResponse
     */
    public function store(WarehouseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $warehouse = $this->warehouseService->create($data);
        $warehouse->load('parent', 'createdBy', 'updatedBy');
        return ApiResponse::success(new WarehouseResoure($warehouse), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin kho
     * @param WarehouseRequest $request
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function update(WarehouseRequest $request, int $id): JsonResponse
    {
        unset($request->id,$request->created_at, $request->updated_at);
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $warehouse = $this->warehouseService->update($warehouse, $request->validated());
        return ApiResponse::success(new WarehouseResoure($warehouse), __('messages.success_update'));
    }

    /**
     * Xóa mềm kho
     * @param Warehouse $warehouse
     * @return JsonResponse
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        if (!$warehouse) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $this->warehouseService->destroy($warehouse);
        return ApiResponse::success(null, __('messages.success_delete'));
    }
    /**
     * Danh sách kho đã xóa
     * @param Request $request
     * @return JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        $items = $this->warehouseService->trash();
        return ApiResponse::success(WarehouseResoure::collection($items));
    }

    /**
     * Khôi phục kho
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $warehouse = $this->warehouseService->restore($id);
        if (!$warehouse) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(new WarehouseResoure($warehouse), __('messages.success_restore'));
    }

    public function restoreMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        $restored = $this->warehouseService->restoreMany($request->input('ids', []));
        return ApiResponse::success(['restored' => $restored], __('messages.success_restore'));
    }
    /**
     * Xóa vĩnh viễn kho
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        if (!$this->warehouseService->forceDelete($id)) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        return ApiResponse::success(null, __('messages.success_force_delete'));
    }

    public function forceDeleteMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        $deleted = $this->warehouseService->forceDeleteMany($request->input('ids', []));
        return ApiResponse::success(['deleted' => $deleted], __('messages.success_force_delete'));
    }
    /**
     * Cập nhật trạng thái kho
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'is_active' => 'required|boolean',
        ]);
        $this->warehouseService->updateStatus($request->ids, $request->boolean('is_active'));
        return ApiResponse::success(null, __('messages.success_update'));
    }

    /**
     * Chuyển đổi trạng thái kho
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->warehouseService->toggleStatus($id);
        if ($result === null) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        return ApiResponse::success($result, __('messages.success_update'));
    }

    public function warehouseList(Request $request): JsonResponse
    {
        $items = $this->warehouseService->warehouseList();
        return ApiResponse::success(WarehouseResoure::collection($items));
    }

    public function trashList(Request $request): JsonResponse
    {
        $items = $this->warehouseService->trashList();
        return ApiResponse::success(WarehouseResoure::collection($items));
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return WarehouseImportTemplateExport::stream();
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        $file = $request->file('file');
        if (!$file) {
            return ApiResponse::error(__('Vui lòng chọn file Excel.'), 422);
        }
        $summary = $this->warehouseService->importWarehouses($file);
        return ApiResponse::success($summary, __('Đã import kho sách xong.'));
    }

    public function exportWarehouses(Request $request): StreamedResponse
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        } else {
            $ids = null;
        }
        return $this->warehouseService->exportWarehouses($ids);
    }
}
