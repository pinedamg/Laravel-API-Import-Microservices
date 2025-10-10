<?php

use App\Jobs\SyncProviderEventsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Dynamically set the schedule for SyncProviderEventsJob
$scheduleJob = Schedule::job(new SyncProviderEventsJob(Config::get('services.provider.url')));

$scheduleFrequency = env('EVENT_SYNC_SCHEDULE', 'hourly');

switch ($scheduleFrequency) {
    case 'everyMinute':
        $scheduleJob->everyMinute();
        break;
    case 'everyFiveMinutes':
        $scheduleJob->everyFiveMinutes();
        break;
    case 'everyFifteenMinutes':
        $scheduleJob->everyFifteenMinutes();
        break;
    case 'everyThirtyMinutes':
        $scheduleJob->everyThirtyMinutes();
        break;
    case 'hourly':
    default:
        $scheduleJob->hourly();
        break;
}
