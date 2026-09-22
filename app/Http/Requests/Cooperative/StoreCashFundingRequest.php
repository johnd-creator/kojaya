<?php

namespace App\Http\Requests\Cooperative;

use App\Http\Requests\Cooperative\Concerns\ValidatesFundingIdempotency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCashFundingRequest extends FormRequest
{
    use ValidatesFundingIdempotency;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareFundingIdempotency();
    }

    public function rules(): array
    {
        return array_merge([
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], $this->idempotencyRules());
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateFundingIdempotency($validator);
    }
}
