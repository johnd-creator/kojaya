<?php

namespace App\Http\Requests\Cooperative;

use App\Enums\AccountLinkReasonCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkCooperativeMemberAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('member')) ?? false;
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
            'reason' => ['required', 'string', Rule::in(AccountLinkReasonCode::values())],
        ];
    }
}
