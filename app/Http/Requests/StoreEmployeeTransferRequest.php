<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'to_organization_id' => ['required', 'uuid', 'exists:organizations,id', 'different:from_organization_id'],
            'effective_date' => ['required', 'date', 'after:today'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
