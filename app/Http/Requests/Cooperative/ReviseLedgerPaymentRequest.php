<?php

namespace App\Http\Requests\Cooperative;

use App\Services\Authorization\CooperativeLedgerCorrectionAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class ReviseLedgerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CooperativeLedgerCorrectionAuthorizer::class)->canCorrect($this->user());
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:CASH,TRANSFER,QRIS'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
