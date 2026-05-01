<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_reference' => ['nullable', 'string', 'max:80'],
            'cooperative_member_id' => ['nullable', 'exists:cooperative_members,id'],
            'payment_method' => ['required', 'in:CASH,TRANSFER,QRIS,MEMBER_CREDIT'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_product_id' => ['required', 'exists:pos_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
