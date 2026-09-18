<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteMemberOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->cooperativeMember !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
            'tanggal_lahir' => ['nullable', 'date'],
            'tempat_lahir' => ['nullable', 'string', 'max:120'],
            'pekerjaan' => ['nullable', 'string', 'max:120'],
            // Legacy sensitive fields accepted passively but ignored during safe update
            'email' => ['nullable', 'string'],
            'identity_number' => ['nullable', 'string'],
            'npwp' => ['nullable', 'string'],
            'kategori' => ['nullable', 'string'],
            'perusahaan' => ['nullable', 'string'],
            'jenis_anggota' => ['nullable', 'string'],
            'no_rekening' => ['nullable', 'string'],
            'nama_bank' => ['nullable', 'string'],
            'nama_pemilik_rekening' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.max' => 'Nomor HP maksimal 40 karakter.',
            'address.max' => 'Alamat maksimal 1000 karakter.',
        ];
    }
}
