<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianWorkOrderEscalateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:BLOCKED,NEED_PART,NEED_SUPERVISOR,REASSIGNMENT,SAFETY_RISK,OTHER'],
            'reason' => ['required', 'string', 'max:2000'],
            'reassignment_requested_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
