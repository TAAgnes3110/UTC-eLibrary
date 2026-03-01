<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chỉ điều hướng: gọi PermissionService, trả ApiResponse.
 */
class PermissionController extends Controller
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->permissionService->index());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);
        $permission = $this->permissionService->store($validated);
        return ApiResponse::success($permission, __('Thêm quyền thành công.'), 201);
    }
}
