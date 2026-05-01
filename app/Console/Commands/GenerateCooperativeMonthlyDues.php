<?php

namespace App\Console\Commands;

use App\Services\Cooperative\DuesGenerationService;
use Illuminate\Console\Command;

class GenerateCooperativeMonthlyDues extends Command
{
    protected $signature = 'cooperative:generate-monthly-dues {--period=}';

    protected $description = 'Generate monthly cooperative dues invoices for active members.';

    public function handle(DuesGenerationService $service): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');
        $created = $service->generateForPeriod($period);

        $this->info("Generated {$created} cooperative dues invoices for {$period}.");

        return self::SUCCESS;
    }
}
