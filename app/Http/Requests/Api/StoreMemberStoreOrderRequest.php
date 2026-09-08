<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberStoreOrderRequest extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $orgId = $this->memberOrganizationId();

        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.pos_product_id' => [
                'required',
                Rule::exists('pos_products', 'id')->where(function ($query) use ($orgId): void {
                    if ($orgId !== null) {
                        $query->where('organization_id', $orgId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'client_reference' => ['nullable', 'string', 'max:80'],
            'channel' => ['nullable', 'in:QRIS,VA,E_WALLET,TRANSFER'],
            'payment_method' => ['nullable', 'in:QRIS,VA,E_WALLET,TRANSFER'],
            'fulfillment_method' => ['nullable', 'in:PICKUP,DELIVERY'],
            'pickup_location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang toko minimal berisi satu item.',
            'items.min' => 'Keranjang toko minimal berisi satu item.',
            'items.*.pos_product_id.required' => 'Produk toko wajib dipilih.',
            'items.*.pos_product_id.exists' => 'Produk toko tidak tersedia.',
            'items.*.quantity.max' => 'Maksimal 99 unit per item.',
        ];
    }
}
