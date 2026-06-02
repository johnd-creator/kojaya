<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_type' => $this->entry_type,
            'description' => $this->description,
            'period' => $this->period,
            'posted_at' => $this->posted_at?->toDateString(),
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
        ];
    }
}
