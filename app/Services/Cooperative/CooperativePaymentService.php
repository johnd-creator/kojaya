<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CooperativePaymentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $user = null): CooperativePayment
    {
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

            $payment->forceFill([
                'status' => 'APPROVED',
                'approved_at' => now(),
                'approved_by' => $approver?->id,
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
}
