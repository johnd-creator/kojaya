<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $loanTypeId = $this->route('loan_type')?->id;

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('loan_types', 'code')->ignore($loanTypeId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'admin_fee' => ['nullable', 'numeric', 'min:0'],
            'late_fee_per_day' => ['nullable', 'numeric', 'min:0'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'gte:min_amount'],
            'min_term_months' => ['required', 'integer', 'min:1'],
            'max_term_months' => ['required', 'integer', 'gte:min_term_months'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
