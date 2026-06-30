<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosMemberCreditPayment;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberCreditService
{
    public function recordPayment(
        CooperativeMember $member,
        float $amount,
        ?User $receiver,
        ?string $referenceNo = null,
        ?string $notes = null,
        ?string $paidAt = null,
    ): PosMemberCreditPayment {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
        }

        if ($amount > (float) $member->outstanding_balance + 0.005) {
            throw ValidationException::withMessages([
                'amount' => 'Pembayaran melebihi saldo terutang anggota.',
            ]);
        }

        return DB::transaction(function () use ($member, $amount, $receiver, $referenceNo, $notes, $paidAt) {
            $payment = PosMemberCreditPayment::query()->create([
                'cooperative_member_id' => $member->id,
                'received_by' => $receiver?->id,
                'reference_no' => $referenceNo,
                'amount' => $amount,
                'paid_at' => $paidAt ?: now()->toDateString(),
                'notes' => $notes,
            ]);

            $member->decrement('outstanding_balance', $amount);

            CooperativeLedgerEntry::query()->create([
                'cooperative_member_id' => $member->id,
                'source_type' => PosMemberCreditPayment::class,
                'source_id' => $payment->id,
                'entry_type' => 'POS_MEMBER_CREDIT_PAYMENT',
                'ledger_scope' => 'POS',
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Bayar cicilan kredit anggota: Rp '.number_format($amount, 0, ',', '.'),
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
