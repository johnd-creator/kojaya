<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_transaction_id' => ['required', 'exists:pos_transactions,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_transaction_item_id' => ['required', 'exists:pos_transaction_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
