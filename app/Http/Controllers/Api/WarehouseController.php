<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResoure;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $items = $this->warehouseService->index($keyword);
        return ApiResponse::success(WarehouseResoure::collection($items));
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        return ApiResponse::success(new WarehouseResoure($warehouse));
    }

    public function store(WarehouseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $warehouse = $this->warehouseService->create($data);
        $warehouse->load('parent', 'createdBy', 'updatedBy');

        return ApiResponse::success(new WarehouseResoure($warehouse), __('messages.success_create'), 201);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse = $this->warehouseService->update($warehouse, $request->validated());
        return ApiResponse::success(new WarehouseResoure($warehouse), __('messages.success_update'));
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->warehouseService->destroy($warehouse);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    
}
