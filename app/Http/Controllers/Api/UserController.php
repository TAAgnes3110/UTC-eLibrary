<?php

namespace App\Http\Controllers\Api;

use App\Exports\UserExport;
use App\Helpers\ApiResponse;
use App\Helpers\BulkZipRequestHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Danh sách tài khoản người dùng.
     *
     * @param  Request  $request  Request chứa các tham số lọc:
     *                            - keyword: từ khóa tìm kiếm (id, tên, mã số, email, số điện thoại)
     *                            - type: 'reader' nếu chỉ lấy bạn đọc, bỏ trống để lấy tất cả
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $typeReader = $request->input('type') === 'reader';
        $items = $this->userService->index($keyword, $typeReader);

        return ApiResponse::success(UserResource::collection($items));
    }

    /**
     * Xem chi tiết một tài khoản người dùng.
     *
     * @param  User  $user  Bản ghi người dùng cần xem (model binding theo id)
     */
    public function show(User $user): JsonResponse
    {
        $user->load([
            'libraryCard.period:id,code,name,start_year,end_year',
            'libraryCard.payment.collector:id,name',
            'faculty:id,code,name',
            'department:id,code,name,faculty_id',
            'period:id,code,name,start_year,end_year',
        ]);

        return ApiResponse::success(new UserResource($user));
    }

    /**
     * Tạo mới tài khoản người dùng.
     *
     * @param  UserRequest  $request  Dữ liệu đã được validate cho tài khoản mới
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return ApiResponse::success(new UserResource($user), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin tài khoản người dùng.
     *
     * @param  UserRequest  $request  Dữ liệu đã được validate cho tài khoản
     * @param  int  $id  ID người dùng cần cập nhật
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        unset($request->id,$request->created_at, $request->updated_at);
        $user = User::find($id);
        if (! $user) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $user = $this->userService->update($user, $request->validated());

        return ApiResponse::success(new UserResource($user), __('messages.success_update'));
    }

    /**
     * Xóa mềm một tài khoản người dùng.
     *
     * @param  int  $id  ID người dùng cần xóa
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return ApiResponse::notFound();
        }
        $this->userService->destroy($user);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Danh sách các tài khoản đã xóa mềm.
     */
    public function trash(Request $request): JsonResponse
    {
        $items = $this->userService->trash();

        return ApiResponse::success(UserResource::collection($items));
    }

    /**
     * Khôi phục một tài khoản đã xóa mềm.
     *
     * @param  int  $id  ID người dùng cần khôi phục
     */
    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->restore($id);
        if (! $user) {
            return ApiResponse::notFound();
        }

        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    public function restoreMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        $restored = $this->userService->restoreMany($request->input('ids', []));

        return ApiResponse::success(['restored' => $restored], __('messages.success_restore'));
    }

    /**
     * Xóa vĩnh viễn một tài khoản người dùng.
     *
     * @param  int  $id  ID người dùng cần xóa vĩnh viễn
     */
    public function forceDelete(int $id): JsonResponse
    {
        if (! $this->userService->forceDelete($id)) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success(null, __('messages.success_force_delete'));
    }

    public function forceDeleteMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        $deleted = $this->userService->forceDeleteMany($request->input('ids', []));

        return ApiResponse::success(['deleted' => $deleted], __('messages.success_force_delete'));
    }

    /**
     * Cập nhật trạng thái hoạt động cho nhiều tài khoản.
     *
     * @param  Request  $request  Request chứa:
     *                            - ids: mảng ID người dùng
     *                            - is_active: true nếu kích hoạt, false nếu khóa
     */
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

    /**
     * Đổi trạng thái hoạt động của một tài khoản (khóa / mở khóa).
     *
     * @param  int  $id  ID người dùng cần đổi trạng thái
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->userService->toggleStatus($id);
        if ($result === null) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success($result, __('messages.success_update'));
    }

    public function updateAvatar(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (! $user) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $file = $request->file('avatar');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn một file ảnh hợp lệ.'), 422);
        }
        try {
            $result = $this->userService->updateAvatar($user, $file);

            return ApiResponse::success($result, __('messages.success_update'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function bulkUpdateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:zip',
        ]);
        $file = $request->file('file');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn một file .zip hợp lệ.'), 422);
        }
        $onlyUserIds = BulkZipRequestHelper::parseFilterIds($request);
        try {
            $summary = $this->userService->bulkUpdateAvatarFromZip($file, $onlyUserIds);

            return ApiResponse::success($summary, __('messages.success_update'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::error(__('Không thể xử lý file zip.'), 422);
        }
    }

    public function exportUsers(Request $request): StreamedResponse
    {
        $ids = $request->input('ids');
        $ids = is_array($ids) ? array_values(array_filter($ids, static fn ($v) => is_numeric($v))) : null;

        return UserExport::stream($ids);
    }

    /** @deprecated Dùng GET /users + GET /master-data */
    public function adminList(Request $request): JsonResponse
    {
        $payload = $this->userService->adminList(20);

        return ApiResponse::success([
            'users' => UserResource::collection($payload['users']),
            'roles' => $payload['roles'],
        ]);
    }

    /** @deprecated Dùng GET /users?type=reader + GET /master-data */
    public function readers(): JsonResponse
    {
        $readers = $this->userService->readers();

        return ApiResponse::success(UserResource::collection($readers));
    }
}
