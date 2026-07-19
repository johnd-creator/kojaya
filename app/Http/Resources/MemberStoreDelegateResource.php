<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberStoreDelegateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'code' => $this->code,
            'per_transaction_limit' => $this->per_transaction_limit !== null ? (int) $this->per_transaction_limit : null,
            'daily_limit' => $this->daily_limit !== null ? (int) $this->daily_limit : null,
            'valid_from' => $this->valid_from?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_currently_active' => $this->isCurrentlyActive(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'has_pin' => ! empty($this->resource->getRawOriginal('pin_hash')),
        ];
    }
}
