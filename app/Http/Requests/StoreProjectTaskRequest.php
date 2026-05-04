<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [$this->expectsJson() ? 'sometimes' : 'required', 'required', 'string', 'max:255'],
            'text' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_task_id' => ['nullable', 'uuid', 'exists:project_tasks,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', $this->expectsJson() ? 'after_or_equal:start_date' : 'after:start_date'],
            'assigned_to' => ['nullable', 'exists:employees,id'],
            'estimated_hours' => [$this->expectsJson() ? 'nullable' : 'required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
