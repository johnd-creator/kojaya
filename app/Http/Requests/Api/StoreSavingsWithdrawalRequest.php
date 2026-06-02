<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->cooperativeMember !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'destination_bank' => ['nullable', 'string', 'max:100'],
            'destination_account_no' => ['nullable', 'string', 'max:100'],
            'destination_account_name' => ['nullable', 'string', 'max:150'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
