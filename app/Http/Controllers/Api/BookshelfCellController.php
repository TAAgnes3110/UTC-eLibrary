<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookshelfCellRequest;
use App\Http\Requests\BookshelfGenerateRequest;
use App\Http\Resources\BookshelfCellResource;
use App\Models\BookshelfCell;
use App\Models\Warehouse;
use App\Services\BookshelfCellService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class BookshelfCellController extends Controller
{
    public function __construct(
        private BookshelfCellService $bookshelfCellService
    ) {}

    public function indexByWarehouse(Request $request, Warehouse $warehouse): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->bookshelfCellService->paginate((int) $warehouse->id, $perPage, [
            'keyword' => $request->input('keyword'),
            'status' => $request->input('status'),
            'sort' => $request->input('sort'),
        ]);

        return ApiResponse::success($this->paginatorPayload($items));
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 20);
        $warehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        $items = $this->bookshelfCellService->paginate($warehouseId, $perPage, [
            'keyword' => $request->input('keyword'),
            'status' => $request->input('status'),
            'sort' => $request->input('sort'),
        ]);

        return ApiResponse::success($this->paginatorPayload($items));
    }

    private function paginatorPayload(LengthAwarePaginator $items): array
    {
        return [
            'data' => BookshelfCellResource::collection($items->items())->resolve(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
        ];
    }

    public function generateByWarehouse(BookshelfGenerateRequest $request, Warehouse $warehouse): JsonResponse
    {
        $summary = $this->bookshelfCellService->generateMatrix(
            $warehouse,
            (bool) $request->boolean('reset', true),
            $request->filled('max_rows') ? (int) $request->input('max_rows') : null,
            $request->filled('max_columns') ? (int) $request->input('max_columns') : null
        );

        return ApiResponse::success($summary, 'Đã tạo dữ liệu mẫu ma trận kệ sách.');
    }

    public function store(BookshelfCellRequest $request): JsonResponse
    {
        try {
            $item = $this->bookshelfCellService->createAutoPlacement($request->validated());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success(new BookshelfCellResource($item), __('messages.success_create'), 201);
    }

    public function update(BookshelfCellRequest $request, BookshelfCell $bookshelfCell): JsonResponse
    {
        try {
            $item = $this->bookshelfCellService->update($bookshelfCell, $request->validated());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e);
        }

        return ApiResponse::success(new BookshelfCellResource($item), __('messages.success_update'));
    }

    public function destroy(BookshelfCell $bookshelfCell): JsonResponse
    {
        $this->bookshelfCellService->destroy($bookshelfCell);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function export(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        } else {
            $ids = null;
        }

        return $this->bookshelfCellService->exportCells($ids);
    }
}
