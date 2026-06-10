<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->cooperativeMember !== null;
    }

    public function rules(): array
    {
        $member = $this->user()?->cooperativeMember;

        return [
            'cooperative_dues_invoice_id' => [
                'required',
                'exists:cooperative_dues_invoices,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $invoice = \App\Models\CooperativeDuesInvoice::find($value);

                    if (! $invoice || $invoice->cooperative_member_id !== $this->user()?->cooperativeMember?->id) {
                        $fail('Invoice tidak ditemukan atau bukan milik Anda.');

                        return;
                    }

                    if (! in_array($invoice->status, ['UNPAID', 'PARTIAL'], true)) {
                        $fail('Invoice sudah lunas.');
                    }
                },
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:TRANSFER,QRIS'],
            'paid_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'cooperative_dues_invoice_id.required' => 'Pilih tagihan yang ingin dibayar.',
            'amount.required' => 'Jumlah pembayaran wajib diisi.',
            'amount.min' => 'Jumlah pembayaran minimal Rp 1.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'paid_at.required' => 'Tanggal pembayaran wajib diisi.',
            'proof.required' => 'Upload bukti pembayaran.',
            'proof.mimes' => 'Bukti pembayaran harus berupa gambar (JPG/PNG) atau PDF.',
            'proof.max' => 'Ukuran file maksimal 4MB.',
        ];
    }
}
