<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = is_array($this->gateway_payload) ? $this->gateway_payload : [];
        $presentation = is_array($payload['presentation'] ?? null) ? $payload['presentation'] : $payload;

        return [
            'id' => $this->id,
            'invoice_id' => $this->cooperative_dues_invoice_id,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'gateway_provider' => $this->gateway_provider,
            'gateway_reference' => $this->gateway_reference,
            'gateway_status' => $this->gateway_status,
            'gateway_expires_at' => $presentation['expires_at'] ?? null,
            'qr_image_url' => $presentation['qr_image_url'] ?? null,
            'poll_after_seconds' => (int) ($presentation['poll_after_seconds'] ?? 5),
            'paid_at' => $this->paid_at?->toDateString(),
            'status' => $this->status,
            'proof_path' => $this->proof_path,
            'reference_no' => $this->reference_no,
            'receipt_no' => $this->receipt_no,
            'receipt_issued_at' => $this->receipt_issued_at?->toISOString(),
            'notes' => $this->notes,
            'invoice' => new MemberInvoiceResource($this->whenLoaded('invoice')),
            'has_receipt' => $this->whenLoaded('receipt', fn () => $this->receipt !== null),
        ];
    }
}
