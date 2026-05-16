<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('trash:purge --days=30')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('periods:sync-admission')
    ->dailyAt('03:10')
    ->withoutOverlapping();

Schedule::command('library-cards:prune-stale-pending-payment')
    ->dailyAt('04:00')
    ->withoutOverlapping();

Schedule::command('library-cards:sync-overdue-locks')
    ->dailyAt('01:30')
    ->withoutOverlapping();

Schedule::command('loans:sync-overdue')
    ->dailyAt('06:05')
    ->withoutOverlapping();

Schedule::command('loans:notify-due-soon')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Schedule::command('storage:sync-quantities')
    ->dailyAt('01:10')
    ->withoutOverlapping();

Schedule::command('digital-orders:expire-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping();
