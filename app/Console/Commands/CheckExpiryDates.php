<?php

namespace App\Console\Commands;

use App\Jobs\SendExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiryDates extends Command
{
    protected $signature = 'expiry:check {--days= : Check specific days}';

    protected $description = 'Check for expiring contracts, certificates, and MCU schedules';

    public function handle()
    {
        $this->info('Checking for expiring items...');
        Log::info('Expiry check started');

        dispatch(new SendExpiryReminder);

        $this->info('Expiry check completed successfully!');
        Log::info('Expiry check completed');

        return Command::SUCCESS;
    }
}
