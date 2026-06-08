<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class StoreCooperativePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cooperative_member_id' => ['required', 'exists:cooperative_members,id'],
            'cooperative_dues_invoice_id' => ['nullable', 'required_without:cooperative_contribution_type_id', 'exists:cooperative_dues_invoices,id'],
            'cooperative_contribution_type_id' => ['nullable', 'required_without:cooperative_dues_invoice_id', 'exists:cooperative_contribution_types,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:CASH,TRANSFER,QRIS'],
            'paid_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:PENDING,APPROVED'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
        ];
    }
}
