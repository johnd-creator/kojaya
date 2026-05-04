<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:100'],
            'end_date' => ['nullable', 'date'],
            'daily_rate_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:RECRUITMENT,SCREENING,MCU,ONBOARDING,PLACED'],
            'has_ppe' => ['nullable', 'boolean'],
            'has_uniform' => ['nullable', 'boolean'],
        ];
    }
}
