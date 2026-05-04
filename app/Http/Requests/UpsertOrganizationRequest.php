<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('organizations', 'code')->ignore($this->route('organization'))],
            'type' => ['required', 'in:HEAD_OFFICE,REGIONAL,BRANCH,SITE'],
            'level' => ['required', 'in:L0,L1,L2,L3'],
            'parent_id' => ['nullable', 'exists:organizations,id'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama organisasi wajib diisi.',
            'code.required' => 'Kode organisasi wajib diisi.',
            'code.unique' => 'Kode organisasi sudah digunakan.',
            'type.required' => 'Tipe organisasi wajib dipilih.',
            'level.required' => 'Level organisasi wajib dipilih.',
            'email.email' => 'Format email organisasi tidak valid.',
        ];
    }
}
