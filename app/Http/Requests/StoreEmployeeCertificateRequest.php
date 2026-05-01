<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certificate_type' => 'required|in:SIO_K3,TRAINING,OTHER',
            'certificate_number' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'issuing_authority' => 'nullable|string|max:255',
            'status' => 'sometimes|in:VALID,EXPIRED,REVOKED',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'certificate_type.required' => 'Certificate type is required',
            'certificate_number.required' => 'Certificate number is required',
            'issue_date.required' => 'Issue date is required',
            'expiry_date.after' => 'Expiry date must be after issue date',
        ];
    }
}
