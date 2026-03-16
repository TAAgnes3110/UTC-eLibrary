<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReadersExport;
use App\Exports\SimpleTableExport;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Danh sách tài khoản người dùng.
     *
     * @param Request $request Request chứa các tham số lọc:
     *                         - keyword: từ khóa tìm kiếm (id, tên, mã số, email, số điện thoại)
     *                         - type: 'reader' nếu chỉ lấy bạn đọc, bỏ trống để lấy tất cả
     * @return JsonResponse
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
     * @param User $user Bản ghi người dùng cần xem (model binding theo id)
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['libraryCard', 'faculty:id,code,name', 'department:id,code,name,faculty_id']);
        return ApiResponse::success(new UserResource($user));
    }

    /**
     * Tạo mới tài khoản người dùng.
     *
     * @param UserRequest $request Dữ liệu đã được validate cho tài khoản mới
     * @return JsonResponse
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return ApiResponse::success(new UserResource($user), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin tài khoản người dùng.
     *
     * @param UserRequest $request Dữ liệu đã được validate cho tài khoản
     * @param int $id ID người dùng cần cập nhật
     * @return JsonResponse
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        unset($request->id,$request->created_at, $request->updated_at);
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        $user = $this->userService->update($user, $request->validated());
        return ApiResponse::success(new UserResource($user), __('messages.success_update'));
    }

    /**
     * Xóa mềm một tài khoản người dùng.
     *
     * @param int $id ID người dùng cần xóa
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        $this->userService->destroy($user);
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Danh sách các tài khoản đã xóa mềm.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trash(Request $request): JsonResponse
    {
        $items = $this->userService->trash();
        return ApiResponse::success(UserResource::collection($items));
    }

    /**
     * Khôi phục một tài khoản đã xóa mềm.
     *
     * @param int $id ID người dùng cần khôi phục
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->restore($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    /**
     * Xóa vĩnh viễn một tài khoản người dùng.
     *
     * @param int $id ID người dùng cần xóa vĩnh viễn
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        if (!$this->userService->forceDelete($id)) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        return ApiResponse::success(null, __('messages.success_force_delete'));
    }

    /**
     * Cập nhật trạng thái hoạt động cho nhiều tài khoản.
     *
     * @param Request $request Request chứa:
     *                         - ids: mảng ID người dùng
     *                         - is_active: true nếu kích hoạt, false nếu khóa
     * @return JsonResponse
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
     * @param int $id ID người dùng cần đổi trạng thái
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->userService->toggleStatus($id);
        if ($result === null) {
            return ApiResponse::notFound(__('messages.error_404'));
        }
        return ApiResponse::success($result, __('messages.success_update'));
    }

    /**
     * Xuất danh sách tài khoản người dùng ra file Excel.
     * @param Request $request 
     * @return BinaryFileResponse
     */
    public function exportUsers(Request $request): BinaryFileResponse
    {
        $ids = $request->input('ids');
        if (is_array($ids)) {
            $ids = array_filter($ids, fn ($v) => is_numeric($v));
        } else {
            $ids = null;
        }
        return $this->userService->exportUsers($ids);
    }

    /**
     * Cập nhật ảnh đại diện cho một tài khoản người dùng.
     *
     * @param Request $request Request chứa file ảnh ở field 'avatar'
     * @param int $id ID người dùng cần cập nhật ảnh đại diện
     * @return JsonResponse
     */
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

    /**
     * Cập nhật ảnh đại diện hàng loạt từ file .zip.
     *
     * File zip phải chứa các ảnh có tên trùng với mã người dùng (code),
     * mỗi ảnh sẽ được gán vào tài khoản tương ứng.
     *
     * @param Request $request Request chứa file zip ở field 'file'
     * @return JsonResponse
     */
    public function bulkUpdateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:zip',
        ]);

        $file = $request->file('file');
        if (!$file) {
            return ApiResponse::error(__('Vui lòng chọn một file .zip hợp lệ.'), 422);
        }

        try {
            $summary = $this->userService->bulkUpdateAvatarFromZip($file);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        } catch (\Throwable) {
            return ApiResponse::error(__('Không thể xử lý file zip.'), 422);
        }
        if (($summary['updated'] ?? 0) === 0) {
            return ApiResponse::error(
                __('Không có ảnh đại diện nào được cập nhật. Vui lòng kiểm tra lại định dạng file, số lượng và tên file (trùng mã người dùng).'),
                422
            );
        }
        return ApiResponse::success($summary, __('messages.success_update'));
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
