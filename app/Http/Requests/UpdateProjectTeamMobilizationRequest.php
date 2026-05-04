<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectTeamMobilizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:RECRUITMENT,SCREENING,MCU,ONBOARDING,PLACED'],
            'has_ppe' => ['nullable', 'boolean'],
            'has_uniform' => ['nullable', 'boolean'],
        ];
    }
}
