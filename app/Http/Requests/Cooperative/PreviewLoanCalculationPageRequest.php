<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class PreviewLoanCalculationPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $requiresValidation = $this->filled('loan_type_id')
            || $this->filled('principal_amount')
            || $this->filled('term_months')
            || $this->filled('first_due_date');

        return [
            'loan_type_id' => [$requiresValidation ? 'required' : 'nullable', 'exists:loan_types,id'],
            'principal_amount' => [$requiresValidation ? 'required' : 'nullable', 'numeric', 'min:1'],
            'term_months' => [$requiresValidation ? 'required' : 'nullable', 'integer', 'min:1'],
            'first_due_date' => [$requiresValidation ? 'required' : 'nullable', 'date'],
        ];
    }
}
