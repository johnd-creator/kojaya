<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MemberSupportTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->cooperativeMember !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'in:GENERAL,PAYMENT,LOAN,SAVINGS,POINTS,PROFILE,POS'],
            'priority' => ['nullable', 'string', 'in:LOW,NORMAL,HIGH,URGENT'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
        ];
    }
}
