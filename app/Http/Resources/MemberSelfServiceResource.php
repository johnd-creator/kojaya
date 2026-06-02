<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSelfServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_no' => $this->member_no,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'joined_at' => $this->joined_at?->toDateString(),
            'resigned_at' => $this->resigned_at?->toDateString(),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
            'user' => new MemberUserResource($this->whenLoaded('user')),
        ];
    }
}
