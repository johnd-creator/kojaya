<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;

class CooperativeOpeningBalanceService
{
    public function sync(CooperativeMember $member, mixed $amount): void
    {
        if ($amount === null || $amount === '') {
            return;
        }

        $amount = (float) $amount;

        if ($amount <= 0) {
            $member->ledgerEntries()
                ->where('entry_type', 'OPENING_BALANCE')
                ->whereNull('cooperative_payment_id')
                ->delete();

            return;
        }

        CooperativeLedgerEntry::query()->updateOrCreate(
            [
                'cooperative_member_id' => $member->id,
                'cooperative_payment_id' => null,
                'entry_type' => 'OPENING_BALANCE',
            ],
            [
                'source_type' => CooperativeMember::class,
                'source_id' => $member->id,
                'debit' => 0,
                'credit' => $amount,
                'period' => null,
                'description' => 'Saldo awal simpanan anggota dari data manual',
                'posted_at' => $member->joined_at ?: now()->toDateString(),
            ],
        );
    }
}
