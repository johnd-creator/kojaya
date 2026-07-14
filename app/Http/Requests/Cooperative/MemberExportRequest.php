<?php

namespace App\Http\Requests\Cooperative;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MemberExportRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const REASON_CODES = [
        'business_verification',
        'regulatory_request',
        'member_correction',
        'internal_audit',
        'other',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::COOPERATIVE_MEMBER_EXPORT->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:32'],
            'jenis_anggota' => ['nullable', 'string', 'max:32'],
            'kategori' => ['nullable', 'string', 'max:32'],
            'include_pii' => ['sometimes', 'boolean'],
            'reason_code' => ['nullable', 'string', Rule::in(self::REASON_CODES)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('include_pii')) {
                return;
            }

            if (filled($this->input('reason_code')) || filled($this->input('reason'))) {
                return;
            }

            $message = 'Alasan atau kode alasan wajib diisi untuk export PII penuh.';
            $validator->errors()->add('reason_code', $message);
            $validator->errors()->add('reason', $message);
        });
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'Alasan wajib diisi untuk export PII penuh.',
        ];
    }
}
