<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class OpenPosCashierShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'pos_inventory_location_id' => ['nullable', 'exists:pos_inventory_locations,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
