<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\PosDailyClosing;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosDailyClosingService
{
    public function closeDay(string $date, User $user): PosDailyClosing
    {
        $existing = $this->findByDate($date);
        if ($existing && $existing->is_locked) {
            throw ValidationException::withMessages([
                'date' => 'Hari ini sudah ditutup dan terkunci.',
            ]);
        }

        return DB::transaction(function () use ($date, $user, $existing): PosDailyClosing {
            $summary = $this->summaryForDate($date);
            $paymentSummary = $this->paymentSummaryForDate($date);
            $memberCreditOutstanding = $this->memberCreditOutstanding();

            $closing = $existing ?? new PosDailyClosing;
            $closing->forceFill([
                'closing_date' => $date,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'transaction_count' => $summary['transaction_count'],
                'gross_sales' => $summary['gross_sales'],
                'total_discount' => $summary['total_discount'],
                'total_void' => $summary['total_void'],
                'total_return' => $summary['total_return'],
                'net_sales' => $summary['net_sales'],
                'member_credit_outstanding' => $memberCreditOutstanding,
                'payment_summary' => $paymentSummary,
                'is_locked' => true,
            ])->save();

            $this->postClosingJournal($closing, $user);

            return $closing->refresh();
        });
    }

    /**
     * @return array<string, float|int>
     */
    public function summaryForDate(string $date): array
    {
        $base = PosTransaction::query()->whereDate('sold_at', $date);

        $completed = (clone $base)->where('status', 'COMPLETED');
        $voided = (clone $base)->where('status', 'VOIDED');
        $returns = PosReturn::query()->whereDate('returned_at', $date);

        $grossSales = (float) (clone $completed)->sum('total_amount');
        $totalDiscount = (float) (clone $completed)->sum('discount_amount');
        $totalVoid = (float) (clone $voided)->sum('total_amount');
        $totalReturn = (float) (clone $returns)->sum('total_amount');
        $netSales = round($grossSales - $totalReturn, 2);

        return [
            'transaction_count' => (clone $completed)->count(),
            'gross_sales' => $grossSales,
            'total_discount' => $totalDiscount,
            'total_void' => $totalVoid,
            'total_return' => $totalReturn,
            'net_sales' => $netSales,
        ];
    }

    /**
     * @return array<int, array<string, float|int|string>>
     */
    public function paymentSummaryForDate(string $date): array
    {
        return PosTransaction::query()
            ->where('status', 'COMPLETED')
            ->whereDate('sold_at', $date)
            ->with('payments')
            ->get()
            ->flatMap(fn ($trx) => $trx->payments)
            ->groupBy('payment_method')
            ->map(fn ($group, $method): array => [
                'method' => $method,
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ])
            ->values()
            ->all();
    }

    public function memberCreditOutstanding(): float
    {
        return (float) DB::table('cooperative_members')->sum('outstanding_balance');
    }

    public function isLocked(string $date): bool
    {
        $closing = $this->findByDate($date);

        return (bool) ($closing?->is_locked);
    }

    private function findByDate(string $date): ?PosDailyClosing
    {
        return PosDailyClosing::query()
            ->whereDate('closing_date', $date)
            ->first();
    }

    private function postClosingJournal(PosDailyClosing $closing, User $user): void
    {
        $revenue = (float) $closing->net_sales;
        if ($revenue <= 0) {
            return;
        }

        $userMemberId = DB::table('cooperative_members')->where('user_id', $user->id)->value('id');
        if (! $userMemberId) {
            return;
        }

        CooperativeLedgerEntry::query()->create([
            'cooperative_member_id' => $userMemberId,
            'source_type' => PosDailyClosing::class,
            'source_id' => $closing->id,
            'entry_type' => 'POS_DAILY_CLOSING',
            'ledger_scope' => 'POS',
            'debit' => 0,
            'credit' => $revenue,
            'description' => "Penjualan POS netto tgl {$closing->closing_date}",
            'posted_at' => $closing->closing_date->toDateString(),
        ]);
    }
}
