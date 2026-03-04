<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Security: Allowed Domains
    |--------------------------------------------------------------------------
    | Danh sách domain/origin được phép gửi trong header "domain".
    | Để trống hoặc null = không kiểm tra domain. Dùng khi gọi API từ Postman/app bên ngoài.
    | Ví dụ: ['https://succinic-unshaped-nery.ngrok-free.dev', 'https://your-app.com']
    */
    'allowed_domains' => array_filter(array_map('trim', explode(',', env('API_ALLOWED_DOMAINS', '')))),

    /*
    |--------------------------------------------------------------------------
    | API Security: Validate period format
    |--------------------------------------------------------------------------
    | Nếu true, header "period" phải khớp pattern (vd: 2025-2026).
    | Pattern: năm-năm, 4 chữ số mỗi bên.
    */
    'validate_period_format' => (bool) env('API_VALIDATE_PERIOD', false),

    /*
    |--------------------------------------------------------------------------
    | Default period format (regex)
    |--------------------------------------------------------------------------
    */
    'period_pattern' => '/^\d{4}-\d{4}$/',
];
