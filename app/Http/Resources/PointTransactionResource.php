<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cooperative_member_id' => $this->cooperative_member_id,
            'transaction_type' => $this->transaction_type,
            'points' => (int) $this->points,
            'balance_before' => (int) $this->balance_before,
            'balance_after' => (int) $this->balance_after,
            'description' => $this->description,
            'reference_number' => $this->reference_number,
            'transaction_date' => $this->transaction_date?->toISOString(),
        ];
    }
}
