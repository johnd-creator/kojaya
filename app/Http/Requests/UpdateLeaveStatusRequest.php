<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateLeaveStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('approve', $this->route('leave'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:Approved,Rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status persetujuan wajib dipilih.',
            'status.in' => 'Status persetujuan harus berupa Approved atau Rejected.',
        ];
    }
}
