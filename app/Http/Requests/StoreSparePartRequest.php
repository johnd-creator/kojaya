<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSparePartRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:spare_parts,code'],
            'name' => ['required', 'string', 'max:255'],
            'specification' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:20'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'max_stock' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'category' => ['nullable', 'string', 'max:100'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode spare part wajib diisi.',
            'code.unique' => 'Kode spare part sudah digunakan.',
            'name.required' => 'Nama spare part wajib diisi.',
            'unit.required' => 'Satuan wajib diisi.',
            'min_stock.required' => 'Stok minimum wajib diisi.',
            'max_stock.required' => 'Stok maksimum wajib diisi.',
            'reorder_level.required' => 'Batas reorder wajib diisi.',
        ];
    }
}
