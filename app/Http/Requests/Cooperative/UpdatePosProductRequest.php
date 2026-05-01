<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePosProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'pos_category_id' => ['nullable', 'exists:pos_categories,id'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('pos_products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('pos_products', 'barcode')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost_price' => $this->input('cost_price', 0),
            'minimum_stock' => $this->input('minimum_stock', 0),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
