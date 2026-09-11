<?php

namespace App\Console\Commands;

use App\Models\BudgetLine;
use Illuminate\Console\Command;

class AuditBudgetProjectIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budgets:audit-project-integrity {--repair : Automatically nullify invalid cross-tenant project relationships}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and optionally repair cross-tenant BudgetLine to Project associations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mismatches = BudgetLine::query()
            ->whereNotNull('budget_lines.project_id')
            ->join('budgets', 'budgets.id', '=', 'budget_lines.budget_id')
            ->join('projects', 'projects.id', '=', 'budget_lines.project_id')
            ->whereColumn('projects.organization_id', '!=', 'budgets.organization_id')
            ->select([
                'budget_lines.id',
                'budget_lines.budget_id',
                'budgets.organization_id as budget_org',
                'budget_lines.project_id',
                'projects.organization_id as project_org',
            ])
            ->get();

        $count = $mismatches->count();

        if ($count === 0) {
            $this->info('Budget line project integrity: ALL CLEAN (0 mismatched rows found).');

            return self::SUCCESS;
        }

        $this->warn("Found {$count} cross-tenant BudgetLine project mismatches:");

        $tableData = $mismatches->map(fn ($m) => [
            'id' => (string) $m->id,
            'budget_id' => (string) $m->budget_id,
            'budget_org' => (string) $m->budget_org,
            'project_id' => (string) $m->project_id,
            'project_org' => (string) $m->project_org,
        ])->toArray();

        $this->table(['BudgetLine ID', 'Budget ID', 'Budget Org', 'Project ID', 'Project Org'], $tableData);

        if ($this->option('repair')) {
            $mismatchIds = $mismatches->pluck('id')->all();

            BudgetLine::whereIn('id', $mismatchIds)->update([
                'project_id' => null,
            ]);

            $this->info("Repaired {$count} BudgetLine records by nullifying foreign project_id.");

            return self::SUCCESS;
        }

        $this->error('Run with --repair to nullify invalid cross-tenant project relationships.');

        return self::FAILURE;
    }
}
