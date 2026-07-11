<?php

namespace App\Console\Commands;

use App\Services\Cooperative\MemberStatusConsistencyReport;
use Illuminate\Console\Command;

class AuditMemberStatusConsistencyCommand extends Command
{
    protected $signature = 'members:audit-status-consistency';

    protected $description = 'Audit cooperative member lifecycle status consistency without changing data.';

    public function handle(MemberStatusConsistencyReport $report): int
    {
        $counts = $report->counts();

        foreach ($counts as $label => $count) {
            $this->line(sprintf('%-32s %d', $label, $count));
        }

        $this->line('manual-review-sample-ids:');
        $report->manualReviewQuery()
            ->limit(20)
            ->pluck('id')
            ->each(function (mixed $id): void {
                $this->line((string) $id);
            });

        return self::SUCCESS;
    }
}
