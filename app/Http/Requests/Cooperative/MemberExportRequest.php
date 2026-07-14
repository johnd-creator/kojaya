<?php

namespace App\Http\Requests\Cooperative;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class MemberExportRequest extends FormRequest
{
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
            'reason' => ['required_if:include_pii,1', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'Alasan wajib diisi untuk export PII penuh.',
        ];
    }
}
