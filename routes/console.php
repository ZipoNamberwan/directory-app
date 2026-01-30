<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:delete-download-business-file')->dailyAt('00:30');
// Schedule::command('app:generate-report')->dailyAt('03:00');
// Schedule::command('app:generate-report-market')->dailyAt('03:30');
Schedule::command('app:prune-pulse')->weekly();
