<?php

namespace App\Http\Requests\Cooperative;

use App\Enums\AccountLinkReasonCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnlinkCooperativeMemberAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('member')) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(AccountLinkReasonCode::values())],
        ];
    }
}
