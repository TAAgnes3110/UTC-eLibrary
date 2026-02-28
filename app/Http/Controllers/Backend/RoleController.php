<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Controller quản lý vai trò (Spatie Permission) và gán permission cho role.
 *
 * @todo Dùng FormRequest riêng cho store/update; thống nhất format response (status/messages).
 */
class RoleController extends Controller
{
    /**
     * Danh sách tất cả role.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success(Role::all());
    }

    /**
     * Tạo role mới.
     *
     * @param Request $request Body: name (string, unique).
     * @return JsonResponse 201 + role.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'api']);
        return ApiResponse::success($role, __('Thêm vai trò thành công.'), 201);
    }

    /**
     * Chi tiết role kèm permissions.
     *
     * @param int|string $id ID role.
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->load('permissions');
        return ApiResponse::success($role);
    }

    /**
     * Cập nhật tên role.
     *
     * @param Request $request Body: name (string, unique trừ role hiện tại).
     * @param int|string $id ID role.
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $validated['name']]);
        return ApiResponse::success($role);
    }

    /**
     * Xóa role.
     *
     * @param int|string $id ID role.
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return ApiResponse::success(null, __('Xóa vai trò thành công.'));
    }

    /**
     * Gán permission cho role.
     *
     * @param Request $request Body: permission (name, exists:permissions,name).
     * @param int|string $id ID role.
     * @return JsonResponse role + permissions.
     */
    public function addPermission(Request $request, $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        $role->givePermissionTo($request->permission);
        return ApiResponse::success($role->load('permissions'), __('Đã gán quyền cho vai trò.'));
    }

    /**
     * Thu hồi permission khỏi role.
     *
     * @param Request $request Body: permission (name).
     * @param int|string $id ID role.
     * @return JsonResponse role + permissions.
     */
    public function removePermission(Request $request, $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        $role->revokePermissionTo($request->permission);
        return ApiResponse::success($role->load('permissions'), __('Đã thu hồi quyền khỏi vai trò.'));
    }
}
