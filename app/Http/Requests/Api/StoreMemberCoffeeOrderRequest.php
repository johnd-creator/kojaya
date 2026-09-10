<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberCoffeeOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function memberOrganizationId(): ?string
    {
        $member = $this->user()?->cooperativeMember()->active()->first();

        return $member?->organization_id ? (string) $member->organization_id : ($this->user()?->organization_id ? (string) $this->user()->organization_id : null);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->memberOrganizationId();

        $productExistsRule = Rule::exists('pos_products', 'id')->where(function ($query) use ($orgId): void {
            if ($orgId !== null) {
                $query->where('organization_id', $orgId);
            } else {
                $query->whereRaw('1 = 0');
            }
        });

        return [
            'pos_product_id' => ['required_without:items', $productExistsRule],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:12'],
            'items' => ['nullable', 'array', 'min:1', 'max:12'],
            'items.*.pos_product_id' => ['required_with:items', $productExistsRule],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:12'],
            'items.*.sugar_level' => ['nullable', 'in:Normal,Less Sugar,No Sugar'],
            'items.*.ice_level' => ['nullable', 'in:Normal,Less Ice,Warm'],
            'items.*.cup_size' => ['nullable', 'in:Reguler,Large'],
            'client_reference' => ['nullable', 'string', 'max:80'],
            'channel' => ['nullable', 'in:QRIS,VA,E_WALLET,TRANSFER'],
            'payment_method' => ['nullable', 'in:QRIS,VA,E_WALLET,TRANSFER'],
            'sugar_level' => ['nullable', 'in:Normal,Less Sugar,No Sugar'],
            'ice_level' => ['nullable', 'in:Normal,Less Ice,Warm'],
            'cup_size' => ['nullable', 'in:Reguler,Large'],
        ];
    }

    public function messages(): array
    {
        return [
            'pos_product_id.required_without' => 'Menu kopi wajib dipilih.',
            'pos_product_id.exists' => 'Menu kopi tidak tersedia.',
            'items.min' => 'Keranjang kopi minimal berisi satu item.',
            'items.*.pos_product_id.required_with' => 'Menu kopi wajib dipilih.',
            'items.*.pos_product_id.exists' => 'Menu kopi tidak tersedia.',
            'quantity.max' => 'Maksimal 12 cup per pesanan.',
            'items.*.quantity.max' => 'Maksimal 12 cup per item.',
        ];
    }
}
