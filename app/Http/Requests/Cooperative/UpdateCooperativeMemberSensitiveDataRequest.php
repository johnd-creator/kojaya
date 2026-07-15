<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCooperativeMemberSensitiveDataRequest extends FormRequest
{
    private const MASK_PATTERN = '/^\*+/';

    /** @var list<string> */
    private const MASKED_FIELDS = [
        'identity_number',
        'npwp',
        'no_rekening',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'identity_number' => ['sometimes', 'nullable', 'string', 'max:40'],
            'npwp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'no_rekening' => ['sometimes', 'nullable', 'string', 'max:30'],
            'nama_bank' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nama_pemilik_rekening' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (self::MASKED_FIELDS as $field) {
                $value = $this->input($field);

                if ($value !== null && is_string($value) && preg_match(self::MASK_PATTERN, $value)) {
                    $validator->errors()->add(
                        $field,
                        'Nilai masked tidak dapat disimpan. Kosongkan field untuk mempertahankan nilai existing atau isi nilai asli.',
                    );
                }
            }
        });
    }

    /**
     * Determine which sensitive fields should be cleared (explicit null).
     *
     * @return list<string>
     */
    public function fieldsToClear(): array
    {
        $clear = [];

        foreach (self::MASKED_FIELDS as $field) {
            if ($this->input($field) === null && $this->exists($field)) {
                $clear[] = $field;
            }
        }

        return $clear;
    }
}
