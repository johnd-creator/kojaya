<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberStoreFundingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'method' => $this->method->value,
            'method_label' => $this->method->label(),
            'amount' => (int) $this->amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'bank_reference' => $this->bank_reference,
            'has_proof' => ! empty($this->resource->getRawOriginal('proof_path')),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'posted_ledger_entry_id' => $this->posted_ledger_entry_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'submitter' => $this->whenLoaded('submitter', fn () => [
                'id' => $this->submitter->id,
                'name' => $this->submitter->name,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
        ];
    }
}
