<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberOpeningBalanceBatch;

class CooperativeOpeningBalanceService
{
    /**
     * Sinkronkan entry ledger OPENING_BALANCE legacy (saldo awal ringkasan
     * yang ditulis langsung dari form anggota sebelum wizard saldo awal
     * tersedia).
     *
     * Kunci updateOrCreate menyertakan `source_type` dan `source_id`
     * bernilai model `CooperativeMember` sehingga entry ini **tidak akan**
     * menimpa entry OPENING_BALANCE yang dihasilkan wizard
     * (`source_type = CooperativeMemberOpeningBalanceLine`).
     */
    public function sync(CooperativeMember $member, mixed $amount): void
    {
        if ($amount === null || $amount === '') {
            return;
        }

        $amount = (float) $amount;

        $legacyKeys = [
            'cooperative_member_id' => $member->id,
            'source_type' => CooperativeMember::class,
            'source_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
        ];

        if ($amount <= 0) {
            CooperativeLedgerEntry::query()
                ->where($legacyKeys)
                ->whereNull('cooperative_payment_id')
                ->delete();

            return;
        }

        // Jangan tulis legacy entry bila wizard sudah pernah posting batch,
        // supaya ledger wizard tetap menjadi source of truth.
        if (CooperativeMemberOpeningBalanceBatch::query()
            ->where('cooperative_member_id', $member->id)
            ->whereIn('status', ['POSTED', 'DRAFT'])
            ->exists()) {
            return;
        }

        CooperativeLedgerEntry::query()->updateOrCreate(
            $legacyKeys,
            [
                'ledger_scope' => 'SAVINGS',
                'cooperative_payment_id' => null,
                'debit' => 0,
                'credit' => $amount,
                'period' => null,
                'description' => 'Saldo awal simpanan anggota (legacy, ringkasan manual).',
                'posted_at' => $member->joined_at ?: now()->toDateString(),
            ],
        );
    }
}
