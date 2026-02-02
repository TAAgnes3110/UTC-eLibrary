<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleOrPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $rolesOrPermission)
    {
        global $currentUser;
        if (!$currentUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn cần đăng nhập để sử dụng chức năng này.',
            ], 401);
        }
        if ($currentUser->hasRoleOrPermission($rolesOrPermission) || $currentUser->isAdmin() || $currentUser->isSuperAdmin()) {
            return $next($request);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Bạn chưa được cấp quyền để sử dụng chức năng này.',
        ], 403);
    }
}
