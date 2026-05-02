<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalDocumentSubmissionResource;
use App\Services\DigitalDocumentSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class DigitalDocumentSubmissionController extends Controller
{
    public function __construct(
        private DigitalDocumentSubmissionService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $request->validate([
            'status' => ['sometimes', 'nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $items = $this->service->list($request->user(), $request->input('status'));

        return ApiResponse::success(DigitalDocumentSubmissionResource::collection($items));
    }

    public function publicIndex(): JsonResponse
    {
        $items = $this->service->listPublicApproved();

        return ApiResponse::success(DigitalDocumentSubmissionResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tải tài liệu số.'), 401);
        }

        // PDF: chỉ tin phần mở rộng .pdf (+ kích thước). Trình duyệt thường gửi application/octet-stream; rule mimes/pdf hay File::types dễ từ chối oan.
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author_names' => ['required', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'file' => [
                'required',
                'file',
                'max:10240',
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
            'author_names.required' => __('Vui lòng nhập tác giả tài liệu.'),
            'file.max' => __('File PDF không được vượt quá 10MB.'),
            'cover_image.image' => __('Ảnh bìa phải là file ảnh hợp lệ.'),
            'cover_image.mimes' => __('Ảnh bìa chỉ nhận JPEG, PNG hoặc WebP.'),
            'cover_image.max' => __('Ảnh bìa không được vượt quá 5MB.'),
        ]);

        $item = $this->service->submitAsReaderPending(
            $request->user(),
            $request->only(['title', 'author_names', 'description']),
            $request->file('file'),
            $request->file('cover_image')
        );

        return ApiResponse::success(
            new DigitalDocumentSubmissionResource($item->load(['submitter:id,name,email', 'reviewer:id,name'])),
            __('Đã gửi tài liệu số, vui lòng chờ duyệt.'),
            201
        );
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        if (! $request->user()) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $request->validate([
            'review_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $item = $this->service->approve($request->user(), $id, $request->input('review_note'));

        return ApiResponse::success(
            new DigitalDocumentSubmissionResource($item),
            __('Duyệt tài liệu số thành công.')
        );
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        if (! $request->user()) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $request->validate([
            'review_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $item = $this->service->reject($request->user(), $id, $request->input('review_note'));

        return ApiResponse::success(
            new DigitalDocumentSubmissionResource($item),
            __('Đã từ chối tài liệu số.')
        );
    }

    public function hideMine(Request $request, int $id): JsonResponse
    {
        if (! $request->user()) {
            return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
        }

        $this->service->hideFromSubmitterList($request->user(), $id);

        return ApiResponse::success(
            null,
            __('Đã ẩn khỏi danh sách của bạn. Thủ thư vẫn xử lý trên hệ thống.')
        );
    }
}
