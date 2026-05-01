<?php

namespace App\Services\Procurement;

use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetValidationService
{
    public function checkAvailability(array $items, string $organizationId, ?string $projectId = null, ?string $costCenter = null): array
    {
        $byGl = collect($items)->groupBy('gl_account')->map(function (Collection $rows) {
            return ['gl_account' => $rows->first()['gl_account'], 'amount' => (float) $rows->sum('amount')];
        })->values();

        $budget = Budget::query()->where('organization_id', $organizationId)->orderByDesc('year')->first();

        $lines = BudgetLine::query()
            ->where('budget_id', optional($budget)->id)
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($costCenter, fn ($q) => $q->where(function ($w) use ($costCenter) {
                $w->whereNull('cost_center')->orWhere('cost_center', $costCenter);
            }))
            ->whereIn('gl_account', $byGl->pluck('gl_account'))
            ->get()
            ->keyBy('gl_account');

        $details = [];
        $ok = true;
        foreach ($byGl as $row) {
            $line = $lines->get($row['gl_account']);
            $allocated = $line?->allocated_amount ?? 0;
            $committed = $line?->committed_amount ?? 0;
            $available = (float) $allocated - (float) $committed;
            $enough = $available >= (float) $row['amount'];
            $ok = $ok && $enough;
            $details[] = ['gl_account' => $row['gl_account'], 'requested' => (float) $row['amount'], 'available' => $available, 'enough' => $enough];
        }

        return [
            'ok' => $ok,
            'details' => $details,
            'total_requested' => (float) collect($items)->sum('amount'),
            'total_available' => (float) $lines->sum(fn ($l) => $l->allocated_amount - $l->committed_amount),
        ];
    }

    public function commit(array $items, string $organizationId, ?string $projectId = null, ?string $costCenter = null): array
    {
        return DB::transaction(function () use ($items, $organizationId, $projectId, $costCenter) {
            $byGl = collect($items)->groupBy('gl_account')->map(function (Collection $rows) {
                return ['gl_account' => $rows->first()['gl_account'], 'amount' => (float) $rows->sum('amount')];
            })->values();

            $budget = Budget::query()->where('organization_id', $organizationId)->orderByDesc('year')->lockForUpdate()->first();

            $lines = BudgetLine::query()
                ->where('budget_id', optional($budget)->id)
                ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
                ->when($costCenter, fn ($q) => $q->where(function ($w) use ($costCenter) {
                    $w->whereNull('cost_center')->orWhere('cost_center', $costCenter);
                }))
                ->whereIn('gl_account', $byGl->pluck('gl_account'))
                ->lockForUpdate()
                ->get()
                ->keyBy('gl_account');

            foreach ($byGl as $row) {
                $line = $lines->get($row['gl_account']);
                if (! $line) {
                    return ['ok' => false, 'error' => 'missing_gl'];
                }
                $available = (float) $line->allocated_amount - (float) $line->committed_amount;
                if ($available < (float) $row['amount']) {
                    return ['ok' => false, 'error' => 'insufficient_budget', 'gl_account' => $row['gl_account']];
                }
            }

            foreach ($byGl as $row) {
                $line = $lines->get($row['gl_account']);
                $line->committed_amount = (float) $line->committed_amount + (float) $row['amount'];
                $line->save();
            }

            return ['ok' => true];
        });
    }
}
