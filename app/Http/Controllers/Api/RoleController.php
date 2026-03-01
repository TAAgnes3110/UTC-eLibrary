<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Chỉ điều hướng: gọi RoleService, trả ApiResponse.
 */
class RoleController extends Controller
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->roleService->index());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);
        $role = $this->roleService->store($validated);
        return ApiResponse::success($role, __('Thêm vai trò thành công.'), 201);
    }

    public function show($id): JsonResponse
    {
        $role = $this->roleService->show($id);
        return ApiResponse::success($role);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);
        $role = $this->roleService->update($role, $validated);
        return ApiResponse::success($role);
    }

    public function destroy($id): JsonResponse
    {
        $this->roleService->destroy($id);
        return ApiResponse::success(null, __('Xóa vai trò thành công.'));
    }

    public function addPermission(Request $request, $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);
        $role = Role::findOrFail($id);
        $role = $this->roleService->addPermission($role, $request->permission);
        return ApiResponse::success($role, __('Đã gán quyền cho vai trò.'));
    }

    public function removePermission(Request $request, $id): JsonResponse
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name',
        ]);
        $role = Role::findOrFail($id);
        $role = $this->roleService->removePermission($role, $request->permission);
        return ApiResponse::success($role, __('Đã thu hồi quyền khỏi vai trò.'));
    }
}
