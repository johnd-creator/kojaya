<?php

namespace App\Console\Commands;

use App\Models\CooperativeMember;
use App\Services\Cooperative\MemberStatusConsistencyReport;
use Illuminate\Console\Command;

class BackfillMemberStatusConsistencyCommand extends Command
{
    protected $signature = 'members:backfill-status-consistency {--apply : Apply only deterministic repairs} {--acknowledge : Confirm that the dry-run report was reviewed and a backup exists} {--chunk=250 : Number of rows per batch}';

    protected $description = 'Report or apply deterministic cooperative member lifecycle status repairs.';

    public function handle(MemberStatusConsistencyReport $report): int
    {
        $query = $report->deterministicRepairs();

        $candidates = $query->count();
        $this->info(sprintf('%d deterministic lifecycle rows detected.', $candidates));

        if (! $this->option('apply')) {
            $this->comment('Dry run only. Re-run with --apply after backup and report review.');

            return self::SUCCESS;
        }

        if (! $this->option('acknowledge')) {
            $this->error('Refusing to mutate status without --acknowledge after report and backup review.');

            return self::FAILURE;
        }

        $updated = 0;
        $query->orderBy('id')->chunkById((int) $this->option('chunk'), function ($members) use (&$updated): void {
            foreach ($members as $member) {
                $validationStatus = match ($member->status) {
                    'ACTIVE' => CooperativeMember::VALIDATION_ACTIVE,
                    'INACTIVE' => CooperativeMember::VALIDATION_INACTIVE,
                    'RESIGNED' => CooperativeMember::VALIDATION_RESIGNED,
                    default => null,
                };

                if ($validationStatus === null) {
                    continue;
                }

                $member->forceFill(['validation_status' => $validationStatus])->save();
                $updated++;
            }
        });

        $this->info(sprintf('%d lifecycle rows repaired.', $updated));

        $manualReview = $report->manualReviewQuery()->count();
        $this->info(sprintf('%d rows remain classified for manual review.', $manualReview));

        return $manualReview === 0 ? self::SUCCESS : self::FAILURE;
    }
}
