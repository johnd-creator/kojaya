<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wajib_default_amount' => ['required', 'numeric', 'min:0'],
            'pokok_default_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'wajib_default_amount.required' => 'Nominal simpanan wajib wajib diisi.',
            'wajib_default_amount.numeric' => 'Nominal simpanan wajib harus berupa angka.',
            'wajib_default_amount.min' => 'Nominal simpanan wajib tidak boleh negatif.',
            'pokok_default_amount.required' => 'Nominal simpanan pokok wajib diisi.',
            'pokok_default_amount.numeric' => 'Nominal simpanan pokok harus berupa angka.',
            'pokok_default_amount.min' => 'Nominal simpanan pokok tidak boleh negatif.',
        ];
    }
}
