<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->expectsJson()) {
            return [
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'text' => ['sometimes', 'required', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string'],
                'parent_task_id' => ['sometimes', 'nullable', 'uuid', 'exists:project_tasks,id'],
                'start_date' => ['sometimes', 'required', 'date'],
                'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
                'assigned_to' => ['sometimes', 'nullable', 'exists:employees,id'],
                'status' => ['sometimes', 'required', 'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED'],
                'estimated_hours' => ['sometimes', 'integer', 'min:0'],
                'actual_hours' => ['sometimes', 'integer', 'min:0'],
                'progress_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
                'progress' => ['sometimes', 'numeric', 'min:0', 'max:1'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_task_id' => ['nullable', 'uuid', 'exists:project_tasks,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'assigned_to' => ['nullable', 'exists:employees,id'],
            'status' => ['required', 'in:PENDING,IN_PROGRESS,COMPLETED,CANCELLED'],
            'estimated_hours' => ['required', 'integer', 'min:0'],
            'actual_hours' => ['required', 'integer', 'min:0'],
            'progress_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
