<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CooperativePaymentService
{
    public function __construct(private readonly CooperativePeriodLockService $periodLockService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $user = null): CooperativePayment
    {
        $invoice = isset($data['cooperative_dues_invoice_id'])
            ? CooperativeDuesInvoice::query()->find($data['cooperative_dues_invoice_id'])
            : null;

        $this->periodLockService->assertUnlocked($invoice?->period ?? substr((string) $data['paid_at'], 0, 7));

        return CooperativePayment::query()->create([
            ...$data,
            'user_id' => $user?->id,
            'status' => $data['status'] ?? 'PENDING',
        ]);
    }

    public function approve(CooperativePayment $payment, ?User $approver = null): CooperativePayment
    {
        return DB::transaction(function () use ($payment, $approver): CooperativePayment {
            $payment = CooperativePayment::query()
                ->lockForUpdate()
                ->with('ledgerEntries')
                ->findOrFail($payment->id);

            if ($payment->status === 'APPROVED' && $payment->ledgerEntries->isNotEmpty()) {
                return $payment;
            }

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            $payment->forceFill([
                'status' => 'APPROVED',
                'approved_at' => now(),
                'approved_by' => $approver?->id,
                'receipt_no' => $payment->receipt_no ?: $this->receiptNo($payment),
                'receipt_issued_at' => $payment->receipt_issued_at ?: now(),
            ])->save();

            if ($payment->cooperative_dues_invoice_id) {
                $invoice = CooperativeDuesInvoice::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->cooperative_dues_invoice_id);

                $paidAmount = (float) $invoice->paid_amount + (float) $payment->amount;

                $invoice->forceFill([
                    'paid_amount' => $paidAmount,
                    'status' => $paidAmount >= (float) $invoice->amount ? 'PAID' : 'PARTIAL',
                ])->save();
            }

            CooperativeLedgerEntry::query()->firstOrCreate(
                [
                    'cooperative_payment_id' => $payment->id,
                    'entry_type' => 'SAVING_PAYMENT',
                ],
                [
                    'cooperative_member_id' => $payment->cooperative_member_id,
                    'source_type' => CooperativePayment::class,
                    'source_id' => $payment->id,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'period' => $payment->invoice?->period,
                    'description' => 'Pembayaran iuran/simpanan koperasi',
                    'posted_at' => $payment->paid_at,
                ],
            );

            return $payment->refresh();
        });
    }

    public function reconcile(CooperativePayment $payment, ?User $user, string $reference, bool $approve = true): CooperativePayment
    {
        return DB::transaction(function () use ($payment, $user, $reference, $approve): CooperativePayment {
            $payment = CooperativePayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($approve && $payment->status !== 'APPROVED') {
                $payment = $this->approve($payment, $user);
            }

            $this->periodLockService->assertUnlocked($payment->invoice?->period ?? $payment->paid_at?->format('Y-m'));

            $payment->forceFill([
                'reconciled_at' => now(),
                'reconciled_by' => $user?->id,
                'reconciliation_reference' => $reference,
            ])->save();

            return $payment->refresh();
        });
    }

    private function receiptNo(CooperativePayment $payment): string
    {
        return sprintf('RCPT-%s-%06d', now()->format('Ymd'), $payment->id);
    }
}
