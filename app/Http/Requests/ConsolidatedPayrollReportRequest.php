<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsolidatedPayrollReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_from' => ['required', 'date_format:Y-m'],
            'period_to' => ['required', 'date_format:Y-m', 'after_or_equal:period_from'],
        ];
    }
}
