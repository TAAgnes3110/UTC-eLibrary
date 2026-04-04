<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('trash:purge --days=30')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('periods:sync-admission')
    ->dailyAt('03:10')
    ->withoutOverlapping();
