<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertJobGradeRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:30', Rule::unique('job_grades', 'code')->ignore($this->route('job_grade'))],
            'name' => ['required', 'string', 'max:100'],
            'level' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode job grade wajib diisi.',
            'code.unique' => 'Kode job grade sudah digunakan.',
            'name.required' => 'Nama job grade wajib diisi.',
            'level.required' => 'Level wajib diisi.',
            'level.min' => 'Level minimal 1.',
        ];
    }
}
