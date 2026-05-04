<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberLoanApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_type_id' => ['required', 'exists:loan_types,id'],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'term_months' => ['required', 'integer', 'min:1'],
            'first_due_date' => ['required', 'date', 'after_or_equal:today'],
            'purpose' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'applied_at' => ['nullable', 'date'],
        ];
    }
}
