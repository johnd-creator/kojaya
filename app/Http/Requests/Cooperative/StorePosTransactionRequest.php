<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['prohibited'],
            'pos_cashier_shift_id' => ['nullable', 'integer', 'exists:pos_cashier_shifts,id'],
            'client_reference' => ['nullable', 'string', 'max:80'],
            'cooperative_member_id' => ['nullable', 'exists:cooperative_members,id'],
            'payment_method' => ['required_without:payments', 'in:CASH,TRANSFER,QRIS,MEMBER_CREDIT,MEMBER_STORE_ACCOUNT'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'purchaser_name' => ['nullable', 'string', 'min:2', 'max:120'],
            'purchase_note' => ['nullable', 'string', 'max:500'],
            'store_delegate_code' => ['nullable', 'string', 'max:40'],
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
            'purchaser_name.required' => 'Nama yang berbelanja wajib diisi untuk pembayaran Saldo Toko.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $payments = $this->input('payments');
            $usesStoreAccount = $this->input('payment_method') === 'MEMBER_STORE_ACCOUNT'
                || (is_array($payments) && collect($payments)->contains(
                    fn (mixed $payment): bool => is_array($payment)
                        && ($payment['payment_method'] ?? null) === 'MEMBER_STORE_ACCOUNT'
                ));

            if ($usesStoreAccount && trim((string) $this->input('purchaser_name')) === '') {
                $validator->errors()->add('purchaser_name', 'Nama yang berbelanja wajib diisi untuk pembayaran Saldo Toko.');
            }
        });
    }
}
