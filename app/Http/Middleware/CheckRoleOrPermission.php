<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias route: role_or_permission — kiểm tra Spatie role/permission qua $currentUser (JWT).
 */
class CheckRoleOrPermission
{
    /**
     * Cho qua nếu $currentUser->hasRoleOrPermission($roleOrPermission), ngược lại 401/403.
     *
     * @param  string|null  $roleOrPermission
     */
    public function handle(Request $request, Closure $next, $roleOrPermission = null): Response
    {
        global $currentUser;

        if (! $currentUser) {
            return ApiResponse::error(__('Bạn cần đăng nhập để sử dụng chức năng này.'), 401);
        }

        if (empty($roleOrPermission)) {
            return $next($request);
        }

        if ($currentUser->hasRoleOrPermission($roleOrPermission)) {
            return $next($request);
        }

        return ApiResponse::error(__('Không đủ quyền.'), 403);
    }
}
