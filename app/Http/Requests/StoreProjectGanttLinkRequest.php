<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectGanttLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', 'exists:project_tasks,id'],
            'target' => ['required', 'exists:project_tasks,id'],
            'type' => ['nullable', 'string'],
        ];
    }
}
