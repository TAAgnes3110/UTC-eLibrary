<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Exports\BookImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    public function __construct(
        private BookService $bookService
    ) {
    }

    /**
     * Danh sách sách.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $perPage = (int) $request->input('per_page', 50);

        $items = $this->bookService->index($keyword, $perPage);

        return ApiResponse::success(BookResource::collection($items));
    }

    /**
     * Tạo mới sách.
     *
     * @param BookRequest $request
     * @return JsonResponse
     */
    public function store(BookRequest $request): JsonResponse
    {
        $book = $this->bookService->create($request->validated());

        return ApiResponse::success(new BookResource($book), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin sách.
     *
     * @param BookRequest $request
     * @param Book $book
     * @return JsonResponse
     */
    public function update(BookRequest $request, Book $book): JsonResponse
    {
        $book = $this->bookService->update($book, $request->validated());

        return ApiResponse::success(new BookResource($book), __('messages.success_update'));
    }

    /**
     * Xem chi tiết một sách.
     *
     * @param Book $book
     * @return JsonResponse
     */
    public function show(Book $book): JsonResponse
    {
        $book->load([
            'classification:id,code,name',
            'classificationDetail:id,code,name,classification_id',
            'warehouse:id,code,name',
            'authors:id,name',
            'publishers:id,name',
        ]);

        return ApiResponse::success(new BookResource($book));
    }

    /**
     * Xóa mềm một sách.
     *
     * @param Book $book
     * @return JsonResponse
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->bookService->destroy($book);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Danh sách sách đã xóa mềm.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 50);
        $items = $this->bookService->trash($perPage);

        return ApiResponse::success(BookResource::collection($items));
    }

    /**
     * Khôi phục một sách đã xóa mềm.
     *
     * @param int $id ID sách cần khôi phục
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $book = $this->bookService->restore($id);
        if (!$book) {
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
     * @param int $id ID sách cần xóa vĩnh viễn
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        if (!$this->bookService->forceDelete($id)) {
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
        if (!$book) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $file = $request->file('book_cover');
        if (!$file) {
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
        if (!$file) {
            return ApiResponse::error(__('Vui lòng chọn một file .zip hợp lệ.'), 422);
        }
        try {
            $summary = $this->bookService->bulkUpdateCoverFromZip($file);
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

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);
        $file = $request->file('file');
        if (!$file) {
            return ApiResponse::error(__('Vui lòng chọn file Excel.'), 422);
        }
        $summary = $this->bookService->importBooks($file);
        return ApiResponse::success($summary, __('Đã import sách in xong.'));
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return BookImportTemplateExport::stream();
    }
}

