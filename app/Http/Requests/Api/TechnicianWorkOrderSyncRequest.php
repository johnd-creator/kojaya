<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianWorkOrderSyncRequest extends FormRequest
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
            'idempotency_key' => ['required', 'string', 'max:120'],
            'checklists' => ['nullable', 'array'],
            'checklists.*.id' => ['required_with:checklists', 'exists:work_order_checklists,id'],
            'checklists.*.is_checked' => ['required_with:checklists', 'boolean'],
            'checklists.*.notes' => ['nullable', 'string', 'max:1000'],
            'parts' => ['nullable', 'array'],
            'parts.*.spare_part_id' => ['required_with:parts', 'exists:spare_parts,id'],
            'parts.*.warehouse_id' => ['required_with:parts', 'exists:warehouses,id'],
            'parts.*.quantity_used' => ['required_with:parts', 'numeric', 'min:0.01'],
            'parts.*.notes' => ['nullable', 'string', 'max:1000'],
            'completion' => ['nullable', 'array'],
            'completion.latitude' => ['required_with:completion', 'numeric'],
            'completion.longitude' => ['required_with:completion', 'numeric'],
            'completion.accuracy' => ['nullable', 'numeric'],
            'completion.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
