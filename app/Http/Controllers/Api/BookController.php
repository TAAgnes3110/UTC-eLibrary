<?php

namespace App\Http\Controllers\Api;

use App\Enums\LoanItemCondition;
use App\Exports\BookImportTemplateExport;
use App\Helpers\ApiResponse;
use App\Helpers\BulkZipRequestHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Warehouse;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    public function __construct(
        private BookService $bookService
    ) {}

    /**
     * Danh sách sách.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'resource_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'search_in' => ['sometimes', 'nullable', 'string'],
            'sort' => ['sometimes', 'nullable', 'in:newest,oldest,az,za'],
        ]);
        $keyword = $request->input('keyword');
        $resourceType = $request->input('resource_type');
        $perPage = (int) $request->input('per_page', 50);
        $searchColumns = $this->parseSearchInFilter($request);
        $sort = $request->input('sort');

        $items = $this->bookService->index($keyword, $resourceType, $perPage, $searchColumns, $sort);

        return ApiResponse::success($this->paginatorPayload($items));
    }

    /**
     * @return list<string>|null
     */
    private function parseSearchInFilter(Request $request): ?array
    {
        if (! $request->filled('search_in')) {
            return null;
        }
        $raw = $request->input('search_in');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $allowed = ['code', 'title', 'author', 'publisher', 'place', 'year', 'classification'];
        $filtered = array_values(array_intersect($candidates, $allowed));

        return $filtered === [] ? null : $filtered;
    }

    /**
     * Tạo mới sách.
     */
    public function store(BookRequest $request): JsonResponse
    {
        $book = $this->bookService->create($request->validated());

        return ApiResponse::success(new BookResource($book), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin sách.
     */
    public function update(BookRequest $request, Book $book): JsonResponse
    {
        $book = $this->bookService->update($book, $request->validated());

        return ApiResponse::success(new BookResource($book), __('messages.success_update'));
    }

    /**
     * Xem chi tiết một sách.
     */
    public function show(Book $book): JsonResponse
    {
        return ApiResponse::success(new BookResource($this->bookService->getForApiDetail($book)));
    }

    /**
     * Xóa mềm một sách.
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->destroy($book);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Danh sách sách đã xóa mềm.
     */
    public function trash(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);
        $items = $this->bookService->trash($perPage);

        return ApiResponse::success($this->paginatorPayload($items));
    }

    /**
     * Khôi phục một sách đã xóa mềm.
     *
     * @param  int  $id  ID sách cần khôi phục
     */
    public function restore(int $id): JsonResponse
    {
        $book = $this->bookService->restore($id);
        if (! $book) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    public function restoreMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        $restored = $this->bookService->restoreMany($request->input('ids', []));

        return ApiResponse::success(['restored' => $restored], __('messages.success_restore'));
    }

    /**
     * Xóa vĩnh viễn một sách.
     *
     * @param  int  $id  ID sách cần xóa vĩnh viễn
     */
    public function forceDelete(int $id): JsonResponse
    {
        if (! $this->bookService->forceDelete($id)) {
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
        $deleted = $this->bookService->forceDeleteMany($request->input('ids', []));

        return ApiResponse::success(['deleted' => $deleted], __('messages.success_force_delete'));
    }

    public function updateImage(Request $request, int $id): JsonResponse
    {
        $book = Book::find($id);
        if (! $book) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $file = $request->file('book_cover');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn file ảnh hợp lệ.'), 422);
        }
        try {
            $result = $this->bookService->updateCoverImage($book, $file);

            return ApiResponse::success($result, __('messages.success_update'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function bulkUpdateImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:zip',
        ]);
        $file = $request->file('file');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn một file .zip hợp lệ.'), 422);
        }
        $onlyBookIds = BulkZipRequestHelper::parseFilterIds($request);
        try {
            $summary = $this->bookService->bulkUpdateCoverFromZip($file, $onlyBookIds);

            return ApiResponse::success($summary, __('messages.success_update'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::error(__('Không thể xử lý file zip.'), 422);
        }
    }

    public function export(Request $request): StreamedResponse
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            $ids = array_values(array_filter($ids, static fn ($v) => is_numeric($v)));
        } else {
            $ids = null;
        }

        return $this->bookService->exportBooks($ids);
    }

    public function exportLost(Request $request): StreamedResponse
    {
        $resourceType = trim((string) $request->input('resource_type', ''));

        $ids = Book::query()
            ->when($resourceType !== '', fn ($q) => $q->where('resource_type', $resourceType))
            ->whereHas('loanItems', function ($q) {
                $q->where('condition_on_return', LoanItemCondition::LOST->value)
                    ->whereHas('loan', fn ($loanQ) => $loanQ->where('status', Loan::STATUS_RETURNED));
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Nếu không có sách mất, vẫn xuất file rỗng (không fallback sang toàn bộ sách).
        if ($ids === []) {
            $ids = [0];
        }

        return $this->bookService->exportBooks($ids);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        $file = $request->file('file');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn file Excel.'), 422);
        }
        $summary = $this->bookService->importBooks($file);

        return ApiResponse::success($summary, __('Đã import sách in xong.'));
    }

    public function previewIdentifiers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $preview = $this->bookService->previewIdentifiers((int) $data['warehouse_id']);

        return ApiResponse::success($preview);
    }

    public function storageSuggestions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
        ]);

        $warehouse = Warehouse::query()->findOrFail((int) $data['warehouse_id']);
        $suggestions = $this->bookService->suggestStorageCabinets(
            (int) $data['warehouse_id']
        );

        $isDigitalDepot = $this->bookService->isDigitalDocumentWarehouse($warehouse);

        return ApiResponse::success([
            'items' => $suggestions,
            'has_available' => $suggestions !== [],
            'message' => $suggestions === [] && ! $isDigitalDepot
                ? 'Trong kho này chưa có tủ lưu trữ phù hợp. Khi bạn lưu sách, hệ thống sẽ tự tạo tủ mặc định theo kho và phân loại.'
                : null,
        ]);
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return BookImportTemplateExport::stream();
    }

    private function paginatorPayload(LengthAwarePaginator $items): array
    {
        return [
            'data' => BookResource::collection($items->items())->resolve(),
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
}
