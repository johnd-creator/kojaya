<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certificate_type' => 'sometimes|in:SIO_K3,TRAINING,OTHER',
            'certificate_number' => 'sometimes|string|max:255',
            'issue_date' => 'sometimes|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'issuing_authority' => 'nullable|string|max:255',
            'status' => 'sometimes|in:VALID,EXPIRED,REVOKED',
            'notes' => 'nullable|string',
        ];
    }
}
