<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePosCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        try {
            app(\App\Services\Cooperative\PosCategoryAccessService::class)->assertCanCreate($user);

            return true;
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return false;
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pos_categories', 'slug')->where(fn ($query) => $query->where('organization_id', $this->targetOrganizationId())),
            ],
            'is_active' => ['boolean'],
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
            'slug' => $this->input('slug') ?: Str::slug((string) $this->input('name')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
