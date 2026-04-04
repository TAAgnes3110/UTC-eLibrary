<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublisherRequest;
use App\Models\Publisher;
use App\Services\PublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function __construct(
        private readonly PublisherService $publisherService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $keyword = (string) $request->query('keyword', '');
        $publishers = $this->publisherService->index($keyword ?: null);

        return ApiResponse::success($publishers);
    }

    public function store(PublisherRequest $request): JsonResponse
    {
        $publisher = $this->publisherService->create($request->validated());

        return ApiResponse::success($publisher, __('Thêm nhà xuất bản thành công.'), 201);
    }

    public function update(PublisherRequest $request, Publisher $publisher): JsonResponse
    {
        $publisher = $this->publisherService->update($publisher, $request->validated());

        return ApiResponse::success($publisher, __('Cập nhật nhà xuất bản thành công.'));
    }

    public function destroy(Publisher $publisher): JsonResponse
    {
        $this->publisherService->delete($publisher);

        return ApiResponse::success(null, __('Xóa nhà xuất bản thành công.'), 204);
    }
}
