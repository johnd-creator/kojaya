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

// Sanctum token hygiene
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('01:00');

// Notification transactional outbox
Schedule::command('notifications:outbox:process --limit=100')->everyThirtySeconds();
Schedule::command('notifications:whatsapp-dues-reminders --days=3')->dailyAt('09:00');

// Cooperative notification outbox delivery (exactly-once settlement notifications)
Schedule::command('cooperative:deliver-notification-outbox --limit=100')->everyMinute();

// Release stock held by abandoned member store/coffee payment intents.
Schedule::command('orders:expire-reservations --limit=500')->everyTenMinutes()->withoutOverlapping();

// Recover intents stuck in CHARGE_CREATING after provider timeout.
Schedule::command('orders:recover-stale-charges --minutes=5 --limit=50')->everyFiveMinutes()->withoutOverlapping();

// Production operations hygiene
Schedule::command('operations:prune-retention')->dailyAt('01:30');
Schedule::command('backup:database --prune')->dailyAt('02:30');
