<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->isMethod('post')) {
            return $user->can('create', \App\Models\Budget::class);
        }

        $budget = $this->route('budget');
        if ($budget instanceof \App\Models\Budget) {
            return $user->can('update', $budget);
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'year' => ['required', 'digits:4'],
            'period' => ['required', Rule::in(['ANNUAL', 'Q1', 'Q2', 'Q3', 'Q4'])],
            'status' => [$this->isMethod('post') ? 'nullable' : 'required', Rule::in(['DRAFT', 'ACTIVE', 'CLOSED'])],
        ];

        if ($this->isMethod('post') && $this->user()?->can('view_budget_all')) {
            $rules['organization_id'] = ['nullable', 'uuid', 'exists:organizations,id'];
        } else {
            $rules['organization_id'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_id.exists' => 'Organisasi tidak valid.',
            'year.required' => 'Tahun RKAP wajib diisi.',
            'year.digits' => 'Tahun RKAP harus 4 digit.',
            'period.required' => 'Periode RKAP wajib dipilih.',
            'status.required' => 'Status RKAP wajib dipilih.',
        ];
    }
}
