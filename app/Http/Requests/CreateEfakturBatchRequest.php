<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEfakturBatchRequest extends FormRequest
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
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['uuid', 'exists:invoices,id'],
            'reference' => ['nullable', 'string'],
        ];
    }
}
