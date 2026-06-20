<?php

namespace App\Http\Requests\Cooperative;

use App\Models\CoffeeOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoffeeOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('access_cooperative_pos') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(CoffeeOrder::statuses())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
