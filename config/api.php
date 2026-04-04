<?php

return [

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
