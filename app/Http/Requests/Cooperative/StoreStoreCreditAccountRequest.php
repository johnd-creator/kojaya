<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoreCreditAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cooperative_member_id' => ['required', 'exists:cooperative_members,id'],
            'credit_limit' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'opening_balance' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
