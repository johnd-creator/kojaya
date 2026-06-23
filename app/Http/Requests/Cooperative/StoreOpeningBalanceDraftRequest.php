<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreOpeningBalanceDraftRequest extends FormRequest
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
            'source_type' => ['required', 'in:MIGRATION_LEDGER,MANUAL_RECONCILIATION,EXCEL_IMPORT,BOARD_DECISION'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_document_date' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_type.in' => 'Tipe sumber harus salah satu dari MIGRATION_LEDGER, MANUAL_RECONCILIATION, EXCEL_IMPORT, atau BOARD_DECISION.',
            'calculation_start_period.required' => 'Periode awal perhitungan wajib diisi.',
            'calculation_end_period.required' => 'Periode akhir perhitungan wajib diisi.',
            'calculation_end_period.after_or_equal' => 'Periode akhir harus sama atau setelah periode awal.',
            'contribution_types.required' => 'Pilih minimal satu kategori simpanan.',
            'overrides.*.reason.required' => 'Override nominal wajib menyertakan alasan.',
        ];
    }

    /**
     * Validasi kondisional: jika `overrides.<id>.unit_amount` diisi dan
     * tidak sama dengan default tarif contribution type, maka `reason`
     * wajib non-empty.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $overrides = $this->input('overrides', []);
            $contributionTypes = collect($this->input('contribution_types', []))
                ->mapWithKeys(function ($id) {
                    $type = \App\Models\CooperativeContributionType::query()->find($id);

                    return $type ? [$id => (float) $type->default_amount] : [$id => null];
                })
                ->all();

            foreach ($overrides as $contributionTypeId => $override) {
                if (! is_array($override) || ! array_key_exists('unit_amount', $override) || $override['unit_amount'] === null || $override['unit_amount'] === '') {
                    continue;
                }

                $unit = (float) $override['unit_amount'];
                $default = $contributionTypes[$contributionTypeId] ?? null;
                $reason = trim((string) ($override['reason'] ?? ''));

                if ($default !== null && $unit !== $default && $reason === '') {
                    $validator->errors()->add(
                        "overrides.{$contributionTypeId}.reason",
                        'Alasan override wajib diisi ketika nominal berbeda dari tarif default.'
                    );
                }
            }
        });
    }
}
