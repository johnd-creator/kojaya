<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class BulkApprovePaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_cooperative_payment') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['required', 'integer', 'exists:cooperative_payments,id'],
        ];
    }
}
