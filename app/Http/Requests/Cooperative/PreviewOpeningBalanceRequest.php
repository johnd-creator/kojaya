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
            'mode' => ['sometimes', 'in:DIRECT,CALCULATED'],
            'cut_off_date' => ['required_if:mode,DIRECT', 'exclude_unless:mode,DIRECT', 'date_format:Y-m-d', 'before_or_equal:today'],
            'direct_amounts' => ['required_if:mode,DIRECT', 'exclude_unless:mode,DIRECT', 'array:POKOK,WAJIB,SUKARELA,KHUSUS'],
            ...array_combine(
                array_map(fn (string $category) => 'direct_amounts.'.$category, \App\Services\Cooperative\CooperativeOpeningBalanceWizardService::CATEGORIES),
                array_fill(0, 4, ['required_if:mode,DIRECT', 'exclude_unless:mode,DIRECT', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2']),
            ),
            'calculation_start_period' => ['exclude_if:mode,DIRECT', 'required_unless:mode,DIRECT', 'date_format:Y-m-d'],
            'calculation_end_period' => ['exclude_if:mode,DIRECT', 'required_unless:mode,DIRECT', 'date_format:Y-m-d', 'after_or_equal:calculation_start_period'],
            'contribution_types' => ['exclude_if:mode,DIRECT', 'required_unless:mode,DIRECT', 'array', 'min:1'],
            'contribution_types.*' => ['exclude_if:mode,DIRECT', 'integer', 'exists:cooperative_contribution_types,id'],
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
