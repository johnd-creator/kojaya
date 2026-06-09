<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class ValidateCooperativeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('validate_cooperative_member') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
