<?php

namespace App\Http\Requests\Cooperative;

use App\Models\PosProduct;
use App\Services\Cooperative\PosProductAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePosProductRequest extends FormRequest
{
    public function authorize(PosProductAccessService $productAccess): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $product = $this->route('product');
        if ($product) {
            $productModel = $product instanceof PosProduct
                ? $product
                : PosProduct::query()->find($product);

            if ($productModel) {
                $productAccess->assertCanOperate($this->user(), $productModel);
            }
        }

        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $productOrgId = $product instanceof PosProduct
            ? $product->organization_id
            : PosProduct::query()->whereKey($product)->value('organization_id');

        return [
            'pos_category_id' => [
                'nullable',
                Rule::exists('pos_categories', 'id')->where(function ($query) use ($productOrgId): void {
                    if ($productOrgId !== null) {
                        $query->where('organization_id', $productOrgId);
                    } else {
                        $query->whereNull('organization_id');
                    }
                }),
            ],
            'sku' => ['required', 'string', 'max:60', Rule::unique('pos_products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:80', Rule::unique('pos_products', 'barcode')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'remove_image' => ['nullable', 'boolean'],
            'brand' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
            'unit' => ['nullable', 'string', 'max:30'],
            'rack_location' => ['nullable', 'string', 'max:60'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_discontinued' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost_price' => $this->input('cost_price', 0),
            'minimum_stock' => $this->input('minimum_stock', 0),
            'is_active' => $this->boolean('is_active'),
            'is_discontinued' => $this->boolean('is_discontinued'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }
}
