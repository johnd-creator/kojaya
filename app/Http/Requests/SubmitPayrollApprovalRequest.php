<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPayrollApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payroll_ids' => ['required', 'array'],
            'payroll_ids.*' => ['integer', 'exists:payrolls,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
