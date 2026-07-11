<?php

namespace App\Exports;

use App\Models\CooperativeMember;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AnggotaExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly array $filters = [],
        private readonly ?string $organizationId = null,
    ) {}

    public function query(): Builder
    {
        $query = CooperativeMember::query();

        if ($this->organizationId !== null) {
            $query->where('organization_id', $this->organizationId);
        }

        if (($search = $this->filters['search'] ?? null) !== null && $search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('no_anggota', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%")
                    ->orWhere('nama_anggota', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%");

                $npwpIndex = CooperativeMember::blindIndexFor('npwp', $search);
                $query->when($npwpIndex, fn (Builder $query) => $query->orWhere('npwp_bidx', $npwpIndex));
            });
        }

        if (($status = $this->filters['status'] ?? null) !== null && $status !== '') {
            if ($status === 'INACTIVE') {
                $query->whereIn('status', ['INACTIVE', 'RESIGNED']);
            } else {
                $query->where('status', $status);
            }
        }

        foreach (['jenis_anggota', 'kategori'] as $filter) {
            if (($value = $this->filters[$filter] ?? null) !== null && $value !== '') {
                $query->where($filter, $value);
            }
        }

        return $query->orderBy('no_anggota');
    }

    public function headings(): array
    {
        return [
            'No Anggota',
            'Tanggal Aktif',
            'Nama Anggota',
            'Status',
            'NPWP',
            'No Telp',
            'Jenis Anggota',
            'Jenis Kelamin',
            'Kategori',
            'Autodebet',
            'No Rekening',
        ];
    }

    public function map($member): array
    {
        return [
            $member->no_anggota_display,
            $member->tanggal_aktif?->toDateString() ?? $member->joined_at?->toDateString(),
            $member->nama_anggota_clean,
            $member->status_badge['label'],
            $this->maskNpwp($member->npwp),
            $member->no_telp ?: $member->phone,
            $member->jenis_anggota_label,
            match ($member->jenis_kelamin) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => null,
            },
            match ($member->kategori) {
                'IP' => 'Indonesia Power',
                'CDB' => 'Cogindo DayaBersama',
                'KOP' => 'Koperasi',
                default => null,
            },
            $member->autodebet,
            $this->maskRekening($member->no_rekening),
        ];
    }

    private function maskNpwp(?string $npwp): ?string
    {
        if ($npwp === null || $npwp === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $npwp);

        if (strlen($digits) <= 6) {
            return '***';
        }

        return substr($digits, 0, 3).'.'.substr($digits, 3, 3).'.***.***';
    }

    private function maskRekening(?string $rekening): ?string
    {
        if ($rekening === null || $rekening === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $rekening);

        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
