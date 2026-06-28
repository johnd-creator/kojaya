<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'email_enabled' => ['sometimes', 'boolean'],
            'database_enabled' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
            'whatsapp_phone' => ['nullable', 'string', 'max:40'],
            'channels' => ['sometimes', 'array'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['array'],
            'categories.*.*' => ['string', 'in:database,push,whatsapp,mail'],
        ];
    }
}
