<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee')?->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'gender' => ['required', 'in:M,F'],
            'birth_date' => ['nullable', 'date'],
            'hire_date' => ['required', 'date'],
            'status' => ['required', 'string'],
            'employee_type' => ['required', 'in:Organic,TKWT'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'job_grade_id' => ['nullable', 'exists:job_grades,id'],
            'work_shift_id' => ['nullable', 'exists:work_shifts,id'],
            'shift_group' => ['nullable', 'in:A,B,C,D'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Nama depan wajib diisi.',
            'employee_code.required' => 'Kode karyawan wajib diisi.',
            'employee_code.unique' => 'Kode karyawan sudah digunakan.',
            'organization_id.required' => 'Organisasi wajib dipilih.',
            'employee_type.required' => 'Tipe karyawan wajib dipilih.',
        ];
    }
}
