<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:generate-report')->dailyAt('06:15');
Schedule::command('app:generate-report-market')->dailyAt('06:00');
Schedule::command('app:generate-report-market')->dailyAt('12:00');
Schedule::command('app:generate-report-market')->dailyAt('18:00');
Schedule::command('app:generate-report-market')->dailyAt('22:30');
Schedule::command('app:delete-download-business-file')->dailyAt('01:30');
Schedule::command('app:prune-pulse')->weekly();
