<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectDocumentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $project = $this->route('project');

        if (is_string($project)) {
            $project = \App\Models\Project::find($project);
        }

        if (! $user || ! $project instanceof \App\Models\Project) {
            return false;
        }

        return $user->can('manageDocuments', $project);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:VALID,EXPIRED'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status dokumen wajib dipilih.',
            'status.in' => 'Status dokumen tidak valid.',
        ];
    }
}
