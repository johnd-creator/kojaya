<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCooperativeMemberRequest extends FormRequest
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
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'user_id' => ['prohibited'],
            'member_no' => ['prohibited'],
            'no_anggota' => ['nullable', 'string', 'max:20', Rule::unique('cooperative_members', 'no_anggota')],
            'tanggal_aktif' => ['required', 'date'],
            'nama_anggota' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:40'],
            'joined_at' => ['prohibited'],
            'jenis_anggota' => ['required', 'in:AB,ALB'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'kategori' => ['required', 'in:IP,CDB,KOP'],
            'autodebet' => ['required', 'in:BNI,BRI,MANUAL'],
            'opening_saving_balance' => ['nullable', 'numeric', 'min:0'],
            'organization_id' => ['prohibited'],
            'status' => ['prohibited'],
            'validation_status' => ['prohibited'],
            'resigned_at' => ['prohibited'],
            'validated_at' => ['prohibited'],
            'validated_by' => ['prohibited'],
            'validation_notes' => ['prohibited'],
            'admin_validated_at' => ['prohibited'],
            'admin_validated_by' => ['prohibited'],
            'admin_validation_notes' => ['prohibited'],
            'profile_completed_at' => ['prohibited'],
            'onboarding_submitted_at' => ['prohibited'],
            'member_login_password' => ['prohibited'],
            'identity_number' => ['prohibited'],
            'npwp' => ['prohibited'],
            'no_rekening' => ['prohibited'],
            'nama_bank' => ['prohibited'],
            'nama_pemilik_rekening' => ['prohibited'],
            'notes' => ['prohibited'],
        ];
    }
}
