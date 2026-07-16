<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CooperativePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->cooperative_member_id,
            'invoice_id' => $this->cooperative_dues_invoice_id,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toDateString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'approved_by' => $this->approved_by,
            'reference_no' => $this->reference_no,
            'receipt_no' => $this->receipt_no,
            'receipt_issued_at' => $this->receipt_issued_at?->toISOString(),
            'contribution_type' => $this->whenLoaded('contributionType', fn () => [
                'id' => $this->contributionType?->id,
                'code' => $this->contributionType?->code,
                'name' => $this->contributionType?->name,
                'category' => $this->contributionType?->category,
            ]),
            'invoice' => new MemberInvoiceResource($this->whenLoaded('invoice')),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member?->id,
                'member_code' => $this->member?->no_anggota_display,
                'name' => $this->member?->nama_anggota_clean,
            ]),
        ];
    }
}
