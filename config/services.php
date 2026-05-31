<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'azure' => [
        'client_id' => env('AZURE_CLIENT_ID'),
        'client_secret' => env('AZURE_CLIENT_SECRET'),
        'redirect' => env('AZURE_REDIRECT_URI', 'http://localhost:8000/auth/microsoft/callback'),
        // common = mọi tài khoản Microsoft (cá nhân + công việc/trường), không ràng buộc domain.
        'tenant' => env('AZURE_TENANT_ID', 'common'),
        'proxy' => env('PROXY'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sepay' => [
        'api_token' => env('SEPAY_API_TOKEN'),
        'api_base_url' => env('SEPAY_API_BASE_URL', 'https://userapi.sepay.vn/v2'),
        'sync_lookback_days' => (int) env('SEPAY_SYNC_LOOKBACK_DAYS', 3),
        'webhook_secret' => env('SEPAY_WEBHOOK_SECRET'),
    ],

    /** Đơn tài liệu số pending quá số ngày này sẽ bị xóa tự động (chỉ khi chưa thanh toán). */
    'digital_orders' => [
        'pending_max_age_days' => (int) env('DIGITAL_ORDER_PENDING_MAX_AGE_DAYS', 3),
        /** Số đơn chờ thanh toán tối đa mỗi tài khoản (chống spam QR). */
        'pending_max_per_user' => (int) env('DIGITAL_ORDER_PENDING_MAX_PER_USER', 3),
    ],

    /** Công cụ CLI tạo PDF xem trước khi FPDI không đọc được PDF nén (Word, InDesign…). */
    'pdf_preview' => [
        'qpdf_binary' => env('QPDF_BINARY'),
        'ghostscript_binary' => env('GHOSTSCRIPT_BINARY'),
        'pdftoppm_binary' => env('PDFTOPPM_BINARY'),
        /** qpdf / Ghostscript / pdftoppm — PDF lớn trên VPS nên 600+ */
        'process_timeout' => (int) env('PDF_PREVIEW_PROCESS_TIMEOUT', 180),
        'page_count_timeout' => (int) env('PDF_PREVIEW_PAGE_COUNT_TIMEOUT', 120),
    ],

];
