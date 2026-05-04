<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertSalaryStructureRequest extends FormRequest
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
            'employee_type' => ['required', 'in:TKWT,Organic'],
            'job_grade_id' => ['required', 'exists:job_grades,id'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'min_tenure_months' => ['integer', 'min:0'],
            'max_tenure_months' => ['nullable', 'integer', 'min:0', 'gte:min_tenure_months'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after:effective_from'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.component_type_id' => ['required', 'exists:salary_component_types,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_type.required' => 'Tipe karyawan wajib dipilih.',
            'job_grade_id.required' => 'Job grade wajib dipilih.',
            'effective_from.required' => 'Tanggal efektif wajib diisi.',
            'items.required' => 'Komponen gaji wajib diisi.',
            'items.*.component_type_id.required' => 'Jenis komponen gaji wajib dipilih.',
            'items.*.amount.required' => 'Nominal komponen gaji wajib diisi.',
        ];
    }
}
