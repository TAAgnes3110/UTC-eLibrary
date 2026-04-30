<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewUserProfileUpdateRequest;
use App\Http\Requests\StoreUserProfileUpdateRequest;
use App\Http\Resources\UserProfileUpdateRequestResource;
use App\Models\UserProfileUpdateRequest;
use App\Services\UserProfileUpdateRequestService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class UserProfileUpdateRequestController extends Controller
{
    public function __construct(
        private UserProfileUpdateRequestService $service
    ) {}

    public function myIndex(Request $request): JsonResponse
    {
        $items = $this->service->myRequests($request->user());

        return ApiResponse::success(UserProfileUpdateRequestResource::collection($items));
    }

    public function store(StoreUserProfileUpdateRequest $request): JsonResponse
    {
        $file = $request->file('proof_image');
        if ($file === null) {
            return ApiResponse::error('Bạn cần tải ảnh minh chứng để gửi yêu cầu.', 422);
        }

        try {
            $record = $this->service->submit($request->user(), $request->validated(), $file);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (QueryException $e) {
            $errorText = strtolower((string) $e->getMessage());
            if (str_contains($errorText, 'unknown column') || str_contains($errorText, 'doesn\'t exist')) {
                return ApiResponse::error('Cơ sở dữ liệu chưa được cập nhật đầy đủ. Vui lòng chạy migration mới nhất rồi thử lại.', 500);
            }

            return ApiResponse::error('Không thể gửi yêu cầu cập nhật lúc này. Vui lòng thử lại sau.', 500);
        } catch (Throwable) {
            return ApiResponse::error('Không thể gửi yêu cầu cập nhật lúc này. Vui lòng thử lại sau.', 500);
        }
        $record->load(['requestedFaculty:id,code,name', 'requestedPeriod:id,code,name', 'reviewer:id,name,email']);

        return ApiResponse::success(new UserProfileUpdateRequestResource($record), 'Đã gửi yêu cầu cập nhật. Vui lòng chờ duyệt.', 201);
    }

    public function hideMyRequests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $affected = $this->service->hideMyRequests($request->user(), $validated['ids']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'affected' => $affected,
        ], 'Đã ẩn các yêu cầu đã chọn.');
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:newest,oldest'],
        ]);
        $items = $this->service->adminList(
            $validated['status'] ?? null,
            (int) ($validated['per_page'] ?? 20),
            $validated['search'] ?? null,
            $validated['sort_by'] ?? 'newest',
        );

        return ApiResponse::success(UserProfileUpdateRequestResource::collection($items));
    }

    public function approve(ReviewUserProfileUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $record = $this->service->approve($id, $request->input('review_note'));
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success(new UserProfileUpdateRequestResource($record), 'Đã duyệt và cập nhật thông tin người dùng.');
    }

    public function reject(ReviewUserProfileUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $record = $this->service->reject($id, $request->input('review_note'));
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success(new UserProfileUpdateRequestResource($record), 'Đã từ chối yêu cầu.');
    }

    public function adminHide(int $id): JsonResponse
    {
        try {
            $this->service->hideByAdmin($id);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success(null, 'Đã ẩn yêu cầu.');
    }
}

