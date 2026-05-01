<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Maintenance PM Triggers schedule
Schedule::command('maintenance:process')->dailyAt('02:00');
// Optional quick check every hour (no creation), useful for monitoring
Schedule::command('maintenance:process --check')->hourly();

// Expiry check schedule - run daily at 08:00
Schedule::command('expiry:check')->dailyAt('08:00');

// Cooperative monthly dues generation
Schedule::command('cooperative:generate-monthly-dues')->monthlyOn(1, '03:00');
