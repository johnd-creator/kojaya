<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'client_reference' => ['nullable', 'string', 'max:80'],
            'cooperative_member_id' => ['nullable', 'exists:cooperative_members,id'],
            'payment_method' => ['required_without:payments', 'in:CASH,TRANSFER,QRIS,MEMBER_CREDIT,MEMBER_STORE_ACCOUNT'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'store_delegate_code' => ['nullable', 'string', 'max:40', 'required_with:store_delegate_pin'],
            'store_delegate_pin' => ['nullable', 'string', 'max:32', 'required_with:store_delegate_code'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_product_id' => ['required', 'exists:pos_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'payments' => ['required_without:payment_method', 'array', 'min:1'],
            'payments.*.payment_method' => ['required_with:payments', 'in:CASH,TRANSFER,QRIS,MEMBER_CREDIT,MEMBER_STORE_ACCOUNT'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0'],
            'payments.*.reference_no' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.quantity.max' => 'Kuantitas per item terlalu besar (maksimal 9999).',
            'store_delegate_code.required_with' => 'Kode delegate wajib diisi bila PIN delegate dikirim.',
            'store_delegate_pin.required_with' => 'PIN delegate wajib diisi bila kode delegate dikirim.',
        ];
    }
}
