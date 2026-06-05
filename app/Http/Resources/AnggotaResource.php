<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'no_anggota' => $this->no_anggota_display,
            'tanggal_aktif' => $this->tanggal_aktif?->toDateString() ?? $this->joined_at?->toDateString(),
            'email' => $this->email,
            'nama_anggota' => $this->nama_anggota,
            'nama_anggota_clean' => $this->nama_anggota_clean,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'npwp' => $this->npwp,
            'no_telp' => $this->no_telp ?: $this->phone,
            'jenis_anggota' => $this->jenis_anggota,
            'jenis_anggota_label' => $this->jenis_anggota_label,
            'jenis_kelamin' => $this->jenis_kelamin,
            'kategori' => $this->kategori,
            'autodebet' => $this->autodebet,
            'no_rekening' => $this->no_rekening,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
