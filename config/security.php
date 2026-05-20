<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security response headers (XSS, clickjacking, MIME sniffing)
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'enabled' => filter_var(env('SECURITY_HEADERS', true), FILTER_VALIDATE_BOOL),
        'frame_options' => env('SECURITY_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API — ẩn khỏi truy cập trình duyệt trực tiếp (production)
    |--------------------------------------------------------------------------
    | SPA luôn gửi X-Requested-With + Accept: application/json.
    | Gõ /api/v1/... trên thanh địa chỉ → 404 (không lộ danh sách endpoint).
    */
    'api' => [
        'hide_browser_access' => filter_var(
            env('API_HIDE_BROWSER_ACCESS', env('APP_ENV', 'production') !== 'local'),
            FILTER_VALIDATE_BOOL
        ),
        'minimal_health' => filter_var(
            env('API_MINIMAL_HEALTH', env('APP_ENV', 'production') !== 'local'),
            FILTER_VALIDATE_BOOL
        ),
    ],

];
