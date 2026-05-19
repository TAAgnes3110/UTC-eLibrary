<?php

/**
 * Thông báo in-app + lịch artisan (nhắc mượn trả, quá hạn, …).
 * Chỉnh .env trên server — không cần sửa code khi đổi giờ chạy / số ngày báo trước.
 */
$pollMs = (int) env('NOTIFICATION_UI_POLL_INTERVAL_MS', 30_000);

return [

    /*
    |--------------------------------------------------------------------------
    | UI — làm mới danh sách thông báo (polling, không WebSocket)
    |--------------------------------------------------------------------------
    | Mặc định 30s: đủ nhanh cho badge, tránh gọi API quá dày.
    | Giới hạn 10s–120s để tránh cấu hình nhầm.
    */
    'ui_poll_interval_ms' => max(10_000, min(120_000, $pollMs)),

    /*
    |--------------------------------------------------------------------------
    | Nhắc phiếu sắp đến hạn (lệnh loans:notify-due-soon)
    |--------------------------------------------------------------------------
    | Ví dụ: 2 = hôm nay 26/4 thì nhắc phiếu hạn 28/4.
    | Dedupe theo ngày — mỗi phiếu tối đa một thông báo chưa đọc / ngày.
    */
    'loan_due_soon_enabled' => filter_var(env('LOAN_DUE_SOON_NOTIFY_ENABLED', true), FILTER_VALIDATE_BOOL),

    'loan_due_soon_days_before' => max(1, min(14, (int) env('LOAN_DUE_SOON_DAYS_BEFORE', 2))),

    /*
    |--------------------------------------------------------------------------
    | Lịch chạy tự động (routes/console.php)
    |--------------------------------------------------------------------------
    | Docker EC2: container `scheduler` chạy `php artisan schedule:work` — không cần crontab.
    | Máy bare-metal: * * * * * cd /path && php artisan schedule:run
    */
    'schedule_enabled' => [
        'trash_purge' => filter_var(env('SCHEDULE_TRASH_PURGE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'periods_sync_admission' => filter_var(env('SCHEDULE_PERIODS_SYNC_ENABLED', true), FILTER_VALIDATE_BOOL),
        'library_cards_prune_pending' => filter_var(env('SCHEDULE_LIBRARY_CARDS_PRUNE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'library_cards_sync_overdue_locks' => filter_var(env('SCHEDULE_LIBRARY_CARDS_OVERDUE_LOCKS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'loans_sync_overdue' => filter_var(env('SCHEDULE_LOANS_SYNC_OVERDUE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'loans_notify_due_soon' => filter_var(env('SCHEDULE_LOANS_NOTIFY_DUE_SOON_ENABLED', true), FILTER_VALIDATE_BOOL),
        'storage_sync_quantities' => filter_var(env('SCHEDULE_STORAGE_SYNC_ENABLED', true), FILTER_VALIDATE_BOOL),
        'digital_orders_expire' => filter_var(env('SCHEDULE_DIGITAL_ORDERS_EXPIRE_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],

    'schedule_at' => [
        'trash_purge' => env('SCHEDULE_TRASH_PURGE_AT', '02:00'),
        'periods_sync_admission' => env('SCHEDULE_PERIODS_SYNC_AT', '03:10'),
        'library_cards_prune_pending' => env('SCHEDULE_LIBRARY_CARDS_PRUNE_AT', '04:00'),
        'library_cards_sync_overdue_locks' => env('SCHEDULE_LIBRARY_CARDS_OVERDUE_LOCKS_AT', '01:30'),
        'loans_sync_overdue' => env('SCHEDULE_LOANS_SYNC_OVERDUE_AT', '06:05'),
        'loans_notify_due_soon' => env('SCHEDULE_LOANS_NOTIFY_DUE_SOON_AT', '07:00'),
        'storage_sync_quantities' => env('SCHEDULE_STORAGE_SYNC_AT', '01:10'),
    ],

    'trash_purge_days' => max(1, (int) env('SCHEDULE_TRASH_PURGE_DAYS', 30)),

    'digital_orders_expire_every_minutes' => max(1, min(60, (int) env('SCHEDULE_DIGITAL_ORDERS_EXPIRE_EVERY_MINUTES', 5))),

];
