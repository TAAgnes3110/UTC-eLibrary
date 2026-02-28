<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ApiResponse;
use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuthorRequest;
use App\Imports\AuthorsImport;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller CRUD tác giả, import Excel, thùng rác.
 *
 * @todo Thêm phân trang cấu hình (per_page) từ request.
 */
class AuthorController extends Controller
{
    /**
     * Danh sách tác giả có phân trang, tìm theo từ khóa (tên hoặc id).
     *
     * @param Request $request Query: keyword (optional).
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $items = Author::query()
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
                if (is_numeric($keyword)) {
                    $q->orWhere('id', (int) $keyword);
                }
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
        return ApiResponse::success($items->toArray());
    }

    /**
     * Chi tiết một tác giả theo id.
     *
     * @param Author $author
     * @return JsonResponse
     */
    public function show(Author $author): JsonResponse
    {
        return ApiResponse::success($author);
    }

    /**
     * Thống kê tổng số tác giả.
     *
     * @return JsonResponse { total: int }
     */
    public function countAuthor(): JsonResponse
    {
        return ApiResponse::success(['total' => Author::query()->count()]);
    }

    /**
     * Danh sách tác giả kèm số đầu sách (dùng cho báo cáo / thống kê).
     *
     * @return JsonResponse
     */
    public function countBookByAuthor(): JsonResponse
    {
        return ApiResponse::success(Author::query()->withCount('books')->get());
    }

    /**
     * Thêm tác giả mới.
     *
     * @param AuthorRequest $request
     * @return JsonResponse 201 + data author, 400 nếu trùng.
     */
    public function store(AuthorRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (Author::duplicate($data)->exists()) {
            return ApiResponse::error(__('messages.error_duplicate'), 400);
        }
        $author = Author::create($data);
        return ApiResponse::success($author, __('messages.success_create'), 201);
    }

    /**
     * Import danh sách tác giả từ file Excel/CSV.
     *
     * @param Request $request file (required).
     * @return JsonResponse status, messages, data (summary + errors).
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        $file = $request->file('file');
        if (!FileHelpers::isExcelFile($file)) {
            return ApiResponse::error(
                'File phải có định dạng: ' . implode(', ', FileHelpers::EXCEL_EXTENSIONS),
                422
            );
        }
        $result = (new AuthorsImport())->import($file);
        $code = match ($result['status']) {
            'success' => 200,
            'partial' => 207,
            default => 422,
        };
        $message = "Import: {$result['summary']['success']} thành công, {$result['summary']['skipped']} bỏ qua, {$result['summary']['errors']} lỗi.";
        return ApiResponse::json([
            'status' => $result['status'],
            'messages' => $message,
            'data' => $result,
        ], $code);
    }

    /**
     * Cập nhật thông tin tác giả.
     *
     * @param AuthorRequest $request
     * @param Author $author
     * @return JsonResponse
     */
    public function update(AuthorRequest $request, Author $author): JsonResponse
    {
        $data = $request->validated();
        if (Author::duplicate($data, $author->id)->exists()) {
            return ApiResponse::error(__('messages.error_duplicate'), 400);
        }
        $author->update($data);
        return ApiResponse::success($author->fresh(), __('messages.success_update'));
    }

    /**
     * Xóa tác giả (xóa mềm).
     *
     * @param Author $author
     * @return JsonResponse
     */
    public function destroy(Author $author): JsonResponse
    {
        $author->delete();
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Danh sách tác giả đã xóa mềm (thùng rác).
     *
     * @return JsonResponse
     */
    public function trash(): JsonResponse
    {
        $items = Author::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get(['id', 'name', 'nationality', 'deleted_at'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'nationality' => $a->nationality,
                'deleted_at' => $a->deleted_at?->toIso8601String(),
            ]);
        return ApiResponse::success($items->toArray());
    }

    /**
     * Khôi phục tác giả từ thùng rác.
     *
     * @param int|string $id
     * @return JsonResponse 200 hoặc 410.
     */
    public function restore($id): JsonResponse
    {
        $author = Author::onlyTrashed()->find($id);
        if (!$author) {
            return ApiResponse::notFound();
        }
        $author->restore();
        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    /**
     * Xóa vĩnh viễn tác giả.
     *
     * @param int|string $id
     * @return JsonResponse 200 hoặc 410.
     */
    public function forceDelete($id): JsonResponse
    {
        $author = Author::onlyTrashed()->find($id);
        if (!$author) {
            return ApiResponse::notFound();
        }
        $author->forceDelete();
        return ApiResponse::success(null, __('Đã xóa vĩnh viễn.'));
    }
}
