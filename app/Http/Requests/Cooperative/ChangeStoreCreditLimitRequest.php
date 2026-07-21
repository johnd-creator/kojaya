<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStoreCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'credit_limit' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'override_below_debt' => ['nullable', 'boolean'],
        ];
    }
}
