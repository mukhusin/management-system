<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Refresh tenders on a schedule
|--------------------------------------------------------------------------
|
| Runs `php artisan tenders:fetch` every 6 hours. For this to actually
| fire, the OS needs to invoke Laravel's scheduler once a minute — see
| the "Scheduling" section of the README. Tweak the cadence with
| TENDERS_FETCH_CRON in .env (any cron expression); leave it unset for
| the every-6-hours default.
|
*/
Schedule::command('tenders:fetch')
    ->cron(config('tenders.fetch_cron'))
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tenders-fetch.log'))
    ->onFailure(function () {
        Log::error('Scheduled tenders:fetch failed — see storage/logs/tenders-fetch.log');
    });
