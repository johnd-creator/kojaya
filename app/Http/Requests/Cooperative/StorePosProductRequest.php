<?php

namespace App\Http\Requests\Cooperative;

use App\Services\Cooperative\PosProductAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosProductRequest extends FormRequest
{
    public function authorize(PosProductAccessService $productAccess): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $productAccess->assertCanCreate($this->user());

        return true;
    }

    public function rules(): array
    {
        return [
            'pos_category_id' => [
                'nullable',
                Rule::exists('pos_categories', 'id')->where(function ($query): void {
                    $orgId = $this->targetOrganizationId();
                    $query->where(function ($q) use ($orgId): void {
                        if ($orgId !== null) {
                            $q->where('organization_id', $orgId)
                                ->orWhereNull('organization_id');
                        } else {
                            $q->whereNull('organization_id');
                        }
                    });
                }),
            ],
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

    public function targetOrganizationId(): ?string
    {
        $user = $this->user();
        if ($user === null) {
            return null;
        }

        if ($user->can('view_cooperative_all')) {
            return session('active_organization_id') ?? ($user->organization_id ? (string) $user->organization_id : null);
        }

        return $user->organization_id ? (string) $user->organization_id : null;
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
