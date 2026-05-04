<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
        $projectId = $this->route('project')?->id;

        return [
            'project_code' => ['required', 'string', 'max:50', Rule::unique('projects', 'project_code')->ignore($projectId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'budget' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:PLANNING,ONGOING,ON_HOLD,COMPLETED,CANCELLED'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_code.required' => 'Kode proyek wajib diisi.',
            'project_code.unique' => 'Kode proyek sudah digunakan.',
            'name.required' => 'Nama proyek wajib diisi.',
            'organization_id.required' => 'Organisasi wajib dipilih.',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ];
    }
}
