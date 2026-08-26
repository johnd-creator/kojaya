<?php

namespace App\Services\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\User;

class CooperativeMemberPageDataService
{
    /**
     * @return array<string, mixed>
     */
    public function list(CooperativeMember $member): array
    {
        return [
            ...$this->base($member),
            'organization_name' => $member->relationLoaded('organization') ? $member->organization?->name : null,
            'saving_balance' => (float) ($member->saving_balance ?? 0),
            'credit_balance' => (float) ($member->credit_balance ?? 0),
            'identity_number' => $this->mask($member->identity_number),
            'npwp' => $this->mask($member->npwp),
            'no_rekening' => $this->mask($member->no_rekening),
            'address' => null,
            'notes' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(CooperativeMember $member, ?User $user): array
    {
        $data = [
            ...$this->base($member),
            'employee_id' => $member->employee_id,
            'user_id' => $member->user_id,
            'tanggal_aktif' => $member->tanggal_aktif?->toISOString(),
            'saving_balance' => (float) ($member->saving_balance ?? 0),
            'credit_balance' => (float) ($member->credit_balance ?? 0),
            'address' => null,
            'autodebet' => $member->autodebet,
            'identity_number' => $this->mask($member->identity_number),
            'npwp' => $this->mask($member->npwp),
            'no_rekening' => $this->mask($member->no_rekening),
            'nama_bank' => null,
            'nama_pemilik_rekening' => null,
            'notes' => null,
            'organization' => $member->relationLoaded('organization') && $member->organization ? [
                'id' => $member->organization->id,
                'name' => $member->organization->name,
            ] : null,
        ];

        if ($this->canViewPii($user)) {
            $data['identity_number'] = $member->identity_number;
            $data['npwp'] = $member->npwp;
            $data['no_rekening'] = $member->no_rekening;
            $data['nama_bank'] = $member->nama_bank;
            $data['nama_pemilik_rekening'] = $member->nama_pemilik_rekening;
            $data['address'] = $member->address;
            $data['notes'] = $member->notes;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function edit(CooperativeMember $member, ?User $user): array
    {
        return [
            ...$this->detail($member, $user),
            'tanggal_aktif' => $member->tanggal_aktif?->toDateString(),
            'joined_at' => $member->joined_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ledgerEntry(CooperativeLedgerEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'entry_type' => $entry->entry_type,
            'ledger_scope' => $entry->ledger_scope,
            'category_snapshot' => $entry->category_snapshot,
            'debit' => (float) $entry->debit,
            'credit' => (float) $entry->credit,
            'period' => $entry->period,
            'description' => $entry->description,
            'posted_at' => $entry->posted_at?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base(CooperativeMember $member): array
    {
        return [
            'id' => $member->id,
            'organization_id' => $member->organization_id,
            'member_no' => $member->member_no,
            'no_anggota' => $member->no_anggota,
            'no_anggota_display' => $member->no_anggota_display,
            'name' => $member->name,
            'nama_anggota' => $member->nama_anggota,
            'nama_anggota_clean' => $member->nama_anggota_clean,
            'jenis_anggota' => $member->jenis_anggota,
            'jenis_anggota_label' => $member->jenis_anggota_label,
            'jenis_kelamin' => $member->jenis_kelamin,
            'kategori' => $member->kategori,
            'email' => $member->email,
            'phone' => $member->phone,
            'no_telp' => $member->no_telp,
            'status' => $member->status,
            'status_badge' => $member->status_badge,
            'validation_status' => $member->validation_status,
            'joined_at' => $member->joined_at?->toISOString(),
        ];
    }

    private function canViewPii(?User $user): bool
    {
        return $user?->can(PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value) ?? false;
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
