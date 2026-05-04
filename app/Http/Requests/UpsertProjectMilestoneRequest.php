<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertProjectMilestoneRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'status' => [$this->isMethod('post') ? 'nullable' : 'required', 'in:PENDING,IN_PROGRESS,COMPLETED,OVERDUE'],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
