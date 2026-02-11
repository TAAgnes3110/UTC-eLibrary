<?php

namespace App\Http\Middleware;

use App\Helpers\CurrentUser;
use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Init
{
    public function handle(Request $request, Closure $next)
    {
        global $currentSystem, $currentCustomer, $currentUser, $currentPerson, $role_prefix, $period, $domain, $bearer_token;

        $bearer_token = $request->bearerToken();
        $user = null;

        try {
            if ($bearer_token) {
                try {
                    $user = JWTAuth::parseToken()->authenticate();
                } catch (\Exception $e) {
                }
            }
            if (!$user && Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user();
            }
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vui lòng đăng nhập để tiếp tục.',
                ], 401);
            }
            $domain = $request->headers->get('domain', request()->getHost());
            $period = $request->headers->get('period', date('Y') . '-' . (date('Y') + 1));
            $currentPerson = $user;
            $currentUser = new CurrentUser($user);
            $currentCustomer = Customer::first() ?? (object)['id' => 0, 'code' => 'UTC', 'name' => 'UTC Library'];
            $role_prefix = 'UTC_LIBRARY_';
            $currentSystem = (object)[
                'system' => 'LIBRARY',
                'user_id' => $user->id,
            ];

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi xác thực: ' . $e->getMessage(),
            ], 401);
        }
    }
}
