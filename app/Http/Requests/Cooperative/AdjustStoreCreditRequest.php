<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStoreCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'effect' => ['required', 'in:credit,debit'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
