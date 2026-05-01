<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'invoice_no' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'amount' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'status' => ['nullable', Rule::in(['DRAFT', 'PENDING', 'APPROVED', 'PAID', 'OVERDUE', 'CANCELLED'])],
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'The client field is required.',
            'invoice_no.required' => 'The invoice number is required.',
            'amount.required' => 'The amount field is required.',
            'due_date.after' => 'The due date must be after the invoice date.',
            'status.in' => 'The status must be one of: DRAFT, PENDING, APPROVED, PAID, OVERDUE, CANCELLED.',
        ];
    }
}
