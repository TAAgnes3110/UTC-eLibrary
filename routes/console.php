<?php

use Illuminate\Support\Facades\Schedule;

$enabled = config('notifications.schedule_enabled', []);
$at = config('notifications.schedule_at', []);

if ($enabled['trash_purge'] ?? true) {
    $days = (int) config('notifications.trash_purge_days', 30);
    Schedule::command("trash:purge --days={$days}")
        ->dailyAt($at['trash_purge'] ?? '02:00')
        ->withoutOverlapping();
}

if ($enabled['periods_sync_admission'] ?? true) {
    Schedule::command('periods:sync-admission')
        ->dailyAt($at['periods_sync_admission'] ?? '03:10')
        ->withoutOverlapping();
}

if ($enabled['library_cards_prune_pending'] ?? true) {
    Schedule::command('library-cards:prune-stale-pending-payment')
        ->dailyAt($at['library_cards_prune_pending'] ?? '04:00')
        ->withoutOverlapping();
}

if ($enabled['library_cards_sync_overdue_locks'] ?? true) {
    Schedule::command('library-cards:sync-overdue-locks')
        ->dailyAt($at['library_cards_sync_overdue_locks'] ?? '01:30')
        ->withoutOverlapping();
}

if ($enabled['loans_sync_overdue'] ?? true) {
    Schedule::command('loans:sync-overdue')
        ->dailyAt($at['loans_sync_overdue'] ?? '06:05')
        ->withoutOverlapping();
}

if (($enabled['loans_notify_due_soon'] ?? true) && config('notifications.loan_due_soon_enabled', true)) {
    Schedule::command('loans:notify-due-soon')
        ->dailyAt($at['loans_notify_due_soon'] ?? '07:00')
        ->withoutOverlapping();
}

if ($enabled['storage_sync_quantities'] ?? true) {
    Schedule::command('storage:sync-quantities')
        ->dailyAt($at['storage_sync_quantities'] ?? '01:10')
        ->withoutOverlapping();
}

if ($enabled['digital_orders_expire'] ?? true) {
    $minutes = (int) config('notifications.digital_orders_expire_every_minutes', 5);
    Schedule::command('digital-orders:expire-pending')
        ->cron('*/'.$minutes.' * * * *')
        ->withoutOverlapping();
}
