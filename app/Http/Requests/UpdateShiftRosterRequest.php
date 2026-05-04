<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'work_shift_id' => ['nullable', 'exists:work_shifts,id'],
            'is_off_day' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
