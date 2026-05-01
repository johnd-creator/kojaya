<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;

class BudgetValidator
{
    public function checkAvailability(string $organizationId, string $glAccount, float $amount, ?string $projectId = null, ?string $costCenter = null): array
    {
        $budget = Budget::where('organization_id', $organizationId)->orderByDesc('year')->first();
        if (! $budget) {
            return ['ok' => false, 'reason' => 'No budget configured'];
        }
        $lineQuery = BudgetLine::where('budget_id', $budget->id)->where('gl_account', $glAccount);
        if ($projectId) {
            $lineQuery->where('project_id', $projectId);
        }
        if ($costCenter) {
            $lineQuery->where('cost_center', $costCenter);
        }
        $line = $lineQuery->first();
        if (! $line) {
            return ['ok' => false, 'reason' => 'No budget line found for GL'];
        }
        $available = (float) $line->allocated_amount - (float) $line->committed_amount - (float) $line->realized_amount;
        if ($amount <= $available) {
            return ['ok' => true, 'available' => $available];
        }

        return ['ok' => false, 'available' => $available, 'reason' => 'Insufficient budget'];
    }
}
