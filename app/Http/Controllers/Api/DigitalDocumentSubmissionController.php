<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Helpers\DeployHelper;
use App\Helpers\TextSanitizer;
use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalDocumentSubmissionPublicResource;
use App\Http\Resources\DigitalDocumentSubmissionResource;
use App\Models\User;
use App\Services\DigitalDocumentSubmissionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class DigitalDocumentSubmissionController extends Controller
{
    public function __construct(
        private DigitalDocumentSubmissionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:pending,approved,rejected'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:newest,oldest'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $paginator = $this->service->paginateForUser($user, $validated, $perPage);

        return ApiResponse::success($this->paginatorPayload($paginator));
    }

    public function publicIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:newest,oldest'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $paginator = $this->service->paginatePublicApproved($validated, $perPage);

        return ApiResponse::success($this->publicPaginatorPayload($paginator));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tải đồ án, luận văn.'), 401);
        }

        // PDF: chỉ tin phần mở rộng .pdf (+ kích thước). Trình duyệt thường gửi application/octet-stream; rule mimes/pdf hay File::types dễ từ chối oan.
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author_names' => ['required', 'string', 'max:500'],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:'.TextSanitizer::DEFAULT_LONG_TEXT_MAX_CHARS,
            ],
            'file' => [
                'required',
                'file',
                'max:'.DeployHelper::maxDigitalPdfUploadKilobytes(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail(__('Vui lòng chọn file PDF.'));

                        return;
                    }
                    $ext = strtolower($value->getClientOriginalExtension() ?: '');
                    if ($ext !== 'pdf') {
                        $fail(__('Chỉ hỗ trợ tải lên file PDF.'));
                    }
                },
            ],
            'cover_image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'author_names.required' => __('Vui lòng nhập tác giả đồ án, luận văn.'),
            'file.max' => __('File PDF không được vượt quá :max MB.', [
                'max' => DeployHelper::maxDigitalPdfUploadMegabytesLabel(),
            ]),
            'cover_image.image' => __('Ảnh bìa phải là file ảnh hợp lệ.'),
            'cover_image.mimes' => __('Ảnh bìa chỉ nhận JPEG, PNG hoặc WebP.'),
            'cover_image.max' => __('Ảnh bìa không được vượt quá 5MB.'),
        ]);

        $attrs = $request->only(['title', 'author_names', 'description']);
        $attrs['description'] = TextSanitizer::fromRichPaste($attrs['description'] ?? null);

        $item = $this->service->submitAsReaderPending(
            $user,
            $attrs,
            $request->file('file'),
            $request->file('cover_image')
        );

        return ApiResponse::success(
            new DigitalDocumentSubmissionResource($item->load(['submitter:id,name,email', 'reviewer:id,name'])),
            __('Đã gửi đồ án, luận văn, vui lòng chờ duyệt.'),
            201
        );
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $request->validate([
            'review_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $item = $this->service->approve($user, $id, $request->input('review_note'));

        return ApiResponse::success(
            new DigitalDocumentSubmissionResource($item),
            __('Duyệt đồ án, luận văn thành công.')
        );
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $request->validate([
            'review_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $item = $this->service->reject($user, $id, $request->input('review_note'));

        return ApiResponse::success(
            new DigitalDocumentSubmissionResource($item),
            __('Đã từ chối đồ án, luận văn.')
        );
    }

    public function hideMine(Request $request, int $id): JsonResponse
    {
        $user = $this->resolveAuthenticatedUser($request);
        if (! $user) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $this->service->hideFromSubmitterList($user, $id);

        return ApiResponse::success(
            null,
            __('Đã ẩn khỏi danh sách của bạn. Thủ thư vẫn xử lý trên hệ thống.')
        );
    }

    /**
     * Đồng bộ với Init middleware ($currentPerson) — tránh 401 oan khi JWT hợp lệ nhưng guard mặc định chưa gắn user.
     */
    private function resolveAuthenticatedUser(Request $request): ?User
    {
        global $currentPerson;

        if ($currentPerson instanceof User) {
            return $currentPerson;
        }

        return $request->user() ?? Auth::guard('web')->user();
    }

    /**
     * @return array{data:list<array<string, mixed>>, meta:array<string, int|null>}
     */
    private function paginatorPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => DigitalDocumentSubmissionResource::collection($paginator->items())->resolve(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    /**
     * @return array{data:list<array<string, mixed>>, meta:array<string, int|null>}
     */
    private function publicPaginatorPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => DigitalDocumentSubmissionPublicResource::collection($paginator->items())->resolve(),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
