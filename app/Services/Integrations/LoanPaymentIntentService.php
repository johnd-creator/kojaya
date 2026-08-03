<?php

namespace App\Services\Integrations;

use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Exceptions\PaymentIntentConflictException;
use App\Models\CooperativeMember;
use App\Models\LoanInstallment;
use App\Models\MemberPaymentIntent;
use App\Support\Money\MinorAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LoanPaymentIntentService
{
    public function __construct(
        private readonly PaymentGatewayService $gateway,
        private readonly MemberPaymentIntentStateService $stateService,
        private readonly MemberPaymentSettlementService $settlementService,
        private readonly PaymentIntentChargeService $chargeService,
    ) {}

    public function resolveOrCreate(
        CooperativeMember $member,
        int $installmentId,
        ?int $userId,
        string $requestedChannel,
    ): LoanPaymentIntentResolution {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decision = DB::transaction(function () use ($member, $installmentId, $userId, $requestedChannel): array {
                $installment = LoanInstallment::query()
                    ->with('loan.loanType')
                    ->whereKey($installmentId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $installment->loan?->cooperative_member_id !== (int) $member->id) {
                    abort(404);
                }

                if (! in_array($installment->loan?->status?->value, [
                    LoanStatus::Active->value,
                    LoanStatus::Defaulted->value,
                ], true) || ! in_array($installment->status?->value, [
                    InstallmentStatus::Pending->value,
                    InstallmentStatus::Partial->value,
                    InstallmentStatus::Overdue->value,
                ], true)) {
                    abort(404);
                }

                $paidIntent = MemberPaymentIntent::query()
                    ->where('cooperative_member_id', $member->id)
                    ->where('payable_type', MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT)
                    ->where('payable_id', $installment->id)
                    ->where(function ($query): void {
                        $query->where('gateway_status', 'PAID')
                            ->orWhereNotNull('settled_at');
                    })
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($paidIntent !== null) {
                    throw PaymentIntentConflictException::loanAlreadyPaid(
                        'Pembayaran cicilan ini sudah diterima. Tunggu pembaruan status cicilan sebelum mencoba lagi.'
                    );
                }

                $remainingMinor = $this->remainingMinor($installment);
                if ($remainingMinor <= 0) {
                    throw ValidationException::withMessages([
                        'loan_installment_id' => 'Cicilan pinjaman ini sudah lunas.',
                    ]);
                }

                $intent = MemberPaymentIntent::query()
                    ->where('cooperative_member_id', $member->id)
                    ->where('payable_type', MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT)
                    ->where('payable_id', $installment->id)
                    ->whereNull('settled_at')
                    ->whereIn('gateway_status', ['PENDING', 'CHARGE_CREATING'])
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($intent === null) {
                    $intent = MemberPaymentIntent::query()->create([
                        'user_id' => $userId,
                        'cooperative_member_id' => $member->id,
                        'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
                        'payable_id' => $installment->id,
                        'amount' => MinorAmount::toDecimalString($remainingMinor),
                        'channel' => $requestedChannel,
                        'gateway_status' => 'PENDING',
                        'settlement_status' => 'NOT_SETTLED',
                        'metadata' => [
                            'description' => "Angsuran {$installment->loan?->loanType?->name} #{$installment->installment_no}",
                            'loan_id' => $installment->loan_id,
                            'installment_no' => $installment->installment_no,
                        ],
                        'expires_at' => now()->addDay(),
                    ]);

                    return [
                        'action' => 'charge',
                        'intent_id' => $intent->id,
                        'created' => true,
                    ];
                }

                $amountMatches = MinorAmount::fromDecimal($intent->amount) === $remainingMinor;
                $expired = $intent->expires_at?->isPast() === true;
                $hasUsablePresentation = $this->chargeService->reusableCharge($intent) !== null;
                $needsReconciliation = $expired
                    || ! $amountMatches
                    || $intent->gateway_status === 'CHARGE_CREATING'
                    || ($intent->gateway_reference !== null && ! $hasUsablePresentation);

                if (! $needsReconciliation) {
                    return [
                        'action' => 'charge',
                        'intent_id' => $intent->id,
                        'created' => false,
                    ];
                }

                return [
                    'action' => 'reconcile',
                    'intent_id' => $intent->id,
                    'remaining_minor' => $remainingMinor,
                    'amount_matches' => $amountMatches,
                    'expired' => $expired,
                    'gateway_reference' => $intent->gateway_reference,
                ];
            });

            if ($decision['action'] === 'charge') {
                return new LoanPaymentIntentResolution(
                    MemberPaymentIntent::query()->findOrFail($decision['intent_id']),
                    (bool) $decision['created'],
                    $requestedChannel,
                );
            }

            $this->reconcileForReplacement($decision);
        }

        throw PaymentIntentConflictException::loanReconciliationRequired(
            'Pembayaran sebelumnya belum definitif diproses. Coba lagi setelah status diperbarui.'
        );
    }

    /**
     * @param  array{intent_id:int, remaining_minor:int, amount_matches:bool, expired:bool, gateway_reference:string|null}  $decision
     */
    private function reconcileForReplacement(array $decision): void
    {
        $intent = MemberPaymentIntent::query()->findOrFail($decision['intent_id']);

        if ($intent->gateway_status === 'CHARGE_CREATING') {
            throw PaymentIntentConflictException::loanReconciliationRequired(
                'Pembayaran sebelumnya sedang dibuat atau direkonsiliasi. Tunggu sebentar sebelum mencoba lagi.'
            );
        }

        if ($intent->gateway_reference === null) {
            if (! $decision['expired']) {
                $this->stateService->expireForReplacement($intent, 'loan_amount_changed_without_provider_charge');
            } else {
                $this->stateService->expireStaleIntent($intent);
            }

            return;
        }

        try {
            $providerCharge = $this->gateway->reconcileIntentCharge($intent->gateway_reference);
        } catch (RuntimeException) {
            throw PaymentIntentConflictException::loanReconciliationRequired(
                'Status pembayaran sebelumnya belum dapat dipastikan. Tidak dibuat tagihan kedua; coba lagi setelah provider tersedia.'
            );
        }

        if ($providerCharge === null) {
            $this->stateService->applyGatewayEvent(
                $intent->gateway_reference,
                'EXPIRED',
                ['status' => 'EXPIRED', 'reason' => 'provider_charge_not_found'],
            );

            return;
        }

        $status = strtoupper((string) ($providerCharge['status'] ?? 'UNKNOWN'));
        $reference = (string) ($providerCharge['reference'] ?? $intent->gateway_reference);
        $amountMinor = $providerCharge['amount_minor'] ?? null;

        if ($amountMinor === null && array_key_exists('amount', $providerCharge)) {
            $amountMinor = MinorAmount::fromDecimal($providerCharge['amount']);
        }

        if ($status === 'PAID') {
            $updated = $this->stateService->applyGatewayEvent(
                $reference,
                'PAID',
                $providerCharge,
                is_int($amountMinor) ? $amountMinor : null,
            );

            if ($updated?->gateway_status === 'PAID' && ! $updated->settled_at) {
                try {
                    $this->settlementService->settle($updated);
                } catch (ValidationException|RuntimeException) {
                    throw PaymentIntentConflictException::loanReconciliationRequired(
                        'Pembayaran sebelumnya diterima provider tetapi belum dapat diselesaikan. Tidak dibuat tagihan kedua.'
                    );
                }
            }

            if ($updated?->gateway_status !== 'PAID') {
                throw PaymentIntentConflictException::loanReconciliationRequired(
                    'Provider melaporkan pembayaran diterima tetapi validasi nominal belum selesai. Tidak dibuat tagihan kedua.'
                );
            }

            return;
        }

        if (in_array($status, ['EXPIRED', 'FAILED', 'CANCELLED', 'DENIED'], true)) {
            $this->stateService->applyGatewayEvent(
                $reference,
                $status,
                $providerCharge,
                is_int($amountMinor) ? $amountMinor : null,
            );

            return;
        }

        if (! $decision['amount_matches']) {
            throw PaymentIntentConflictException::loanAmountStale(
                'Sisa cicilan berubah sementara pembayaran sebelumnya masih aktif. Selesaikan atau tunggu pembayaran sebelumnya sebelum mencoba lagi.'
            );
        }

        throw PaymentIntentConflictException::loanReconciliationRequired(
            'Pembayaran sebelumnya masih aktif atau statusnya belum pasti. Tidak dibuat tagihan kedua.'
        );
    }

    private function remainingMinor(LoanInstallment $installment): int
    {
        return max(
            MinorAmount::fromDecimal($installment->amount_due) - MinorAmount::fromDecimal($installment->amount_paid),
            0,
        );
    }
}
