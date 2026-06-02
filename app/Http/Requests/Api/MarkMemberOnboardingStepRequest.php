<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkMemberOnboardingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'step' => ['required', 'string', Rule::in(['profile', 'kyc', 'first_savings', 'loans', 'rewards'])],
        ];
    }
}
