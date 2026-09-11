<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertBudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $budget = $this->route('budget');

        if (! $user || ! ($budget instanceof \App\Models\Budget)) {
            return false;
        }

        return $user->can('update', $budget);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $budget = $this->route('budget');
        $organizationId = $budget instanceof \App\Models\Budget ? $budget->organization_id : null;

        return [
            'cost_center' => ['nullable', 'string', 'max:50'],
            'project_id' => [
                'nullable',
                'uuid',
                Rule::exists('projects', 'id')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                }),
            ],
            'gl_account' => [
                'required',
                'string',
                'max:50',
                Rule::unique('budget_lines')->where(function ($query) use ($budget) {
                    return $query->where('budget_id', $budget?->id)
                        ->where('project_id', $this->input('project_id'))
                        ->where('cost_center', $this->input('cost_center'));
                })->ignore($this->route('line')?->id),
            ],
            'category' => ['required', Rule::in(['OPEX', 'CAPEX'])],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.exists' => 'Project tidak valid.',
            'gl_account.required' => 'Akun GL wajib diisi.',
            'gl_account.unique' => 'Akun GL sudah digunakan pada kombinasi budget ini.',
            'category.required' => 'Kategori budget line wajib dipilih.',
            'allocated_amount.required' => 'Nilai alokasi wajib diisi.',
            'allocated_amount.min' => 'Nilai alokasi tidak boleh negatif.',
        ];
    }
}
