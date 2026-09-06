<?php

namespace App\Services\Cooperative;

use App\Enums\PaymentSettlementStatus;
use App\Exceptions\OrganizationScopeException;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\PosMemberCreditPayment;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use App\Support\Money\MinorAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberCreditService
{
    public function __construct(
        private readonly OrganizationScopeService $organizationScope,
    ) {}

    public function recordPayment(
        CooperativeMember $member,
        int|float|string $amount,
        User $receiver,
        ?string $referenceNo = null,
        ?string $notes = null,
        ?string $paidAt = null,
    ): PosMemberCreditPayment {
        $this->organizationScope->assertVisible($receiver, $member);

        return DB::transaction(function () use ($member, $amount, $receiver, $referenceNo, $notes, $paidAt) {
            $lockedMember = CooperativeMember::query()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->organizationScope->assertVisible($receiver, $lockedMember);

            try {
                $amountMinor = MinorAmount::fromDecimal($amount);
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
            }
            $amountDecimal = MinorAmount::toDecimalString($amountMinor);
            $lockedBalanceMinor = MinorAmount::fromDecimal($lockedMember->outstanding_balance ?? '0.00');

            if ($amountMinor <= 0) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
            }

            if ($lockedBalanceMinor <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Anggota tidak memiliki saldo terutang untuk dibayar.',
                ]);
            }

            if ($amountMinor > $lockedBalanceMinor) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran melebihi saldo terutang anggota.',
                ]);
            }

            $newBalanceMinor = $lockedBalanceMinor - $amountMinor;
            $newBalanceDecimal = MinorAmount::toDecimalString($newBalanceMinor);

            $payment = PosMemberCreditPayment::query()->create([
                'cooperative_member_id' => $lockedMember->id,
                'received_by' => $receiver->id,
                'reference_no' => $referenceNo,
                'amount' => $amountDecimal,
                'paid_at' => $paidAt ?: now()->toDateString(),
                'notes' => $notes,
            ]);

            $lockedMember->forceFill(['outstanding_balance' => $newBalanceDecimal])->save();

            CooperativeLedgerEntry::query()->create([
                'organization_id' => $lockedMember->organization_id,
                'cooperative_member_id' => $lockedMember->id,
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

    public function recordSettlementPayment(
        MemberPaymentIntent $intent,
        ?string $notes = null,
        ?string $paidAt = null,
    ): PosMemberCreditPayment {
        return DB::transaction(function () use ($intent, $notes, $paidAt) {
            $lockedIntent = MemberPaymentIntent::query()
                ->whereKey($intent->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedIntent->payable_type !== MemberPaymentIntent::PAYABLE_POS_CREDIT) {
                throw ValidationException::withMessages([
                    'payable_type' => 'Jenis pembayaran intent bukan kredit POS.',
                ]);
            }

            try {
                if (! $lockedIntent->gatewayStatus()->isPaid()) {
                    throw ValidationException::withMessages([
                        'gateway_status' => 'Status gateway payment intent bukan PAID.',
                    ]);
                }
            } catch (\DomainException) {
                throw ValidationException::withMessages([
                    'gateway_status' => 'Status gateway payment intent tidak valid.',
                ]);
            }

            if ($lockedIntent->isSettled()) {
                throw ValidationException::withMessages([
                    'settlement_status' => 'Payment intent sudah diselesaikan (already settled).',
                ]);
            }

            try {
                $settlementStatus = $lockedIntent->settlementStatus();
            } catch (\DomainException) {
                throw ValidationException::withMessages([
                    'settlement_status' => 'Status settlement payment intent tidak valid.',
                ]);
            }

            if (! in_array($settlementStatus, [PaymentSettlementStatus::NotSettled, PaymentSettlementStatus::Settling], true)) {
                throw ValidationException::withMessages([
                    'settlement_status' => 'Status settlement payment intent tidak memenuhi syarat untuk diproses.',
                ]);
            }

            if (empty($lockedIntent->cooperative_member_id) || empty($lockedIntent->payable_id) || (int) $lockedIntent->cooperative_member_id !== (int) $lockedIntent->payable_id) {
                throw ValidationException::withMessages([
                    'payable_id' => 'Target anggota kredit POS pada intent pembayaran tidak cocok.',
                ]);
            }

            $lockedMember = CooperativeMember::query()
                ->whereKey($lockedIntent->cooperative_member_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedMember) {
                throw ValidationException::withMessages([
                    'cooperative_member_id' => 'Anggota koperasi untuk settlement tidak ditemukan.',
                ]);
            }

            if (empty($lockedMember->organization_id)) {
                throw new OrganizationScopeException('Model ['.CooperativeMember::class.'] has no valid organization.');
            }

            try {
                $amountMinor = MinorAmount::fromDecimal($lockedIntent->amount);
            } catch (\InvalidArgumentException) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran intent tidak valid.']);
            }
            $amountDecimal = MinorAmount::toDecimalString($amountMinor);
            $lockedBalanceMinor = MinorAmount::fromDecimal($lockedMember->outstanding_balance ?? '0.00');

            if ($amountMinor <= 0) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran harus lebih dari 0.']);
            }

            if ($lockedBalanceMinor <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Anggota tidak memiliki saldo terutang untuk dibayar.',
                ]);
            }

            if ($amountMinor > $lockedBalanceMinor) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran melebihi saldo terutang anggota.',
                ]);
            }

            $newBalanceMinor = $lockedBalanceMinor - $amountMinor;
            $newBalanceDecimal = MinorAmount::toDecimalString($newBalanceMinor);

            $payment = PosMemberCreditPayment::query()->create([
                'cooperative_member_id' => $lockedMember->id,
                'received_by' => null,
                'reference_no' => $lockedIntent->gateway_reference,
                'amount' => $amountDecimal,
                'paid_at' => $paidAt ?: now()->toDateString(),
                'notes' => $notes,
            ]);

            $lockedMember->forceFill(['outstanding_balance' => $newBalanceDecimal])->save();

            CooperativeLedgerEntry::query()->create([
                'organization_id' => $lockedMember->organization_id,
                'cooperative_member_id' => $lockedMember->id,
                'source_type' => PosMemberCreditPayment::class,
                'source_id' => $payment->id,
                'entry_type' => 'POS_MEMBER_CREDIT_PAYMENT',
                'ledger_scope' => 'POS',
                'debit' => 0,
                'credit' => $amountDecimal,
                'description' => 'Bayar cicilan kredit anggota: Rp '.$amountDecimal,
                'posted_at' => $payment->paid_at->toDateString(),
            ]);

            $lockedIntent->forceFill([
                'settled_at' => $lockedIntent->settled_at ?? now(),
                'settled_by_service' => 'pos_member_credit_payment:'.$payment->id,
                'settlement_status' => PaymentSettlementStatus::Settled->value,
            ])->save();

            return $payment->load('member', 'receiver');
        });
    }

    public function isCreditTransaction(PosTransaction $transaction): bool
    {
        return $transaction->payments->contains(fn ($p) => $p->payment_method === 'MEMBER_CREDIT');
    }
}
