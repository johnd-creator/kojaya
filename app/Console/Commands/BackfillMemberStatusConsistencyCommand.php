<?php

namespace App\Console\Commands;

use App\Models\CooperativeMember;
use Illuminate\Console\Command;

class BackfillMemberStatusConsistencyCommand extends Command
{
    protected $signature = 'members:backfill-status-consistency {--apply : Apply only the deterministic, documented repairs} {--chunk=250 : Number of rows per batch}';

    protected $description = 'Report or apply deterministic cooperative member lifecycle status repairs.';

    public function handle(): int
    {
        $query = CooperativeMember::query()
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('status', 'ACTIVE')->whereNull('validation_status');
                })->orWhere(function ($query): void {
                    $query->where('status', 'INACTIVE')->where('validation_status', CooperativeMember::VALIDATION_ACTIVE);
                })->orWhere(function ($query): void {
                    $query->where('status', 'RESIGNED')->where('validation_status', CooperativeMember::VALIDATION_ACTIVE);
                });
            });

        $candidates = $query->count();
        $this->info(sprintf('%d deterministic lifecycle rows detected.', $candidates));

        if (! $this->option('apply')) {
            $this->comment('Dry run only. Re-run with --apply after backup and report review.');

            return self::SUCCESS;
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

        return self::SUCCESS;
    }
}
