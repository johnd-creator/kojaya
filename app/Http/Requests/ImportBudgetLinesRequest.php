<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBudgetLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $budget = $this->route('budget');

        if (! $user || ! ($budget instanceof \App\Models\Budget)) {
            return false;
        }

        return $user->can('import', $budget);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,csv'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File import RKAP wajib diunggah.',
            'file.mimes' => 'File import harus berformat XLSX atau CSV.',
        ];
    }
}
