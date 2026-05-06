<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EssReimbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'submission_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['required', 'string', 'in:TRANSPORT,MEAL,MEDICAL,LODGING,OFFICE_SUPPLIES,OTHER'],
            'items.*.description' => ['required', 'string', 'max:1000'],
            'items.*.amount' => ['required', 'numeric', 'min:1'],
            'items.*.receipt_date' => ['required', 'date'],
            'items.*.receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }
}
