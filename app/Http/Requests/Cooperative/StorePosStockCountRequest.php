<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosStockCountRequest extends FormRequest
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
            'pos_inventory_location_id' => ['required', 'exists:pos_inventory_locations,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_product_id' => ['required', 'exists:pos_products,id'],
            'items.*.counted_qty' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
