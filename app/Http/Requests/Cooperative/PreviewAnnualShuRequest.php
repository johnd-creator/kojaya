<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class PreviewAnnualShuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'cooperative_pool' => ['nullable', 'numeric', 'min:0'],
            'pos_profit_pool' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
