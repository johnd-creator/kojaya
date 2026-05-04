<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCooperativeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('cooperative_members', 'user_id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'identity_number' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:PENDING,ACTIVE,INACTIVE,RESIGNED'],
            'notes' => ['nullable', 'string'],
            'member_login_password' => ['nullable', 'string', 'min:8'],
            'opening_saving_balance' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
