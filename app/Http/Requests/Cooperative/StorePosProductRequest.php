<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pos_category_id' => ['nullable', 'exists:pos_categories,id'],
            'sku' => ['required', 'string', 'max:60', 'unique:pos_products,sku'],
            'barcode' => ['nullable', 'string', 'max:80', 'unique:pos_products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:30'],
            'rack_location' => ['nullable', 'string', 'max:60'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_discontinued' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost_price' => $this->input('cost_price', 0),
            'stock' => $this->input('stock', 0),
            'minimum_stock' => $this->input('minimum_stock', 0),
            'is_active' => $this->boolean('is_active', true),
            'is_discontinued' => $this->boolean('is_discontinued'),
        ]);
    }
}
