<?php

namespace App\Services\Cooperative;

use App\Enums\CooperativeShuPeriodStatus;
use App\Models\CooperativeShuPeriod;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ShuRevisionService
{
    public function requestRevision(CooperativeShuPeriod $period, string $reason, User $actor): CooperativeShuPeriod
    {
        if (! in_array($period->status, [CooperativeShuPeriodStatus::Closed, CooperativeShuPeriodStatus::ClosedRevised], true)) {
            throw ValidationException::withMessages([
                'period' => ['Only closed SHU periods can be reopened for revision.'],
            ]);
        }

        $fromStatus = $period->status;

        $period->update([
            'status' => CooperativeShuPeriodStatus::Revision,
            'revision_reason' => $reason,
            'revision_requested_by' => $actor->id,
            'revision_requested_at' => now(),
        ]);

        $period->logApproval($fromStatus->value, CooperativeShuPeriodStatus::Revision->value, $actor, $reason);

        return $period->refresh();
    }
}
