<?php

namespace App\Http\Resources;

use App\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CooperativeMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewSensitiveData = $request->user()?->can(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value) ?? false;

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'member_no' => $this->member_no,
            'no_anggota' => $this->no_anggota,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->mask($this->phone),
            'status' => $this->status,
            'validation_status' => $this->validation_status,
            'joined_at' => $this->joined_at?->toDateString(),
            'identity_number' => $canViewSensitiveData ? $this->identity_number : $this->mask($this->identity_number),
            'npwp' => $canViewSensitiveData ? $this->npwp : $this->mask($this->npwp),
            'no_rekening' => $canViewSensitiveData ? $this->no_rekening : $this->mask($this->no_rekening),
            'nama_pemilik_rekening' => $canViewSensitiveData ? $this->nama_pemilik_rekening : null,
            'nama_bank' => $canViewSensitiveData ? $this->nama_bank : null,
            'address' => $canViewSensitiveData ? $this->address : null,
            'organization' => $this->whenLoaded('organization', fn (): array => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
        ];
    }

    private function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $visible = min(4, strlen($value));

        return str_repeat('*', max(strlen($value) - $visible, 0)).substr($value, -$visible);
    }
}
