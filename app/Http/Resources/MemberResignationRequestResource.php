<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResignationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'effective_date' => $this->effective_date?->toDateString(),
            'review_notes' => $this->review_notes,
            'requested_at' => $this->requested_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member->id,
                'name' => $this->member->nama_anggota_clean,
                'member_code' => $this->member->no_anggota_display,
                'status' => $this->member->status,
                'organization' => $this->whenLoaded('member.organization', fn () => [
                    'id' => $this->member->organization?->id,
                    'name' => $this->member->organization?->name,
                ]),
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
            ]),
        ];
    }
}
