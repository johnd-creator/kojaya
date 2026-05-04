<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'uuid', 'exists:assets,id'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'type' => ['required', 'in:PREVENTIVE,CORRECTIVE'],
            'priority' => ['required', 'in:LOW,MEDIUM,HIGH,EMERGENCY'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Aset wajib dipilih.',
            'organization_id.required' => 'Organisasi wajib dipilih.',
            'type.required' => 'Tipe work order wajib dipilih.',
            'priority.required' => 'Prioritas wajib dipilih.',
        ];
    }
}
