<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveGoodsReceiveNote extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.po_item_id' => ['required', 'uuid'],
            'items.*.received_qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.condition' => ['nullable', 'string', 'max:100'],
        ];
    }
}
