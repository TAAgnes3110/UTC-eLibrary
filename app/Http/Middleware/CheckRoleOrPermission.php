<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\CurrentUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        // Luôn đồng bộ từ user hiện tại (session/JWT) — tránh global $currentUser còn từ request trước (đặc biệt trong test).
        $user = $request->user();
        $currentUser = $user !== null ? new CurrentUser($user) : null;

        if (! $currentUser) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error(__('Bạn cần đăng nhập để sử dụng chức năng này.'), 401);
            }

            return redirect()->route('login');
        }

        if (empty($roleOrPermission)) {
            return $next($request);
        }

        if ($currentUser->hasRoleOrPermission($roleOrPermission)) {
            return $next($request);
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return ApiResponse::error(__('Không đủ quyền.'), 403);
        }

        abort(403, __('Không đủ quyền.'));
    }
}
