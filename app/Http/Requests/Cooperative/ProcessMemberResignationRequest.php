<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class ProcessMemberResignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:APPROVE,REJECT'],
            'review_notes' => ['nullable', 'required_if:decision,REJECT', 'string', 'max:1000'],
        ];
    }
}
