<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePosCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $category = $this->route('category');
        if ($category instanceof \App\Models\PosCategory) {
            app(\App\Services\Cooperative\PosCategoryAccessService::class)->assertCanOperate($user, $category);
        }

        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category instanceof \App\Models\PosCategory ? $category->id : $category;
        $targetOrgId = $category instanceof \App\Models\PosCategory
            ? $category->organization_id
            : \App\Models\PosCategory::query()->whereKey($categoryId)->value('organization_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pos_categories', 'slug')
                    ->where(fn ($query) => $query->where('organization_id', $targetOrgId))
                    ->ignore($categoryId),
            ],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
