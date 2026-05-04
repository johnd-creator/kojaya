<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPositionRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:30', Rule::unique('positions', 'code')->ignore($this->route('position'))],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'department_id' => ['required', 'exists:departments,id'],
            'job_grade_id' => ['required', 'exists:job_grades,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode posisi wajib diisi.',
            'code.unique' => 'Kode posisi sudah digunakan.',
            'name.required' => 'Nama posisi wajib diisi.',
            'department_id.required' => 'Departemen wajib dipilih.',
            'department_id.exists' => 'Departemen tidak valid.',
            'job_grade_id.required' => 'Job grade wajib dipilih.',
            'job_grade_id.exists' => 'Job grade tidak valid.',
        ];
    }
}
