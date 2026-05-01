<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalCheckupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkup_date' => 'sometimes|date',
            'next_checkup_date' => 'nullable|date|after:checkup_date',
            'result' => 'sometimes|in:FIT,FIT_WITH_RESTRICTION,UNFIT',
            'fit_to_work' => 'sometimes|boolean',
            'notes' => 'nullable|string',
            'doctor_name' => 'nullable|string|max:255',
            'clinic_name' => 'nullable|string|max:255',
        ];
    }
}
