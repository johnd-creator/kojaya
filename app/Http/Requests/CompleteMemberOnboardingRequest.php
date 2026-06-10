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
        $member = $this->user()?->cooperativeMember;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'phone' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:1000'],
            'identity_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('cooperative_members', 'identity_number')
                    ->ignore($member?->id)
                    ->whereNull('deleted_at'),
            ],
            'npwp' => ['nullable', 'string', 'max:32'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'kategori' => ['required', Rule::in(['IP', 'CDB', 'KOP'])],
            'tanggal_lahir' => ['nullable', 'date'],
            'tempat_lahir' => ['nullable', 'string', 'max:120'],
            'pekerjaan' => ['nullable', 'string', 'max:120'],
            'no_rekening' => ['nullable', 'string', 'max:30'],
            'nama_bank' => ['nullable', 'string', 'max:60'],
            'nama_pemilik_rekening' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'address.required' => 'Alamat wajib diisi.',
            'identity_number.required' => 'Nomor identitas wajib diisi.',
            'identity_number.unique' => 'Nomor identitas sudah dipakai anggota lain.',
            'jenis_kelamin.required' => 'Pilih jenis kelamin.',
            'kategori.required' => 'Pilih perusahaan.',
        ];
    }
}
