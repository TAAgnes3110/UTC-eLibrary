<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function __construct(
        private readonly AuthorService $authorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('keyword', '');
        $authors = $this->authorService->index($keyword ?: null);
        return ApiResponse::success($authors);
    }

    public function store(AuthorRequest $request): JsonResponse
    {
        $author = $this->authorService->create($request->validated());
        return ApiResponse::success($author, __('Thêm tác giả thành công.'), 201);
    }

    public function update(AuthorRequest $request, Author $author): JsonResponse
    {
        $author = $this->authorService->update($author, $request->validated());
        return ApiResponse::success($author, __('Cập nhật tác giả thành công.'));
    }

    public function destroy(Author $author): JsonResponse
    {
        $author->delete();
        return ApiResponse::success(null, __('Xóa tác giả thành công.'), 204);
    }
}
