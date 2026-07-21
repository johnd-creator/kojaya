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
            'reference_type' => $this->publicReferenceType(),
            'reference_id' => $this->reference_id === null ? null : (string) $this->reference_id,
            'transaction_no' => $this->transaction_no,
            'purchaser_name' => $this->purchaser_name,
            'cashier_name' => $this->whenLoaded('actor', fn (): ?string => $this->actor?->name),
            'purchase_note' => $this->purchase_note,
            'reason' => $this->reason,
            'status' => $this->metadata['status'] ?? null,
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

    private function publicReferenceType(): ?string
    {
        return match ($this->reference_type) {
            'pos_transaction', 'App\\Models\\PosTransaction' => 'pos_transaction',
            'pos_return', 'App\\Models\\PosReturn' => 'pos_return',
            'funding_request', 'App\\Models\\MemberStoreFundingRequest' => 'funding_request',
            'store_account', 'App\\Models\\MemberStoreAccount' => 'store_account',
            'ledger_entry', 'App\\Models\\MemberStoreLedgerEntry' => 'ledger_entry',
            default => null,
        };
    }
}
