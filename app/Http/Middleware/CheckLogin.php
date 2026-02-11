<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        global $currentUser;

        if (isset($currentUser) && $currentUser->id > 0) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Bạn cần đăng nhập để sử dụng chức năng này.',
        ], 401);
    }
}
