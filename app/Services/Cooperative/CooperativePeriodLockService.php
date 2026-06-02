<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeClosingChecklist;
use App\Models\CooperativePeriodLock;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CooperativePeriodLockService
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function defaultSteps(): array
    {
        return [
            ['key' => 'dues_generated', 'label' => 'Tagihan iuran bulanan sudah digenerate'],
            ['key' => 'payments_verified', 'label' => 'Pembayaran anggota sudah diverifikasi'],
            ['key' => 'bank_reconciled', 'label' => 'Rekonsiliasi bank sudah selesai'],
            ['key' => 'ledger_reviewed', 'label' => 'Ledger simpanan sudah direview'],
            ['key' => 'reports_exported', 'label' => 'Laporan operasional sudah diexport'],
        ];
    }

    public function assertUnlocked(?string $period, string $module = 'COOPERATIVE'): void
    {
        if ($period && $this->isLocked($period, $module)) {
            throw ValidationException::withMessages([
                'period' => "Periode telah dikunci ({$period}, {$module}).",
            ]);
        }
    }

    public function isLocked(string $period, string $module = 'COOPERATIVE'): bool
    {
        return CooperativePeriodLock::query()
            ->where('period', $period)
            ->where('module', $module)
            ->where('status', 'LOCKED')
            ->whereNull('unlocked_at')
            ->exists();
    }

    public function ensureChecklist(string $period, string $module = 'COOPERATIVE'): void
    {
        foreach ($this->defaultSteps() as $step) {
            CooperativeClosingChecklist::query()->firstOrCreate(
                [
                    'period' => $period,
                    'module' => $module,
                    'step_key' => $step['key'],
                ],
                [
                    'step_label' => $step['label'],
                    'status' => 'OPEN',
                ],
            );
        }
    }

    public function completeStep(string $period, string $stepKey, ?User $user, ?string $notes = null, string $module = 'COOPERATIVE'): CooperativeClosingChecklist
    {
        $this->ensureChecklist($period, $module);

        $step = CooperativeClosingChecklist::query()
            ->where('period', $period)
            ->where('module', $module)
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

    public function lock(string $period, ?User $user, ?string $reason = null, string $module = 'COOPERATIVE'): CooperativePeriodLock
    {
        $this->ensureChecklist($period, $module);

        $openSteps = CooperativeClosingChecklist::query()
            ->where('period', $period)
            ->where('module', $module)
            ->where('status', '!=', 'DONE')
            ->count();

        if ($openSteps > 0) {
            throw ValidationException::withMessages([
                'checklist' => 'All closing checklist steps must be completed before locking the period.',
            ]);
        }

        return CooperativePeriodLock::query()->updateOrCreate(
            ['period' => $period, 'module' => $module],
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

    public function unlock(string $period, ?User $user, ?string $module = 'COOPERATIVE'): CooperativePeriodLock
    {
        $lock = CooperativePeriodLock::query()
            ->where('period', $period)
            ->where('module', $module)
            ->firstOrFail();

        $lock->forceFill([
            'status' => 'UNLOCKED',
            'unlocked_at' => now(),
            'unlocked_by' => $user?->id,
        ])->save();

        return $lock;
    }
}
