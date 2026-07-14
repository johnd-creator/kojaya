<?php

namespace App\Http\Resources;

use App\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberSelfServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewSensitiveData = $this->canViewSensitiveData($request);

        return [
            'id' => $this->id,
            'member_no' => $this->member_no,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $canViewSensitiveData ? $this->phone : $this->mask($this->phone),
            'address' => $canViewSensitiveData ? $this->address : null,
            'status' => $this->status,
            'validation_status' => $this->validation_status,
            'joined_at' => $this->joined_at?->toDateString(),
            'resigned_at' => $this->resigned_at?->toDateString(),
            'gender' => $this->jenis_kelamin,
            'birth_date' => $this->tanggal_lahir?->toDateString(),
            'birth_place' => $this->tempat_lahir,
            'occupation' => $this->pekerjaan,
            'npwp' => $canViewSensitiveData ? $this->npwp : $this->mask($this->npwp),
            'bank_name' => $canViewSensitiveData ? $this->nama_bank : null,
            'bank_account_number' => $canViewSensitiveData ? $this->no_rekening : $this->mask($this->no_rekening),
            'bank_account_holder' => $canViewSensitiveData ? $this->nama_pemilik_rekening : null,
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ]),
            'user' => new MemberUserResource($this->whenLoaded('user')),
        ];
    }

    private function canViewSensitiveData(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ((int) $this->user_id === (int) $user->id) {
            return true;
        }

        return $user->can(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value)
            && ($user->can(PermissionEnum::COOPERATIVE_VIEW_ALL->value)
                || (string) $user->organization_id === (string) $this->organization_id);
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
