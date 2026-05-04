<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertWorkShiftRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50', Rule::unique('work_shifts', 'name')->ignore($this->route('work_shift'))],
            'type' => ['required', 'in:SHIFT,NON_SHIFT'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_flexible' => ['boolean'],
            'flexible_minutes' => ['integer', 'min:0', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama shift wajib diisi.',
            'name.unique' => 'Nama shift sudah digunakan.',
            'type.required' => 'Tipe shift wajib dipilih.',
            'start_time.required' => 'Jam mulai wajib diisi.',
            'end_time.required' => 'Jam selesai wajib diisi.',
        ];
    }
}
