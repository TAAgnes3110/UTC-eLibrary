<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorageCabinetRequest;
use App\Http\Resources\StorageCabinetResource;
use App\Models\StorageCabinet;
use App\Services\StorageCabinetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorageCabinetController extends Controller
{
    public function __construct(private StorageCabinetService $service) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->service->paginate((int) $request->input('per_page', 20), [
            'keyword' => $request->input('keyword'),
            'warehouse_id' => $request->input('warehouse_id'),
            'classification_id' => $request->input('classification_id'),
            'status' => $request->input('status'),
            'sort' => $request->input('sort'),
            'search_in' => $request->input('search_in'),
        ]);

        return ApiResponse::success([
            'data' => StorageCabinetResource::collection($items->items())->resolve(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
        ]);
    }

    public function store(StorageCabinetRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return ApiResponse::success(new StorageCabinetResource($item), __('messages.success_create'), 201);
    }

    public function update(StorageCabinetRequest $request, StorageCabinet $storageCabinet): JsonResponse
    {
        $item = $this->service->update($storageCabinet, $request->validated());

        return ApiResponse::success(new StorageCabinetResource($item), __('messages.success_update'));
    }

    public function destroy(StorageCabinet $storageCabinet): JsonResponse
    {
        $this->service->destroy($storageCabinet);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

}
