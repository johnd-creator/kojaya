<?php

namespace App\Http\Requests\Api;

use App\Enums\TokenApp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RotateTokenRequest extends FormRequest
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
            'app' => ['nullable', 'string', Rule::enum(TokenApp::class)],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
