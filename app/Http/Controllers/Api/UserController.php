<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReadersExport;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserTrashedResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Chỉ điều hướng: gọi UserService, trả ApiResponse / UserResource.
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $typeReader = $request->input('type') === 'reader';
        $items = $this->userService->index($keyword, $typeReader);
        return ApiResponse::success(UserResource::collection($items));
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['libraryCard', 'faculty:id,code,name', 'department:id,code,name,faculty_id']);
        return ApiResponse::success(new UserResource($user));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return ApiResponse::success(new UserResource($user), __('messages.success_create'), 201);
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        $user = $this->userService->update($user, $request->validated());
        return ApiResponse::success(new UserResource($user), __('messages.success_update'));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        $this->userService->destroy($user);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function trash(Request $request): JsonResponse
    {
        $items = $this->userService->trash();
        return ApiResponse::success(UserTrashedResource::collection($items));
    }

    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->restore($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    public function forceDelete(int $id): JsonResponse
    {
        if (!$this->userService->forceDelete($id)) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã xóa vĩnh viễn.'));
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'is_active' => 'required|boolean',
        ]);
        $this->userService->updateStatus($request->ids, $request->boolean('is_active'));
        return ApiResponse::success(null, __('messages.success_update'));
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->userService->toggleStatus($id);
        if ($result === null) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success($result, __('messages.success_update'));
    }

    public function exportReaders(): BinaryFileResponse
    {
        return Excel::download(new ReadersExport(), 'danh_sach_ban_doc.xlsx');
    }

    public function updateAvatar(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::error(__('Không tìm thấy người dùng.'), 404);
        }
        $file = $request->file('avatar');
        if (!$file) {
            return ApiResponse::error(__('Vui lòng chọn một file ảnh hợp lệ.'), 422);
        }
        try {
            $result = $this->userService->updateAvatar($user, $file);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
        if ($result === null) {
            return ApiResponse::error(__('Vui lòng chọn một file ảnh hợp lệ.'), 422);
        }
        return ApiResponse::success($result, __('messages.success_update'));
    }

    /** @deprecated Dùng GET /users + GET /master-data */
    public function adminPageData(Request $request): JsonResponse
    {
        $payload = $this->userService->adminPageData(20);
        return ApiResponse::success([
            'users' => UserResource::collection($payload['users']),
            'roles' => $payload['roles'],
        ]);
    }

    /** @deprecated Dùng GET /users?type=reader + GET /master-data */
    public function readersPageData(): JsonResponse
    {
        $readers = $this->userService->readersPageData();
        return ApiResponse::success(UserResource::collection($readers));
    }
}
