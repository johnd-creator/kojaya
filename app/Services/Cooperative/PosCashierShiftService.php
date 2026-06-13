<?php

namespace App\Services\Cooperative;

use App\Models\PosAuditLog;
use App\Models\PosCashierShift;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCashierShiftService
{
    public function openShift(User $cashier, float $openingCash, ?int $locationId = null, ?string $notes = null): PosCashierShift
    {
        $existing = PosCashierShift::query()
            ->where('cashier_id', $cashier->id)
            ->where('status', PosCashierShift::STATUS_OPEN)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'shift' => 'Kasir masih memiliki shift terbuka. Tutup shift terlebih dahulu.',
            ]);
        }

        return DB::transaction(function () use ($cashier, $openingCash, $locationId, $notes): PosCashierShift {
            $shift = PosCashierShift::query()->create([
                'shift_no' => $this->nextShiftNo(),
                'cashier_id' => $cashier->id,
                'pos_inventory_location_id' => $locationId,
                'shift_date' => now()->toDateString(),
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'status' => PosCashierShift::STATUS_OPEN,
                'notes' => $notes,
            ]);

            $this->logEvent('shift.opened', $cashier, $shift, ['opening_cash' => $openingCash]);

            return $shift;
        });
    }

    public function closeShift(PosCashierShift $shift, float $closingCash, ?string $notes = null): PosCashierShift
    {
        if ($shift->status !== PosCashierShift::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'shift' => 'Shift sudah ditutup.',
            ]);
        }

        return DB::transaction(function () use ($shift, $closingCash, $notes): PosCashierShift {
            $stats = $this->computeShiftStats($shift);

            $shift->forceFill([
                'closing_cash' => $closingCash,
                'expected_cash' => $stats['expected_cash'],
                'cash_difference' => round($closingCash - $stats['expected_cash'], 2),
                'transaction_count' => $stats['transaction_count'],
                'total_sales' => $stats['total_sales'],
                'total_cash_sales' => $stats['total_cash_sales'],
                'closed_at' => now(),
                'status' => PosCashierShift::STATUS_CLOSED,
                'notes' => $notes ?? $shift->notes,
            ])->save();

            $this->logEvent('shift.closed', null, $shift, [
                'closing_cash' => $closingCash,
                'expected_cash' => $stats['expected_cash'],
                'difference' => $shift->cash_difference,
            ]);

            return $shift->refresh();
        });
    }

    public function getOpenShift(User $cashier): ?PosCashierShift
    {
        return PosCashierShift::query()
            ->where('cashier_id', $cashier->id)
            ->where('status', PosCashierShift::STATUS_OPEN)
            ->first();
    }

    /**
     * @return array{transaction_count:int,total_sales:float,total_cash_sales:float,expected_cash:float}
     */
    public function computeShiftStats(PosCashierShift $shift): array
    {
        $base = PosTransaction::query()
            ->where('cashier_id', $shift->cashier_id)
            ->where('pos_cashier_shift_id', $shift->id)
            ->where('status', 'COMPLETED');

        $totalSales = (float) (clone $base)->sum('total_amount');
        $totalCashSales = (float) (clone $base)
            ->whereHas('payments', fn ($q) => $q->where('payment_method', 'CASH'))
            ->with('payments')
            ->get()
            ->sum(fn (PosTransaction $trx) => (float) $trx->payments->where('payment_method', 'CASH')->sum('amount'));

        $transactionCount = (clone $base)->count();

        $expectedCash = (float) $shift->opening_cash + $totalCashSales;

        return [
            'transaction_count' => $transactionCount,
            'total_sales' => round($totalSales, 2),
            'total_cash_sales' => round($totalCashSales, 2),
            'expected_cash' => round($expectedCash, 2),
        ];
    }

    private function nextShiftNo(): string
    {
        return 'SHF-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }

    private function logEvent(string $event, ?User $user, PosCashierShift $shift, array $payload): void
    {
        PosAuditLog::query()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'entity_type' => PosCashierShift::class,
            'entity_id' => $shift->id,
            'severity' => PosAuditLog::SEVERITY_INFO,
            'payload' => $payload,
        ]);
    }
}
