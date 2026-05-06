<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app' => ['required', 'string', 'max:40'],
            'device_id' => ['required', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:40'],
            'push_token' => ['required', 'string', 'max:4096'],
        ];
    }
}
