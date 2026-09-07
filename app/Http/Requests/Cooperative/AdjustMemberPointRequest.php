<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class AdjustMemberPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
            'organization_id' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'points.required' => 'Jumlah penyesuaian poin harus diisi.',
            'points.integer' => 'Jumlah poin harus berupa bilangan bulat.',
            'points.not_in' => 'Jumlah penyesuaian poin tidak boleh 0.',
            'description.required' => 'Alasan penyesuaian poin harus diisi.',
            'description.max' => 'Alasan penyesuaian tidak boleh melebihi 255 karakter.',
            'organization_id.uuid' => 'ID organisasi harus berupa format UUID yang valid.',
        ];
    }
}
