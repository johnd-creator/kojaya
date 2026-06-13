<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosStockReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pos_supplier_id' => ['nullable', 'exists:pos_suppliers,id'],
            'pos_inventory_location_id' => ['required', 'exists:pos_inventory_locations,id'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_product_id' => ['required', 'exists:pos_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_no' => ['nullable', 'string', 'max:60'],
            'items.*.expired_at' => ['nullable', 'date'],
        ];
    }
}
