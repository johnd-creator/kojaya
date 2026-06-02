<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_no' => $this->transaction_no,
            'client_reference' => $this->client_reference,
            'cooperative_member_id' => $this->cooperative_member_id,
            'cashier_id' => $this->cashier_id,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'gross_profit' => (float) $this->gross_profit,
            'status' => $this->status,
            'sold_at' => $this->sold_at?->toISOString(),
            'items' => $this->whenLoaded('items'),
            'payments' => $this->whenLoaded('payments'),
            'member' => new MemberResource($this->whenLoaded('member')),
        ];
    }
}
