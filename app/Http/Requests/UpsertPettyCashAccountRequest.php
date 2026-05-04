<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPettyCashAccountRequest extends FormRequest
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
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'limit' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.required' => 'Organisasi wajib dipilih.',
            'organization_id.exists' => 'Organisasi tidak valid.',
            'name.required' => 'Nama kas kecil wajib diisi.',
            'limit.required' => 'Limit kas kecil wajib diisi.',
            'limit.min' => 'Limit tidak boleh negatif.',
            'status.required' => 'Status wajib dipilih.',
        ];
    }
}
