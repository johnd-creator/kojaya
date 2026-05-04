<?php

namespace App\Http\Requests;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GeneratePayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Payroll::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'period.required' => 'Periode payroll wajib diisi.',
            'period.date_format' => 'Format periode payroll harus YYYY-MM.',
            'organization_id.required' => 'Organisasi wajib dipilih.',
        ];
    }
}
