<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberStoreAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'cooperative_member_id' => $this->cooperative_member_id,
            'balance' => (int) $this->balance,
            'credit_limit' => (int) $this->credit_limit,
            'available_credit' => (int) $this->availableCredit(),
            'available_spending' => (int) $this->availableCredit(),
            'balance_label' => (int) $this->balance < 0 ? 'Pemakaian/utang toko' : 'Saldo tersimpan',
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_negative' => (int) $this->balance < 0,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'full_name' => $this->member->full_name ?? $this->member->name ?? null,
                'name' => $this->member->name ?? null,
                'member_no' => $this->member->member_no ?? null,
            ]),
        ];
    }
}
