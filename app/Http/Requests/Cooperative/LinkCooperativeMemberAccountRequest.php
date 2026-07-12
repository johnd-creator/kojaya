<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkCooperativeMemberAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $memberId = $this->route('member')?->id;

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('cooperative_members', 'user_id')->ignore($memberId),
            ],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
