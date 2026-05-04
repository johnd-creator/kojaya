<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StoreRewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:BARANG,DISKON,LAYANAN'],
            'description' => ['nullable', 'string'],
            'points_required' => ['required', 'integer', 'min:1'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'valid_until' => ['nullable', 'date'],
            'image_url' => ['nullable', 'url'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
