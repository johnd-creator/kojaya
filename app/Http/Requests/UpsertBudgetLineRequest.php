<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertBudgetLineRequest extends FormRequest
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
            'cost_center' => ['nullable', 'string', 'max:50'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'gl_account' => [
                'required',
                'string',
                'max:50',
                Rule::unique('budget_lines')->where(function ($query) {
                    return $query->where('budget_id', $this->route('budget')?->id)
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
            'gl_account.required' => 'Akun GL wajib diisi.',
            'gl_account.unique' => 'Akun GL sudah digunakan pada kombinasi budget ini.',
            'category.required' => 'Kategori budget line wajib dipilih.',
            'allocated_amount.required' => 'Nilai alokasi wajib diisi.',
            'allocated_amount.min' => 'Nilai alokasi tidak boleh negatif.',
        ];
    }
}
