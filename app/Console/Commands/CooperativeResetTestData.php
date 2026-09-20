<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cooperative\CooperativeTestDataResetService;
use App\Support\SeedSafety\SeederEnvironmentGuard;
use App\Support\SeedSafety\SeederExecutionProfile;
use Illuminate\Console\Command;
use LogicException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

class CooperativeResetTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cooperative:reset-test-data
        {--dry-run : Show fixture-owned records without modifying the database}
        {--with-edge-cases : Also load SEED-06 TEST_ONLY_INVALID_FIXTURE data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and reseed deterministic cooperative test data.';

    /**
     * Execute the console command.
     */
    public function handle(CooperativeTestDataResetService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $withEdgeCases = (bool) $this->option('with-edge-cases');

        try {
            $env = SeederEnvironmentGuard::assertEnvironmentConsistency();
        } catch (LogicException $e) {
            $this->error($e->getMessage());

            return SymfonyCommand::FAILURE;
        }

        // 1. Strict environment guard for reset tooling
        if (! SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::LocalTestFixture, $env)) {
            $allowedEnvironments = SeederEnvironmentGuard::allowedEnvironmentsFor(SeederExecutionProfile::LocalTestFixture);
            $this->error("SEED-07 reset tooling is unavailable in this environment [{$env}].");
            $this->line('Allowed environments: '.implode(', ', $allowedEnvironments));

            return SymfonyCommand::FAILURE;
        }

        // 2. Strict guard for --with-edge-cases option (only testing & playwright)
        if ($withEdgeCases && ! SeederEnvironmentGuard::isAllowed(SeederExecutionProfile::TestOnlyFixture, $env)) {
            $this->error("The --with-edge-cases option is only allowed in testing and playwright environments (current: [{$env}]).");
            $this->line('Command aborted with zero mutations.');

            return SymfonyCommand::FAILURE;
        }

        // 3. Handle Dry Run mode
        if ($dryRun) {
            $this->info('SEED-07 — Deterministic Reset / Reseed Tooling (DRY RUN)');
            $this->line("Environment: {$env}");
            $this->newLine();

            $plan = $service->dryRun($withEdgeCases);
            $counts = $plan['records_to_remove'];

            $this->line('Fixture-owned records that WOULD be removed:');
            $this->line(sprintf('  - Users (seed/demo):             %d', $counts['users']));
            $this->line(sprintf('  - Members (DEV-KOP/demo):        %d', $counts['members']));
            $this->line(sprintf('  - Social accounts:               %d', $counts['social_accounts']));
            $this->line(sprintf('  - Personal access tokens:        %d', $counts['personal_access_tokens']));
            $this->line(sprintf('  - Member store accounts:         %d', $counts['store_accounts']));
            $this->line(sprintf('  - Member store ledger entries:   %d', $counts['store_ledger_entries']));
            $this->line(sprintf('  - Cooperative loans:             %d', $counts['loans']));
            $this->line(sprintf('  - Cooperative dues invoices:     %d', $counts['dues_invoices']));
            $this->line(sprintf('  - Cooperative payments:          %d', $counts['payments']));
            $this->line(sprintf('  - Cooperative receipts:          %d', $counts['receipts']));
            $this->line(sprintf('  - Cooperative ledger entries:    %d', $counts['cooperative_ledger_entries']));
            $this->line(sprintf('  - POS transactions:              %d', $counts['pos_transactions']));
            $this->line(sprintf('  - POS canonical products:        %d', $counts['pos_products']));
            $this->newLine();

            $this->line('Reseed plan:');
            foreach ($plan['reseed_plan'] as $seederName) {
                $this->line("  ✓ {$seederName}");
            }
            $this->line('Edge cases: '.($withEdgeCases ? 'enabled (P11)' : 'disabled'));
            $this->newLine();

            $this->info('No changes were made.');

            return SymfonyCommand::SUCCESS;
        }

        // 4. Handle Live Reset & Reseed
        $this->info('SEED-07 — Deterministic Reset / Reseed Tooling');
        $this->line("Environment: {$env}");
        $this->newLine();

        try {
            $result = $service->reset($withEdgeCases);

            $this->line('Reset:');
            $this->line('  ✓ Canonical users & demo staff');
            $this->line('  ✓ Canonical members & legacy demo members');
            $this->line('  ✓ Financial fixture rows (dues, payments, receipts, ledgers)');
            $this->line('  ✓ Store accounts, ledgers & POS transactions');
            $this->line('  ✓ Seed user runtime tokens & social accounts');
            $this->newLine();

            $this->line('Reseed:');
            foreach ($result['reseeded'] as $seederName) {
                $this->line("  ✓ {$seederName}");
            }
            $this->line('Edge cases: '.($withEdgeCases ? 'enabled (P11 BLOCKED_UNKNOWN)' : 'disabled'));
            $this->newLine();

            $this->info('SEED-07 reset complete.');

            return SymfonyCommand::SUCCESS;
        } catch (Throwable $e) {
            $this->error('SEED-07 reset failed: '.$e->getMessage());

            return SymfonyCommand::FAILURE;
        }
    }
}
