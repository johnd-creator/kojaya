<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class RequestCooperativeMemberRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('validate_cooperative_member') ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
