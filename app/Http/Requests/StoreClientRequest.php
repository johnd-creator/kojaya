<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:clients,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'tax_id' => 'nullable|string|max:50',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'client_type' => 'required|in:PLN,PRIVATE',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode client wajib diisi.',
            'code.unique' => 'Kode client sudah ada.',
            'name.required' => 'Nama client wajib diisi.',
            'contact_person.required' => 'Nama penanggung jawab wajib diisi.',
            'phone.required' => 'No telepon wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'client_type.required' => 'Tipe client wajib diisi.',
            'client_type.in' => 'Tipe client harus PLN atau PRIVATE.',
            'organization_id.exists' => 'Organization tidak ditemukan.',
        ];
    }
}
