<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRedemptionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'organization_id' => ['prohibited'],
        ];
    }
}
