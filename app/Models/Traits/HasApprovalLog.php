<?php

namespace App\Models\Traits;

use App\Models\ApprovalLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasApprovalLog
{
    public function approvalLogs(): MorphMany
    {
        return $this->morphMany(ApprovalLog::class, 'subject');
    }

    public function logApproval(
        ?string $fromStatus,
        string $toStatus,
        ?User $actor = null,
        ?string $note = null,
    ): ApprovalLog {
        return ApprovalLog::query()->create([
            'subject_type' => static::class,
            'subject_id' => (string) $this->getKey(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'approved_by' => $actor?->id,
            'note' => $note,
        ]);
    }

    public function approvalLogItems(): Collection
    {
        return $this->approvalLogs()
            ->with('approvedBy:id,name')
            ->orderBy('created_at')
            ->get();
    }
}
