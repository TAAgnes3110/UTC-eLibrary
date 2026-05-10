<?php

namespace App\Http\Controllers\Api;

use App\Exports\UserExport;
use App\Helpers\ApiResponse;
use App\Helpers\BulkZipRequestHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserListResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
     * - search_in: các cột tìm kiếm (id, tên, mã số, email, số điện thoại)
     * - status: trạng thái hoạt động (active, blocked)
     * - role_filter: các vai trò người dùng (admin, reader, staff)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search_in' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,blocked'],
            'role_filter' => ['nullable'],
        ]);
        $keyword = $request->input('keyword');
        $typeReader = $request->input('type') === 'reader';
        $perPage = (int) $request->input('per_page', 50);
        $perPage = $perPage < 1 ? 50 : min($perPage, 100);
        $searchColumns = $this->parseSearchInFilter($request);
        $status = $request->input('status');
        $roleFilter = $this->parseRoleFilter($request);
        $cacheable = ! $request->filled('keyword')
            && (int) $request->input('page', 1) <= 3
            && in_array($perPage, [15, 20, 30], true);
        if (! $cacheable) {
            $items = $this->userService->index($keyword, $typeReader, $perPage, $searchColumns, $status, $roleFilter);

            return ApiResponse::success(UserListResource::collection($items));
        }
        $cacheKey = 'api:users:index:'.md5(json_encode([
            'v' => $this->userService->adminListCacheVersion(),
            'page' => (int) $request->input('page', 1),
            'per_page' => $perPage,
            'type_reader' => $typeReader,
            'status' => (string) ($status ?? ''),
            'role_filter' => $roleFilter ?? [],
            'search_in' => $searchColumns ?? [],
        ], JSON_UNESCAPED_UNICODE));
        $payload = Cache::remember($cacheKey, now()->addSeconds(45), function () use ($keyword, $typeReader, $perPage, $searchColumns, $status, $roleFilter): array {
            $items = $this->userService->index($keyword, $typeReader, $perPage, $searchColumns, $status, $roleFilter);

            return UserListResource::collection($items)->response()->getData(true);
        });

        return ApiResponse::success($payload);
    }

    /**
     * @return list<string>|null
     */
    private function parseSearchInFilter(Request $request): ?array
    {
        if (! $request->filled('search_in')) {
            return null;
        }
        $raw = $request->input('search_in');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $allowed = ['name', 'email', 'code', 'phone'];
        $filtered = array_values(array_intersect($candidates, $allowed));

        return $filtered === [] ? null : $filtered;
    }

    /**
     * @return list<string>|null
     */
    private function parseRoleFilter(Request $request): ?array
    {
        if (! $request->filled('role_filter')) {
            return null;
        }
        $raw = $request->input('role_filter');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $roles = array_values(array_filter($candidates, static fn ($v): bool => is_string($v) && $v !== ''));

        return $roles === [] ? null : $roles;
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
