<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler – one cron entry "php artisan schedule:run" every minute
|--------------------------------------------------------------------------
|
| Shared hosting (e.g. netcup Webhosting) cannot keep a queue worker running.
| The scheduler starts a short-lived worker every minute instead; it stops
| when the queue is empty, so nothing keeps running between the cron calls.
|
*/

Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=1 --timeout=240')
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command('scans:fail-stale')->everyFiveMinutes();
