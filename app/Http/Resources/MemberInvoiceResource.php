<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period' => $this->period,
            'amount' => (float) $this->amount,
            'paid_amount' => (float) $this->paid_amount,
            'remaining_amount' => round((float) $this->amount - (float) $this->paid_amount, 2),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'contribution_type' => $this->whenLoaded('contributionType', fn () => [
                'id' => $this->contributionType?->id,
                'code' => $this->contributionType?->code,
                'name' => $this->contributionType?->name,
                'category' => $this->contributionType?->category,
            ]),
        ];
    }
}
