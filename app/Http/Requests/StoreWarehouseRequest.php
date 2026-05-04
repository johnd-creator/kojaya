<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:STORAGE,REPAIR,DISPOSAL'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode gudang wajib diisi.',
            'code.unique' => 'Kode gudang sudah digunakan.',
            'name.required' => 'Nama gudang wajib diisi.',
            'organization_id.required' => 'Organisasi wajib dipilih.',
            'type.required' => 'Tipe gudang wajib dipilih.',
        ];
    }
}
