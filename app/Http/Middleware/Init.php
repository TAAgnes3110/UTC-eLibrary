<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\CurrentUser;
use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Init
{
    /**
     * Khởi tạo biến global và kiểm tra đăng nhập.
     *
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|mixed
     */
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
                return ApiResponse::error(__('Vui lòng đăng nhập để tiếp tục.'), 401);
            }

            $domain = $request->headers->get('domain', request()->getHost());
            $allowedDomains = config('api.allowed_domains', []);
            if (!empty($allowedDomains) && !in_array($domain, $allowedDomains, true)) {
                return ApiResponse::error(__('Domain không được phép gọi API.'), 403);
            }

            $period = $request->headers->get('period', date('Y') . '-' . (date('Y') + 1));
            if (config('api.validate_period_format', false)) {
                $pattern = config('api.period_pattern', '/^\d{4}-\d{4}$/');
                if (!preg_match($pattern, $period)) {
                    return ApiResponse::error(__('Định dạng period không hợp lệ (ví dụ: 2025-2026).'), 403);
                }
            }
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
            return ApiResponse::error(__('Lỗi xác thực: ') . $e->getMessage(), 401);
        }
    }
}
