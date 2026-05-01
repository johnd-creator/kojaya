<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ApprovePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'level' => ['required', 'integer', 'min:1', 'max:3'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
