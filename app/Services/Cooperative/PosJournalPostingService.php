<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\PosCashierShift;
use App\Models\PosReturn;
use App\Models\PosTransaction;

class PosJournalPostingService
{
    public function postSale(PosTransaction $transaction): ?CooperativeLedgerEntry
    {
        $amount = (float) $transaction->total_amount;
        if ($amount <= 0) {
            return null;
        }

        return $this->firstOrCreateEntry(
            PosTransaction::class,
            $transaction->id,
            'POS_SALE',
            [
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'ledger_scope' => 'POS',
                'debit' => 0,
                'credit' => $amount,
                'description' => "Penjualan POS {$transaction->transaction_no}",
                'posted_at' => $transaction->sold_at?->toDateString() ?? now()->toDateString(),
            ],
        );
    }

    public function postCogs(PosTransaction $transaction): ?CooperativeLedgerEntry
    {
        $cogs = 0.0;
        foreach ($transaction->items()->with('product')->get() as $item) {
            $cogs += (float) $item->cost_price * (int) $item->quantity;
        }
        $cogs = round($cogs, 2);

        if ($cogs <= 0) {
            return null;
        }

        return $this->firstOrCreateEntry(
            PosTransaction::class,
            $transaction->id,
            'POS_COGS',
            [
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'ledger_scope' => 'POS',
                'debit' => $cogs,
                'credit' => 0,
                'description' => "HPP penjualan POS {$transaction->transaction_no}",
                'posted_at' => $transaction->sold_at?->toDateString() ?? now()->toDateString(),
            ],
        );
    }

    public function postReturn(PosReturn $return): ?CooperativeLedgerEntry
    {
        $transaction = $return->transaction ?? $return->posTransaction;
        $amount = (float) $return->total_amount;
        if ($amount <= 0) {
            return null;
        }

        // POS_RETURN follows the long-standing member-ledger convention:
        // `credit = $amount` represents the cooperative refunding/crediting
        // the member. This is consistent with POS_MEMBER_CREDIT_PAYMENT
        // (member paying off debt) and POS_MEMBER_CREDIT_VOID (cooperative
        // re-stating member credit). See docs/decisions.md ADR-008.
        $memberReturn = $this->firstOrCreateEntry(
            PosReturn::class,
            $return->id,
            'POS_RETURN',
            [
                'cooperative_member_id' => $transaction?->cooperative_member_id,
                'ledger_scope' => 'POS',
                'debit' => 0,
                'credit' => $amount,
                'description' => "Retur POS {$return->return_no}",
                'posted_at' => $return->returned_at?->toDateString() ?? now()->toDateString(),
            ],
        );

        // POS_RETURN_REVERSAL is the accounting contra-revenue entry. It is
        // posted without a member counterparty so reports can isolate the
        // revenue reversal while preserving the member-ledger view above.
        $this->firstOrCreateEntry(
            PosReturn::class,
            $return->id,
            'POS_RETURN_REVERSAL',
            [
                'cooperative_member_id' => null,
                'ledger_scope' => 'POS',
                'debit' => $amount,
                'credit' => 0,
                'description' => "Kontra-revenue retur POS {$return->return_no}",
                'posted_at' => $return->returned_at?->toDateString() ?? now()->toDateString(),
            ],
        );

        return $memberReturn;
    }

    public function postMemberCredit(PosTransaction $transaction): ?CooperativeLedgerEntry
    {
        $amount = (float) $transaction->payments
            ->where('payment_method', 'MEMBER_CREDIT')
            ->sum('amount');

        if ($amount <= 0 || ! $transaction->cooperative_member_id) {
            return null;
        }

        return $this->firstOrCreateEntry(
            PosTransaction::class,
            $transaction->id,
            'POS_MEMBER_CREDIT',
            [
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'ledger_scope' => 'POS',
                'debit' => $amount,
                'credit' => 0,
                'description' => "Piutang anggota POS {$transaction->transaction_no}",
                'posted_at' => $transaction->sold_at?->toDateString() ?? now()->toDateString(),
            ],
        );
    }

    /**
     * Posts reversing entries for a voided transaction:
     * - POS_SALE_REVERSAL: cancels POS_SALE on the member ledger
     * - POS_COGS_REVERSAL: cancels POS_COGS (no member)
     * - POS_MEMBER_CREDIT_REVERSAL: cancels POS_MEMBER_CREDIT (reduces piutang)
     */
    public function postVoidReversal(PosTransaction $transaction): void
    {
        $saleAmount = (float) $transaction->total_amount;
        if ($saleAmount > 0) {
            $this->firstOrCreateEntry(
                PosTransaction::class,
                $transaction->id,
                'POS_SALE_REVERSAL',
                [
                    'cooperative_member_id' => $transaction->cooperative_member_id,
                    'ledger_scope' => 'POS',
                    'debit' => $saleAmount,
                    'credit' => 0,
                    'description' => "Void penjualan POS {$transaction->transaction_no}",
                    'posted_at' => $transaction->sold_at?->toDateString() ?? now()->toDateString(),
                ],
            );
        }

        $cogs = 0.0;
        foreach ($transaction->items()->with('product')->get() as $item) {
            $cogs += (float) $item->cost_price * (int) $item->quantity;
        }
        $cogs = round($cogs, 2);

        if ($cogs > 0) {
            $this->firstOrCreateEntry(
                PosTransaction::class,
                $transaction->id,
                'POS_COGS_REVERSAL',
                [
                    'cooperative_member_id' => null,
                    'ledger_scope' => 'POS',
                    'debit' => 0,
                    'credit' => $cogs,
                    'description' => "Void HPP POS {$transaction->transaction_no}",
                    'posted_at' => $transaction->sold_at?->toDateString() ?? now()->toDateString(),
                ],
            );
        }

        $creditAmount = (float) $transaction->payments
            ->where('payment_method', 'MEMBER_CREDIT')
            ->sum('amount');
        if ($creditAmount > 0 && $transaction->cooperative_member_id) {
            $this->firstOrCreateEntry(
                PosTransaction::class,
                $transaction->id,
                'POS_MEMBER_CREDIT_REVERSAL',
                [
                    'cooperative_member_id' => $transaction->cooperative_member_id,
                    'ledger_scope' => 'POS',
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'description' => "Void piutang anggota POS {$transaction->transaction_no}",
                    'posted_at' => $transaction->sold_at?->toDateString() ?? now()->toDateString(),
                ],
            );
        }
    }

    public function postShiftDifference(int $cashierShiftId, float $difference): ?CooperativeLedgerEntry
    {
        if (abs($difference) < 0.01) {
            return null;
        }

        return $this->firstOrCreateEntry(
            PosCashierShift::class,
            $cashierShiftId,
            'POS_SHIFT_DIFF',
            [
                'cooperative_member_id' => null,
                'ledger_scope' => 'POS',
                'debit' => $difference < 0 ? abs($difference) : 0,
                'credit' => $difference > 0 ? $difference : 0,
                'description' => $difference < 0
                    ? "Selisih kas negatif shift #{$cashierShiftId}"
                    : "Selisih kas positif shift #{$cashierShiftId}",
                'posted_at' => now()->toDateString(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function firstOrCreateEntry(string $sourceType, int $sourceId, string $entryType, array $attributes): CooperativeLedgerEntry
    {
        $existing = CooperativeLedgerEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('entry_type', $entryType)
            ->first();

        if ($existing) {
            return $existing;
        }

        $attributes = array_merge([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'entry_type' => $entryType,
        ], $attributes);

        return CooperativeLedgerEntry::query()->create($attributes);
    }
}
