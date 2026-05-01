<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeFamilyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'relationship' => ['required', Rule::in(['Husband', 'Wife', 'Child'])],
            'birth_date' => 'nullable|date',
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'nik_ktp' => [
                'nullable',
                'string',
                Rule::unique('employee_families')->ignore($this->employee_family->id ?? $this->route('employee_family')),
            ],
            'is_working_here' => 'nullable|boolean',
            'related_employee_id' => 'nullable|exists:employees,id',
        ];
    }
}
