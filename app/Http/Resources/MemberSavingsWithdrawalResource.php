<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSavingsWithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'status' => $this->status?->value ?? $this->status,
            'destination_bank' => $this->destination_bank,
            'destination_account_no' => $this->destination_account_no,
            'destination_account_name' => $this->destination_account_name,
            'reason' => $this->reason,
            'rejection_reason' => $this->rejection_reason,
            'approved_at' => $this->approved_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
