<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Danh sách tất cả permission.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success(Permission::all());
    }

    /**
     * Tạo permission mới.
     *
     * @param Request $request Body: name (string, unique).
     * @return JsonResponse 201 + permission.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'api']);
        return ApiResponse::success($permission, __('Thêm quyền thành công.'), 201);
    }
}
