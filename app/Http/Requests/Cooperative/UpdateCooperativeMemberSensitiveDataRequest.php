<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCooperativeMemberSensitiveDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'identity_number' => ['sometimes', 'nullable', 'string', 'max:40'],
            'npwp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'no_rekening' => ['sometimes', 'nullable', 'string', 'max:30'],
            'nama_bank' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nama_pemilik_rekening' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
