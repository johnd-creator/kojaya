<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectTaskProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'actual_hours' => ['required', 'integer', 'min:0'],
        ];
    }
}
