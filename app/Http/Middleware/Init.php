<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\CurrentUser;
use App\Models\Customer;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Init
{
    /**
     * Khởi tạo biến global và kiểm tra đăng nhập.
     *
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        global $currentSystem, $currentCustomer, $currentUser, $currentPerson, $role_prefix, $domain, $bearer_token;

        $bearer_token = $request->bearerToken();
        $user = null;

        try {
            if ($bearer_token) {
                try {
                    $user = JWTAuth::parseToken()->authenticate();
                } catch (\Exception $e) {
                }
            }
            if (! $user && Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user();
            }
            if (! $user) {
                return ApiResponse::error(__('Bạn cần đăng nhập để tiếp tục.'), 401);
            }

            $domain = $request->headers->get('domain', request()->getHost());
            $allowedDomains = config('api.allowed_domains', []);
            if (! empty($allowedDomains) && ! $this->isDomainAllowed($domain, $allowedDomains)) {
                return ApiResponse::error(__('Domain không được phép.'), 403);
            }

            $currentPerson = $user;
            $currentUser = new CurrentUser($user);
            $currentCustomer = Customer::first() ?? (object) ['id' => 0, 'code' => 'UTC', 'name' => 'UTC Library'];
            $role_prefix = 'UTC_LIBRARY_';
            $currentSystem = (object) [
                'system' => 'LIBRARY',
                'user_id' => $user->id,
            ];

            return $next($request);
        } catch (\Exception $e) {
            return ApiResponse::error(__('Lỗi xác thực.'), 401);
        }
    }

    /**
     * Cho phép khớp vừa chuỗi đầy đủ (origin) vừa host:port — tránh 403 khi host chỉ gửi
     * "utc-lib.rf.gd" mà API_ALLOWED_DOMAINS là "https://utc-lib.rf.gd", hoặc khi header
     * "domain" bị proxy/hosting strip và Laravel fallback sang getHost().
     *
     * @param  array<int, string>  $allowedDomains
     */
    private function isDomainAllowed(string $domain, array $allowedDomains): bool
    {
        foreach ($allowedDomains as $allowed) {
            $allowed = trim($allowed);
            if ($allowed === '') {
                continue;
            }
            if ($domain === $allowed) {
                return true;
            }
            $requestKey = $this->normalizeDomainKey($domain);
            $allowedKey = $this->normalizeDomainKey($allowed);
            if ($requestKey !== null && $allowedKey !== null && strcasecmp($requestKey, $allowedKey) === 0) {
                return true;
            }
        }

        return false;
    }

    private function normalizeDomainKey(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (str_contains($value, '://')) {
            $host = parse_url($value, PHP_URL_HOST);
            if ($host === null || $host === '') {
                return null;
            }
            $port = parse_url($value, PHP_URL_PORT);

            return $port ? "{$host}:{$port}" : $host;
        }

        return $value;
    }
}
