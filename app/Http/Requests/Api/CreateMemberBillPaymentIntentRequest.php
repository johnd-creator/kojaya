<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateMemberBillPaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('member:write') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'in:QRIS,VA,E_WALLET,TRANSFER'],
        ];
    }
}
