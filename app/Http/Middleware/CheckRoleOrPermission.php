<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleOrPermission
{
    public function handle(Request $request, Closure $next, $roleOrPermission = null): Response
    {
        global $currentUser;

        if (!$currentUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn cần đăng nhập để sử dụng chức năng này.',
            ], 401);
        }

        if (empty($roleOrPermission)) {
            return $next($request);
        }

        if ($currentUser->hasRoleOrPermission($roleOrPermission)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Bạn chưa được cấp quyền để sử dụng chức năng này.',
        ], 403);
    }
}
