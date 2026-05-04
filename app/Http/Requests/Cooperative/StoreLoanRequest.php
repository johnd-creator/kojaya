<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cooperative_member_id' => ['required', 'exists:cooperative_members,id'],
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
