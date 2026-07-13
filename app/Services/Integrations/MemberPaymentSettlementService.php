<?php

namespace App\Services\Integrations;

use App\Enums\PaymentReservationStatus;
use App\Enums\PaymentSettlementStatus;
use App\Models\CoffeeOrder;
use App\Models\CooperativeMember;
use App\Models\LoanInstallment;
use App\Models\MemberPaymentIntent;
use App\Models\PosProduct;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeNotificationDispatcher;
use App\Services\Cooperative\LoanService;
use App\Services\Cooperative\MemberCreditService;
use App\Services\Cooperative\MemberOrderReservationService;
use App\Services\Cooperative\PosTransactionService;
use App\Support\Money\MinorAmount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MemberPaymentSettlementService
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly MemberCreditService $memberCreditService,
        private readonly PosTransactionService $posTransactionService,
        private readonly CooperativeNotificationDispatcher $notificationDispatcher,
        private readonly MemberOrderReservationService $reservationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function settle(MemberPaymentIntent $intent): MemberPaymentIntent
    {
        $intent = DB::transaction(function () use ($intent): MemberPaymentIntent {
            $intent = MemberPaymentIntent::query()
                ->lockForUpdate()
                ->with('member.user')
                ->findOrFail($intent->id);

            if ($intent->gateway_status !== 'PAID' || $intent->isSettled()) {
                return $intent;
            }

            $settlement = $intent->settlementStatus();
            if (! in_array($settlement, [PaymentSettlementStatus::NotSettled, PaymentSettlementStatus::Settling], true)) {
                return $intent;
            }

            if ($intent->isOrderType()) {
                $reservation = $intent->reservationStatus();

                if ($reservation !== PaymentReservationStatus::Reserved) {
                    $this->handleInvalidReservationForSettlement($intent, $reservation);

                    return $intent;
                }

                $this->reservationService->consume($intent);
                $intent->refresh();
            }

            $settledBy = match ($intent->payable_type) {
                MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT => $this->settleLoanInstallment($intent),
                MemberPaymentIntent::PAYABLE_POS_CREDIT => $this->settlePosCredit($intent),
                MemberPaymentIntent::PAYABLE_COFFEE_ORDER => $this->settleCoffeeOrder($intent),
                MemberPaymentIntent::PAYABLE_STORE_ORDER => $this->settleStoreOrder($intent),
                default => throw ValidationException::withMessages([
                    'payable_type' => 'Jenis pembayaran anggota belum didukung settlement gateway.',
                ]),
            };

            $intent->forceFill([
                'settled_at' => now(),
                'settled_by_service' => $settledBy,
                'settlement_status' => PaymentSettlementStatus::Settled->value,
            ])->save();

            return $intent->refresh();
        });

        return $intent;
    }

    private function handleInvalidReservationForSettlement(MemberPaymentIntent $intent, PaymentReservationStatus $reservation): void
    {
        Log::error('Settlement guard: PAID intent has invalid reservation state', [
            'intent_id' => $intent->id,
            'gateway_status' => $intent->gateway_status,
            'reservation_status' => $reservation->value,
        ]);

        $this->auditLogService->log(
            'settlement.reconciliation_incident',
            'member_payment_intent',
            $intent,
            [
                'reason' => 'PAID intent reached settlement with reservation state: '.$reservation->value,
                'requires_manual_resolution' => true,
            ],
        );

        $intent->forceFill([
            'settlement_status' => PaymentSettlementStatus::Failed->value,
        ])->save();
    }

    private function settleLoanInstallment(MemberPaymentIntent $intent): string
    {
        $installment = LoanInstallment::query()
            ->with('loan')
            ->findOrFail($intent->payable_id);

        if ((int) $installment->loan->cooperative_member_id !== (int) $intent->cooperative_member_id) {
            throw ValidationException::withMessages([
                'payable_id' => 'Cicilan pinjaman bukan milik anggota ini.',
            ]);
        }

        $remainingDue = round((float) $installment->amount_due - (float) $installment->amount_paid, 2);
        if (MinorAmount::greaterThan($intent->amount, $remainingDue)) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal payment intent melebihi sisa cicilan.',
            ]);
        }

        $payment = $this->loanService->recordPayment($installment->loan, [
            'amount' => (float) $intent->amount,
            'paid_at' => now()->toDateString(),
            'payment_method' => $intent->channel,
            'reference_no' => $intent->gateway_reference,
            'notes' => 'Settlement gateway anggota untuk tagihan loan:'.$installment->id,
        ]);

        return 'loan_payment:'.$payment->id;
    }

    private function settlePosCredit(MemberPaymentIntent $intent): string
    {
        $member = CooperativeMember::query()->findOrFail($intent->cooperative_member_id);

        if ((int) $intent->payable_id !== (int) $member->id) {
            throw ValidationException::withMessages([
                'payable_id' => 'Tagihan kredit POS bukan milik anggota ini.',
            ]);
        }

        $payment = $this->memberCreditService->recordPayment(
            member: $member,
            amount: (float) $intent->amount,
            receiver: null,
            referenceNo: $intent->gateway_reference,
            notes: 'Settlement gateway anggota untuk kredit POS.',
            paidAt: now()->toDateString(),
        );

        return 'pos_member_credit_payment:'.$payment->id;
    }

    private function settleCoffeeOrder(MemberPaymentIntent $intent): string
    {
        $metadata = $intent->metadata ?? [];
        $items = $metadata['items'] ?? [];

        if (! is_array($items) || $items === []) {
            throw ValidationException::withMessages([
                'items' => 'Item kopi untuk settlement tidak ditemukan.',
            ]);
        }

        $transactionItems = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $product = PosProduct::query()->findOrFail($item['pos_product_id'] ?? null);
            $transactionItems[] = [
                'pos_product_id' => $product->id,
                'quantity' => (int) ($item['quantity'] ?? 1),
            ];
        }

        if ($transactionItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Item kopi untuk settlement tidak valid.',
            ]);
        }

        $transaction = $this->posTransactionService->create([
            'client_reference' => (string) ($metadata['client_reference'] ?? 'COFFEE-INTENT-'.$intent->id),
            'cooperative_member_id' => $intent->cooperative_member_id,
            'payment_method' => $intent->channel,
            'amount' => (float) $intent->amount,
            'cash_received' => (float) $intent->amount,
            'discount_amount' => 0,
            'items' => $transactionItems,
        ], $intent->user);

        $firstItem = $items[0];
        $coffeeOrder = CoffeeOrder::query()->firstOrCreate(
            ['pos_transaction_id' => $transaction->id],
            [
                'cooperative_member_id' => $intent->cooperative_member_id,
                'pos_product_id' => (int) ($firstItem['pos_product_id'] ?? $transactionItems[0]['pos_product_id']),
                'quantity' => array_sum(array_map(fn (array $item): int => (int) ($item['quantity'] ?? 1), $transactionItems)),
                'status' => CoffeeOrder::STATUS_RECEIVED,
                'customization' => [
                    'items' => $items,
                    'source_payment_intent_id' => $intent->id,
                ],
                'received_at' => now(),
            ],
        );

        DB::afterCommit(fn () => $this->notificationDispatcher->coffeeOrderReceived($coffeeOrder, $intent->user));

        return 'coffee_order:'.$coffeeOrder->id;
    }

    private function settleStoreOrder(MemberPaymentIntent $intent): string
    {
        $metadata = $intent->metadata ?? [];
        $items = $metadata['items'] ?? [];

        if (! is_array($items) || $items === []) {
            throw ValidationException::withMessages([
                'items' => 'Item toko untuk settlement tidak ditemukan.',
            ]);
        }

        $transactionItems = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $product = PosProduct::query()->findOrFail($item['pos_product_id'] ?? null);
            $transactionItems[] = [
                'pos_product_id' => $product->id,
                'quantity' => (int) ($item['quantity'] ?? 1),
            ];
        }

        if ($transactionItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Item toko untuk settlement tidak valid.',
            ]);
        }

        $transaction = $this->posTransactionService->create([
            'client_reference' => (string) ($metadata['client_reference'] ?? 'STORE-INTENT-'.$intent->id),
            'cooperative_member_id' => $intent->cooperative_member_id,
            'payment_method' => $intent->channel,
            'amount' => (float) $intent->amount,
            'cash_received' => (float) $intent->amount,
            'discount_amount' => 0,
            'items' => $transactionItems,
        ], $intent->user);

        $transactionId = $transaction->id;
        DB::afterCommit(function () use ($transactionId): void {
            $transaction = \App\Models\PosTransaction::query()
                ->with(['items.product', 'payments', 'member'])
                ->findOrFail($transactionId);
            $this->notificationDispatcher->posSaleCompleted($transaction, request()?->user());
        });

        return 'store_transaction:'.$transaction->id;
    }
}
