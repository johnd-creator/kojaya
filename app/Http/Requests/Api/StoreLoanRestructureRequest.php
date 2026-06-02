<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRestructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->cooperativeMember !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'proposed_principal_amount' => ['nullable', 'numeric', 'min:1'],
            'proposed_interest_rate' => ['nullable', 'numeric', 'min:0'],
            'proposed_term_months' => ['nullable', 'integer', 'min:1', 'max:240'],
            'proposed_first_due_date' => ['nullable', 'date'],
        ];
    }
}
