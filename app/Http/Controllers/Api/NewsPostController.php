<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\NewsPostRequest;
use App\Models\NewsPost;
use App\Services\NewsPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsPostController extends Controller
{
    public function __construct(
        private readonly NewsPostService $newsPostService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:news,notice'],
            'sort' => ['nullable', 'string', 'in:newest,oldest'],
            'search_in' => ['nullable', 'array'],
            'search_in.*' => ['string', 'in:title,content'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 100);
        $items = $this->newsPostService->paginate($validated, $perPage);

        return ApiResponse::success([
            'data' => array_map(fn (NewsPost $post): array => $this->mapPost($post, false), $items->items()),
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

    public function publicIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:news,notice'],
            'sort' => ['nullable', 'string', 'in:newest,oldest'],
            'search_in' => ['nullable', 'array'],
            'search_in.*' => ['string', 'in:title,content'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 100);
        $items = $this->newsPostService->paginatePublicPublished($validated, $perPage);

        return ApiResponse::success([
            'data' => array_map(fn (NewsPost $post): array => $this->mapPost($post, false), $items->items()),
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

    public function publicShow(string $slug): JsonResponse
    {
        $post = $this->newsPostService->findPublishedBySlug($slug);
        if (! $post instanceof NewsPost) {
            return ApiResponse::notFound(__('Không tìm thấy bài viết.'));
        }

        return ApiResponse::success($this->mapPost($post));
    }

    public function store(NewsPostRequest $request): JsonResponse
    {
        $post = $this->newsPostService->create(
            $request->validated(),
            $request->file('attachments', []),
            $request->file('thumbnail')
        );

        return ApiResponse::success(
            $this->mapPost($post),
            __('Tạo bài viết thành công.'),
            201
        );
    }

    public function show(NewsPost $newsPost): JsonResponse
    {
        return ApiResponse::success($this->mapPost($newsPost->load(['attachments', 'createdBy:id,name,email'])));
    }

    public function update(NewsPostRequest $request, NewsPost $newsPost): JsonResponse
    {
        $post = $this->newsPostService->update(
            $newsPost,
            $request->validated(),
            $request->file('attachments', []),
            $request->file('thumbnail')
        );

        return ApiResponse::success(
            $this->mapPost($post),
            __('Cập nhật bài viết thành công.')
        );
    }

    public function destroy(NewsPost $newsPost): JsonResponse
    {
        $this->newsPostService->delete($newsPost->load('attachments'));

        return ApiResponse::success(null, null, 204);
    }

    public function uploadContentImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
        ]);

        $path = $this->newsPostService->uploadContentImage($validated['image']);

        return ApiResponse::success([
            'path' => $path,
            'url' => Storage::url($path),
        ]);
    }

    private function mapPost(NewsPost $post, bool $includeAttachments = true): array
    {
        $attachments = [];
        if ($includeAttachments && $post->relationLoaded('attachments')) {
            $attachments = $post->attachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'mime' => $attachment->mime,
                'byte_size' => $attachment->byte_size,
                'file_path' => $attachment->file_path,
            ])->values()->all();
        }

        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'content' => $post->content,
            'status' => $post->status,
            'type' => $post->type,
            'thumbnail_path' => $post->thumbnail_path,
            'thumbnail_url' => $post->thumbnail_path ? Storage::url($post->thumbnail_path) : null,
            'published_at' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'posted_by' => $post->createdBy ? [
                'id' => $post->createdBy->id,
                'name' => $post->createdBy->name,
                'email' => $post->createdBy->email,
            ] : null,
            'attachments' => $attachments,
        ];
    }
}
