<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberPortalProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'gender' => ['nullable', 'string', 'in:L,P'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_place' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:60'],
            'bank_account_number' => ['nullable', 'string', 'max:30'],
            'bank_account_holder' => ['nullable', 'string', 'max:160'],
        ];
    }
}
