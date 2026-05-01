<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalCheckupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkup_date' => 'required|date',
            'next_checkup_date' => 'nullable|date|after:checkup_date',
            'result' => 'required|in:FIT,FIT_WITH_RESTRICTION,UNFIT',
            'fit_to_work' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'doctor_name' => 'nullable|string|max:255',
            'clinic_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'checkup_date.required' => 'Checkup date is required',
            'result.required' => 'MCU result is required',
            'next_checkup_date.after' => 'Next checkup date must be after checkup date',
        ];
    }
}
