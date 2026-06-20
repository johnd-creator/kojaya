<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberCoffeeOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pos_product_id' => ['required', 'exists:pos_products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:12'],
            'client_reference' => ['nullable', 'string', 'max:80'],
            'payment_method' => ['nullable', 'in:QRIS,CASH,TRANSFER,MEMBER_CREDIT'],
            'sugar_level' => ['nullable', 'in:Normal,Less Sugar,No Sugar'],
            'ice_level' => ['nullable', 'in:Normal,Less Ice,Warm'],
            'cup_size' => ['nullable', 'in:Reguler,Large'],
        ];
    }

    public function messages(): array
    {
        return [
            'pos_product_id.required' => 'Menu kopi wajib dipilih.',
            'pos_product_id.exists' => 'Menu kopi tidak tersedia.',
            'quantity.max' => 'Maksimal 12 cup per pesanan.',
        ];
    }
}
