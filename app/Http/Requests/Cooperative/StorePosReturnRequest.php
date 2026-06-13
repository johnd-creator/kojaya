<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StorePosReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $routeTransaction = $this->route('transaction');
        if ($routeTransaction !== null && ! $this->has('pos_transaction_id')) {
            $this->merge([
                'pos_transaction_id' => is_object($routeTransaction) ? $routeTransaction->getKey() : $routeTransaction,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'pos_transaction_id' => ['required', 'integer', 'exists:pos_transactions,id'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pos_transaction_item_id' => ['required', 'integer', 'exists:pos_transaction_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
