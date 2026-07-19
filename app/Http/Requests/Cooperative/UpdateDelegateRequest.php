<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDelegateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'per_transaction_limit' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'daily_limit' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'valid_from' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }
}
