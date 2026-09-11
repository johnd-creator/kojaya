<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $project = $this->route('project');

        if (is_string($project)) {
            $project = Project::find($project);
        }

        if (! $user || ! $project instanceof Project) {
            return false;
        }

        return $user->can('update', $project);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:SIKA,PERMIT,DRAWING,OTHER'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'project_id' => ['prohibited'],
            'organization_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'owner_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama dokumen wajib diisi.',
            'type.required' => 'Tipe dokumen wajib dipilih.',
            'file.required' => 'File dokumen wajib diunggah.',
            'file.mimes' => 'File dokumen harus PDF, JPG, JPEG, atau PNG.',
            'expiry_date.after' => 'Tanggal kedaluwarsa harus setelah hari ini.',
            'project_id.prohibited' => 'Kolom project_id tidak diizinkan.',
            'organization_id.prohibited' => 'Kolom organization_id tidak diizinkan.',
            'user_id.prohibited' => 'Kolom user_id tidak diizinkan.',
            'owner_id.prohibited' => 'Kolom owner_id tidak diizinkan.',
        ];
    }
}
