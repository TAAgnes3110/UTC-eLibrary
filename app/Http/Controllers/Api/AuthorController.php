<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chỉ điều hướng: gọi AuthorService, trả ApiResponse.
 */
class AuthorController extends Controller
{
    public function __construct(
        private AuthorService $authorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->authorService->list(
            $request->input('keyword'),
            (int) $request->input('per_page', 10)
        );
        return ApiResponse::success($paginator->toArray());
    }

    public function show(Author $author): JsonResponse
    {
        return ApiResponse::success($author);
    }

    public function countAuthor(): JsonResponse
    {
        return ApiResponse::success(['total' => $this->authorService->count()]);
    }

    public function countBookByAuthor(): JsonResponse
    {
        return ApiResponse::success($this->authorService->countBookByAuthor());
    }

    public function store(AuthorRequest $request): JsonResponse
    {
        try {
            $author = $this->authorService->create($request->validated());
            return ApiResponse::success($author, __('messages.success_create'), 201);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        try {
            $result = $this->authorService->import($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
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

    public function update(AuthorRequest $request, Author $author): JsonResponse
    {
        try {
            $author = $this->authorService->update($author, $request->validated());
            return ApiResponse::success($author, __('messages.success_update'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function destroy(Author $author): JsonResponse
    {
        $this->authorService->destroy($author);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function trash(Request $request): JsonResponse
    {
        $paginator = $this->authorService->trash(AuthorService::TRASH_PER_PAGE)->withQueryString();
        return ApiResponse::success($paginator->toArray());
    }

    public function restore($id): JsonResponse
    {
        $author = $this->authorService->restore((int) $id);
        if (!$author) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    public function forceDelete($id): JsonResponse
    {
        if (!$this->authorService->forceDelete((int) $id)) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã xóa vĩnh viễn.'));
    }
}
