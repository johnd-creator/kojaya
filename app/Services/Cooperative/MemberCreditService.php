<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosMemberCreditPayment;
use App\Models\PosTransaction;
use App\Models\User;
use App\Support\Money\MinorAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberCreditService
{
    public function recordPayment(
        CooperativeMember $member,
        int|float|string $amount,
        ?User $receiver,
        ?string $referenceNo = null,
        ?string $notes = null,
        ?string $paidAt = null,
    ): PosMemberCreditPayment {
        $amountMinor = MinorAmount::fromDecimal($amount);
        $amountDecimal = MinorAmount::toDecimalString($amountMinor);
        $outstandingBalanceMinor = MinorAmount::fromDecimal($member->outstanding_balance ?? '0.00');

        if ($amountMinor <= 0) {
            throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
        }

        if ($amountMinor > $outstandingBalanceMinor) {
            throw ValidationException::withMessages([
                'amount' => 'Pembayaran melebihi saldo terutang anggota.',
            ]);
        }

        return DB::transaction(function () use ($member, $amountDecimal, $receiver, $referenceNo, $notes, $paidAt) {
            $payment = PosMemberCreditPayment::query()->create([
                'cooperative_member_id' => $member->id,
                'received_by' => $receiver?->id,
                'reference_no' => $referenceNo,
                'amount' => $amountDecimal,
                'paid_at' => $paidAt ?: now()->toDateString(),
                'notes' => $notes,
            ]);

            $member->decrement('outstanding_balance', $amountDecimal);

            CooperativeLedgerEntry::query()->create([
                'cooperative_member_id' => $member->id,
                'source_type' => PosMemberCreditPayment::class,
                'source_id' => $payment->id,
                'entry_type' => 'POS_MEMBER_CREDIT_PAYMENT',
                'ledger_scope' => 'POS',
                'debit' => 0,
                'credit' => $amountDecimal,
                'description' => 'Bayar cicilan kredit anggota: Rp '.$amountDecimal,
                'posted_at' => $payment->paid_at->toDateString(),
            ]);

            return $payment->load('member', 'receiver');
        });
    }

    public function isCreditTransaction(PosTransaction $transaction): bool
    {
        return $transaction->payments->contains(fn ($p) => $p->payment_method === 'MEMBER_CREDIT');
    }
}
