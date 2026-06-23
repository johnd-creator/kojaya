<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class PreviewOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_cooperative_opening_balance') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'calculation_start_period' => ['required', 'date_format:Y-m-d'],
            'calculation_end_period' => ['required', 'date_format:Y-m-d', 'after_or_equal:calculation_start_period'],
            'contribution_types' => ['required', 'array', 'min:1'],
            'contribution_types.*' => ['integer', 'exists:cooperative_contribution_types,id'],
            'include_current_month' => ['nullable', 'boolean'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['array'],
            'overrides.*.unit_amount' => ['nullable', 'numeric', 'min:0'],
            'overrides.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'calculation_start_period.required' => 'Periode awal perhitungan wajib diisi.',
            'calculation_end_period.required' => 'Periode akhir perhitungan wajib diisi.',
            'calculation_end_period.after_or_equal' => 'Periode akhir harus sama atau setelah periode awal.',
            'contribution_types.required' => 'Pilih minimal satu kategori simpanan.',
            'contribution_types.*.exists' => 'Kategori simpanan tidak valid.',
        ];
    }
}
