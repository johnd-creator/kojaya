<?php

namespace App\Services\Cooperative;

use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CooperativeReceiptService
{
    public function issue(CooperativePayment $payment, ?User $issuer = null): CooperativeReceipt
    {
        return DB::transaction(function () use ($payment, $issuer): CooperativeReceipt {
            $payment = CooperativePayment::query()
                ->lockForUpdate()
                ->with(['invoice.contributionType', 'member', 'receipt'])
                ->findOrFail($payment->id);

            if ($payment->receipt) {
                return $payment->receipt;
            }

            $period = $payment->invoice?->period ?? $payment->paid_at?->format('Y-m') ?? now()->format('Y-m');
            $receiptNo = $this->nextReceiptNo($period);
            $path = 'cooperative/receipts/'.$receiptNo.'.pdf';

            $receipt = CooperativeReceipt::query()->create([
                'receipt_no' => $receiptNo,
                'cooperative_payment_id' => $payment->id,
                'cooperative_member_id' => $payment->cooperative_member_id,
                'pdf_path' => $path,
                'issued_at' => now(),
                'issued_by' => $issuer?->id,
            ]);

            $payment->forceFill([
                'receipt_no' => $receiptNo,
                'receipt_issued_at' => $receipt->issued_at,
            ])->save();

            Storage::disk('local')->put($path, $this->renderPdf($payment->refresh()->load(['invoice.contributionType', 'member']), $receipt));

            return $receipt->refresh();
        });
    }

    private function nextReceiptNo(string $period): string
    {
        $prefix = 'RC-'.str_replace('-', '', $period).'-';
        $sequence = CooperativeReceipt::query()
            ->where('receipt_no', 'like', $prefix.'%')
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('%s%06d', $prefix, $sequence);
    }

    private function renderPdf(CooperativePayment $payment, CooperativeReceipt $receipt): string
    {
        $html = view('cooperative.receipts.payment', [
            'payment' => $payment,
            'receipt' => $receipt,
        ])->render();

        return Pdf::loadHTML($html)->output();
    }
}
