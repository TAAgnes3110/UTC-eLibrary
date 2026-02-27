<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;
use App\Imports\AuthorsImport;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{

    /**
     * Danh sách tác giả có phân trang, tìm theo từ khóa (tên hoặc id).
     *
     * @param Request $request
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
        return $this->jsonResponse($items->toArray());
    }

    /**
     * Chi tiết một tác giả theo id.
     *
     * @param Author $author
     * @return JsonResponse
     */
    public function show(Author $author): JsonResponse
    {
        return $this->jsonResponse(['status' => 'success', 'data' => $author]);
    }

    /**
     * Thống kê tổng số tác giả.
     *
     * @return JsonResponse
     */
    public function countAuthor(): JsonResponse
    {
        return $this->jsonResponse(['total' => Author::query()->count()]);
    }

    /**
     * Danh sách tác giả kèm số đầu sách (dùng cho báo cáo / thống kê).
     *
     * @return JsonResponse
     */
    public function countBookByAuthor(): JsonResponse
    {
        return $this->jsonResponse(['data' => Author::query()->withCount('books')->get()]);
    }

    /**
     * Thêm tác giả mới.
     *
     * @param AuthorRequest $request
     * @return JsonResponse
     */
    public function store(AuthorRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (Author::duplicate($data)->exists()) {
            return $this->jsonResponse(['status' => 'error', 'message' => __('messages.error_duplicate')], 400);
        }
        $author = Author::create($data);
        return $this->jsonResponse([
            'status' => 'success',
            'message' => __('messages.success_create'),
            'data' => $author,
        ], 201);
    }

    /**
     * Import danh sách tác giả từ file Excel/CSV.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        $file = $request->file('file');
        if (!FileHelpers::isExcelFile($file)) {
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'File phải có định dạng: ' . implode(', ', FileHelpers::EXCEL_EXTENSIONS),
            ], 422);
        }
        $result = (new AuthorsImport())->import($file);
        $code = match ($result['status']) {
            'success' => 200,
            'partial' => 207,
            default => 422
        };
        return $this->jsonResponse([
            'status' => $result['status'],
            'message' => "Import: {$result['summary']['success']} thành công, {$result['summary']['skipped']} bỏ qua, {$result['summary']['errors']} lỗi.",
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
            return $this->jsonResponse(['status' => 'error', 'message' => __('messages.error_duplicate')], 400);
        }
        $author->update($data);
        return $this->jsonResponse([
            'status' => 'success',
            'message' => __('messages.success_update'),
            'data' => $author->fresh(),
        ]);
    }

    /**
     * Xóa tác giả (xóa mềm nếu model dùng SoftDeletes).
     *
     * @param Author $author
     * @return JsonResponse
     */
    public function destroy(Author $author): JsonResponse
    {
        $author->delete();
        return $this->jsonResponse([
            'status' => 'success',
            'message' => __('messages.success_delete'),
        ]);
    }

    /**
     * Danh sách tác giả đã xóa mềm (thùng rác).
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
        return $this->jsonResponse(['data' => $items]);
    }

    /**
     * Khôi phục tác giả từ thùng rác.
     */
    public function restore($id): JsonResponse
    {
        $author = Author::onlyTrashed()->find($id);
        if (!$author) {
            return $this->jsonResponse(['status' => 'error', 'message' => __('messages.error_410')], 410);
        }
        $author->restore();
        return $this->jsonResponse(['status' => 'success', 'message' => __('Đã khôi phục.')]);
    }

    /**
     * Xóa vĩnh viễn tác giả.
     */
    public function forceDelete($id): JsonResponse
    {
        $author = Author::onlyTrashed()->find($id);
        if (!$author) {
            return $this->jsonResponse(['status' => 'error', 'message' => __('messages.error_410')], 410);
        }
        $author->forceDelete();
        return $this->jsonResponse(['status' => 'success', 'message' => __('Đã xóa vĩnh viễn.')]);
    }
}
