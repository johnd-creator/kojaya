<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAssetRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', Rule::unique('assets', 'code')->ignore($this->route('asset'))],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,UNDER_MAINTENANCE'],
            'purchase_date' => ['nullable', 'date'],
            'serial_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode aset wajib diisi.',
            'code.unique' => 'Kode aset sudah digunakan.',
            'name.required' => 'Nama aset wajib diisi.',
            'category.required' => 'Kategori aset wajib diisi.',
            'organization_id.required' => 'Organisasi wajib dipilih.',
            'status.required' => 'Status aset wajib dipilih.',
        ];
    }
}
