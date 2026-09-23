<?php

namespace App\Http\Requests\Cooperative;

use App\Services\Authorization\CooperativeLedgerCorrectionAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class CancelLedgerPaymentRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
