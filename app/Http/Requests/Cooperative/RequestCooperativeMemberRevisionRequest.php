<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class RequestCooperativeMemberRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can('verify_cooperative_member')
            || $user?->can('approve_cooperative_member')
            || $user?->can('validate_cooperative_member')
            || false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
