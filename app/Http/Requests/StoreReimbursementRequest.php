<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReimbursementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'submission_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category' => ['required', 'string', 'in:TRANSPORT,MEAL,MEDICAL,LODGING,OFFICE_SUPPLIES,OTHER'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.receipt_date' => ['required', 'date'],
            'items.*.receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'submission_date.required' => 'Tanggal pengajuan wajib diisi.',
            'items.required' => 'Minimal satu item reimbursement wajib diisi.',
            'items.*.category.required' => 'Kategori item wajib diisi.',
            'items.*.description.required' => 'Deskripsi item wajib diisi.',
            'items.*.amount.required' => 'Nominal item wajib diisi.',
            'items.*.receipt_date.required' => 'Tanggal kuitansi wajib diisi.',
        ];
    }
}
