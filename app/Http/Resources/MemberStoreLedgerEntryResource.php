<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberStoreLedgerEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_type' => $this->entry_type->value,
            'entry_type_label' => $this->entry_type->label(),
            'amount' => (int) $this->amount,
            'effect' => $this->effect->value,
            'balance_before' => (int) $this->balance_before,
            'balance_after' => (int) $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reason' => $this->reason,
            'is_reversed' => $this->isReversed(),
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ]),
            'delegate' => $this->whenLoaded('delegate', fn () => [
                'id' => $this->delegate->id,
                'display_name' => $this->delegate->display_name,
            ]),
        ];
    }
}
