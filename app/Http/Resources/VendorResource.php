<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status->value,
            'rating' => $this->rating,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_id' => $this->when($request->user()?->can('manage_procurement'), $this->tax_id),
            'address' => $this->address,
        ];
    }
}
