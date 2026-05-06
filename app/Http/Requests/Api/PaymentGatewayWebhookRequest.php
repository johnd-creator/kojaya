<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PaymentGatewayWebhookRequest extends FormRequest
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
            'order_id' => ['nullable', 'string', 'max:120'],
            'transaction_id' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:120'],
            'gateway_reference' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:40'],
            'status_code' => ['nullable', 'string', 'max:20'],
            'transaction_status' => ['nullable', 'string', 'max:40'],
            'fraud_status' => ['nullable', 'string', 'max:40'],
            'gross_amount' => ['nullable', 'numeric'],
            'payment_type' => ['nullable', 'string', 'max:40'],
            'signature_key' => ['nullable', 'string', 'max:256'],
            'reconciliation_reference' => ['nullable', 'string', 'max:120'],
        ];
    }
}
