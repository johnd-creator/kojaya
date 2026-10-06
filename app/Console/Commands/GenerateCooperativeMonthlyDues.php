<?php

namespace App\Console\Commands;

use App\Services\Cooperative\DuesGenerationService;
use Illuminate\Console\Command;

class GenerateCooperativeMonthlyDues extends Command
{
    protected $signature = 'cooperative:generate-monthly-dues {--period=} {--member= : Catch up one active member in the current period}';

    protected $description = 'Generate monthly cooperative dues invoices for active members.';

    public function handle(DuesGenerationService $service): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');
        $memberId = $this->option('member');
        if ($memberId !== null && (! ctype_digit((string) $memberId) || (int) $memberId < 1 || $period !== now()->format('Y-m'))) {
            $this->error('Member catch-up requires a valid member ID and the current period.');

            return self::FAILURE;
        }
        $created = $service->generateForPeriod($period, $memberId !== null ? (int) $memberId : null);

        $this->info("Generated {$created} cooperative dues invoices for {$period}.");

        return self::SUCCESS;
    }
}
