<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransferBatchRequest extends FormRequest
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
            'bank_name' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'format' => ['in:CSV,XML,FW'],
            'batch_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.beneficiary_name' => ['required', 'string'],
            'items.*.beneficiary_account' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.currency' => ['in:IDR'],
            'items.*.reference' => ['nullable', 'string'],
            'items.*.invoice_id' => ['nullable', 'uuid', 'exists:invoices,id'],
        ];
    }
}
