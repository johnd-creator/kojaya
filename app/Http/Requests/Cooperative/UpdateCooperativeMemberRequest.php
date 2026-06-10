<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCooperativeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $namaAnggota = (string) $this->input('nama_anggota', $this->input('name', ''));
        $autodebet = (string) $this->input('autodebet', 'MANUAL');
        $noRekening = $this->input('no_rekening');
        $tanggalAktif = $this->normalizeDateInput($this->input('tanggal_aktif', $this->input('joined_at')));

        $this->merge([
            'tanggal_aktif' => $tanggalAktif,
            'nama_anggota' => $namaAnggota,
            'name' => rtrim(rtrim($namaAnggota, '*')),
            'phone' => $this->input('no_telp', $this->input('phone')),
            'joined_at' => $tanggalAktif,
            'jenis_anggota' => str_ends_with(trim($namaAnggota), '*') ? 'ALB' : $this->input('jenis_anggota', 'AB'),
            'jenis_kelamin' => $this->input('jenis_kelamin', 'L'),
            'kategori' => $this->input('kategori', 'KOP'),
            'autodebet' => $autodebet,
            'no_rekening' => $autodebet === 'MANUAL' || strtoupper((string) $noRekening) === 'MANUAL' ? null : $noRekening,
        ]);
    }

    private function normalizeDateInput(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return $value;
    }

    public function rules(): array
    {
        $memberId = $this->route('member')?->id;

        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('cooperative_members', 'user_id')->ignore($memberId)],
            'no_anggota' => ['nullable', 'string', 'max:20', Rule::unique('cooperative_members', 'no_anggota')->ignore($memberId)],
            'tanggal_aktif' => ['required', 'date'],
            'nama_anggota' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
            'identity_number' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'jenis_anggota' => ['required', 'in:AB,ALB'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'kategori' => ['required', 'in:IP,CDB,KOP'],
            'autodebet' => ['required', 'in:BNI,BRI,MANUAL'],
            'no_rekening' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'member_login_password' => ['nullable', 'string', 'min:8'],
            'opening_saving_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
