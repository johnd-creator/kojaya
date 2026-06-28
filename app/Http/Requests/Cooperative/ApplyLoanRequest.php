<?php

namespace App\Http\Requests\Cooperative;

use App\Models\LoanType;
use Illuminate\Foundation\Http\FormRequest;

class ApplyLoanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $loanTypeId = $this->input('loan_type_id');

        if (is_string($loanTypeId) && $loanTypeId !== '' && ! ctype_digit($loanTypeId)) {
            $resolvedId = LoanType::query()
                ->where('code', $loanTypeId)
                ->value('id');

            $this->merge([
                'loan_type_id' => $resolvedId ?: 0,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_type_id' => ['required', 'integer', 'exists:loan_types,id'],
            'principal_amount' => ['required', 'numeric', 'min:1'],
            'term_months' => ['required', 'integer', 'min:1'],
            'first_due_date' => ['required', 'date'],
            'purpose' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
