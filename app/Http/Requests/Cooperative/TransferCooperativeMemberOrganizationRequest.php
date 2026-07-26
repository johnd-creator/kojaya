<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferCooperativeMemberOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_organization_id' => [
                'required',
                'uuid',
                Rule::exists('organizations', 'id')->where('is_active', true),
            ],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_organization_id.required' => 'Organisasi tujuan wajib dipilih.',
            'target_organization_id.exists' => 'Organisasi tujuan tidak aktif atau tidak ditemukan.',
            'reason.required' => 'Alasan pemindahan wajib diisi.',
            'reason.min' => 'Alasan pemindahan minimal 10 karakter.',
        ];
    }
}
