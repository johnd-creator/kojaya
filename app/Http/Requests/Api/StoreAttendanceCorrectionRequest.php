<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'corrected_clock_in' => ['nullable', 'date_format:H:i'],
            'corrected_clock_out' => ['nullable', 'date_format:H:i', 'after:corrected_clock_in'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->filled('corrected_clock_in') && ! $this->filled('corrected_clock_out')) {
                $validator->errors()->add('corrected_clock_in', 'At least one corrected time is required.');
            }
        });
    }
}
