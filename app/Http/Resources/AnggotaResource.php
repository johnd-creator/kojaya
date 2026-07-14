<?php

namespace App\Http\Resources;

use App\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnggotaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canViewSensitiveData = $request->user()?->can(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value) ?? false;

        return [
            'id' => $this->id,
            'no_anggota' => $this->no_anggota_display,
            'tanggal_aktif' => $this->tanggal_aktif?->toDateString() ?? $this->joined_at?->toDateString(),
            'email' => $this->email,
            'nama_anggota' => $this->nama_anggota,
            'nama_anggota_clean' => $this->nama_anggota_clean,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'npwp' => $canViewSensitiveData ? $this->npwp : $this->mask($this->npwp),
            'no_telp' => $this->no_telp ?: $this->phone,
            'jenis_anggota' => $this->jenis_anggota,
            'jenis_anggota_label' => $this->jenis_anggota_label,
            'jenis_kelamin' => $this->jenis_kelamin,
            'kategori' => $this->kategori,
            'autodebet' => $this->autodebet,
            'no_rekening' => $canViewSensitiveData ? $this->no_rekening : $this->mask($this->no_rekening),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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
