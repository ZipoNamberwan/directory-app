<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:generate-report')->dailyAt('21:30');
Schedule::command('app:generate-report-market')->dailyAt('06:00');
Schedule::command('app:generate-report-market')->dailyAt('12:00');
Schedule::command('app:generate-report-market')->dailyAt('18:00');
Schedule::command('app:generate-report-market')->dailyAt('22:30');
Schedule::command('app:delete-download-business-file')->dailyAt('01:30');

// Schedule::command('app:generate-report')->everyMinute();

