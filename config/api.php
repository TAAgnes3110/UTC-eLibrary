<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ghi log mọi request API (duration, status, …) vào storage/logs/api.log
    |--------------------------------------------------------------------------
    | Bật khi debug hiệu năng. Trên hosting yếu / I/O chậm, nên để false (mặc định).
    */
    'log_requests' => filter_var(env('API_LOG_REQUESTS', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | API Security: Allowed Domains
    |--------------------------------------------------------------------------
    | Danh sách domain/origin được phép gửi trong header "domain".
    | Để trống hoặc null = không kiểm tra domain. Dùng khi gọi API từ Postman/app bên ngoài.
    | Ví dụ: ['https://your-app.com', 'https://admin.your-app.com']
    */
    'allowed_domains' => array_filter(array_map('trim', explode(',', env('API_ALLOWED_DOMAINS', '')))),
];
