<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePettyCashTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'petty_cash_account_id' => ['required', 'exists:petty_cash_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'type' => ['required', 'in:DEBIT,CREDIT'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string'],
            'reference_no' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'petty_cash_account_id.required' => 'Akun kas kecil wajib dipilih.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'type.required' => 'Tipe transaksi wajib dipilih.',
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.min' => 'Nominal transaksi minimal 0,01.',
            'description.required' => 'Deskripsi transaksi wajib diisi.',
        ];
    }
}
