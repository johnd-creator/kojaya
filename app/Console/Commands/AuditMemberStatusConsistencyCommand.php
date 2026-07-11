<?php

namespace App\Console\Commands;

use App\Models\CooperativeMember;
use Illuminate\Console\Command;

class AuditMemberStatusConsistencyCommand extends Command
{
    protected $signature = 'members:audit-status-consistency';

    protected $description = 'Audit cooperative member lifecycle status consistency without changing data.';

    public function handle(): int
    {
        $knownStatuses = ['PENDING', 'ACTIVE', 'INACTIVE', 'RESIGNED'];
        $knownValidationStatuses = [
            CooperativeMember::VALIDATION_PENDING,
            CooperativeMember::VALIDATION_PENDING_REVIEW,
            CooperativeMember::VALIDATION_ACTIVE,
            CooperativeMember::VALIDATION_INACTIVE,
            CooperativeMember::VALIDATION_REJECTED,
            CooperativeMember::VALIDATION_REVISION,
            CooperativeMember::VALIDATION_RESIGNED,
        ];

        $counts = [
            'total' => CooperativeMember::query()->count(),
            'ACTIVE/ACTIVE' => CooperativeMember::query()->where('status', 'ACTIVE')->where('validation_status', CooperativeMember::VALIDATION_ACTIVE)->count(),
            'ACTIVE/null' => CooperativeMember::query()->where('status', 'ACTIVE')->whereNull('validation_status')->count(),
            'ACTIVE/non-active-validation' => CooperativeMember::query()->where('status', 'ACTIVE')->whereNotNull('validation_status')->where('validation_status', '!=', CooperativeMember::VALIDATION_ACTIVE)->count(),
            'non-active/ACTIVE' => CooperativeMember::query()->whereIn('status', ['PENDING', 'INACTIVE', 'RESIGNED'])->where('validation_status', CooperativeMember::VALIDATION_ACTIVE)->count(),
            'unknown status' => CooperativeMember::query()->whereNotIn('status', $knownStatuses)->count(),
            'unknown validation_status' => CooperativeMember::query()->whereNotNull('validation_status')->whereNotIn('validation_status', $knownValidationStatuses)->count(),
        ];

        foreach ($counts as $label => $count) {
            $this->line(sprintf('%-32s %d', $label, $count));
        }

        $this->line('manual-review-sample-ids:');
        CooperativeMember::query()
            ->where(function ($query) use ($knownStatuses, $knownValidationStatuses): void {
                $query->whereNotIn('status', $knownStatuses)
                    ->orWhere(function ($query) use ($knownValidationStatuses): void {
                        $query->whereNotNull('validation_status')->whereNotIn('validation_status', $knownValidationStatuses);
                    })
                    ->orWhere(function ($query): void {
                        $query->where('status', 'ACTIVE')->whereNotNull('validation_status')->where('validation_status', '!=', CooperativeMember::VALIDATION_ACTIVE);
                    });
            })
            ->limit(20)
            ->pluck('id')
            ->each(fn (mixed $id): int => $this->line((string) $id));

        return self::SUCCESS;
    }
}
