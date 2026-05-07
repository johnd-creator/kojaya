<?php

namespace App\Services\Finance;

use App\Models\CooperativeClosingChecklist;
use App\Models\CooperativePeriodLock;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FinanceClosingService
{
    public function defaultSteps(): array
    {
        return [
            ['key' => 'journal_reviewed', 'label' => 'Jurnal bulanan sudah direview'],
            ['key' => 'bank_reconciled', 'label' => 'Rekonsiliasi bank sudah selesai'],
            ['key' => 'efaktur_submitted', 'label' => 'e-Faktur sudah disubmit ke DJP'],
            ['key' => 'trial_balance_reviewed', 'label' => 'Neraca saldo sudah direview'],
            ['key' => 'reports_generated', 'label' => 'Laporan keuangan bulanan sudah digenerate'],
        ];
    }

    public function assertUnlocked(?string $period): void
    {
        if ($period && $this->isLocked($period)) {
            throw ValidationException::withMessages([
                'period' => "Period {$period} is locked for FINANCE.",
            ]);
        }
    }

    public function isLocked(string $period): bool
    {
        return CooperativePeriodLock::query()
            ->where('period', $period)
            ->where('module', 'FINANCE')
            ->where('status', 'LOCKED')
            ->whereNull('unlocked_at')
            ->exists();
    }

    public function ensureChecklist(string $period): void
    {
        foreach ($this->defaultSteps() as $step) {
            CooperativeClosingChecklist::query()->firstOrCreate(
                [
                    'period' => $period,
                    'module' => 'FINANCE',
                    'step_key' => $step['key'],
                ],
                [
                    'step_label' => $step['label'],
                    'status' => 'OPEN',
                ],
            );
        }
    }

    public function completeStep(string $period, string $stepKey, ?User $user, ?string $notes = null): CooperativeClosingChecklist
    {
        $this->ensureChecklist($period);

        $step = CooperativeClosingChecklist::query()
            ->where('period', $period)
            ->where('module', 'FINANCE')
            ->where('step_key', $stepKey)
            ->firstOrFail();

        $step->forceFill([
            'status' => 'DONE',
            'notes' => $notes,
            'completed_at' => now(),
            'completed_by' => $user?->id,
        ])->save();

        return $step;
    }

    public function lock(string $period, ?User $user, ?string $reason = null): CooperativePeriodLock
    {
        $this->ensureChecklist($period);

        $openSteps = CooperativeClosingChecklist::query()
            ->where('period', $period)
            ->where('module', 'FINANCE')
            ->where('status', '!=', 'DONE')
            ->count();

        if ($openSteps > 0) {
            throw ValidationException::withMessages([
                'checklist' => 'All closing checklist steps must be completed before locking the period.',
            ]);
        }

        return CooperativePeriodLock::query()->updateOrCreate(
            ['period' => $period, 'module' => 'FINANCE'],
            [
                'status' => 'LOCKED',
                'reason' => $reason,
                'locked_at' => now(),
                'locked_by' => $user?->id,
                'unlocked_at' => null,
                'unlocked_by' => null,
            ],
        );
    }

    public function unlock(string $period, ?User $user): CooperativePeriodLock
    {
        $lock = CooperativePeriodLock::query()
            ->where('period', $period)
            ->where('module', 'FINANCE')
            ->firstOrFail();

        $lock->forceFill([
            'status' => 'UNLOCKED',
            'unlocked_at' => now(),
            'unlocked_by' => $user?->id,
        ])->save();

        return $lock;
    }
}
