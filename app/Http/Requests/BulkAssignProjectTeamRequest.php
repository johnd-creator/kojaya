<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignProjectTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
            'role' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'daily_rate_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
