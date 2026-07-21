<?php

namespace App\Http\Requests\Cooperative;

use App\Models\MemberStoreAccount;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDelegateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'min:2', 'max:120'],
            'user_id' => ['nullable', 'exists:users,id'],
            'per_transaction_limit' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'daily_limit' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'valid_from' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $userId = $this->input('user_id');

            if ($userId === null) {
                return;
            }

            $account = $this->resolveOwnedAccount();

            if ($account === null) {
                return;
            }

            $userOrg = User::query()->whereKey($userId)->value('organization_id');

            if ((string) $userOrg !== (string) $account->organization_id) {
                $validator->errors()->add('user_id', 'Pengguna delegate harus berada pada organisasi yang sama.');
            }
        });
    }

    private function resolveOwnedAccount(): ?MemberStoreAccount
    {
        $member = $this->user()?->cooperativeMember()->active()->first();

        if ($member === null) {
            return null;
        }

        return MemberStoreAccount::query()
            ->where('organization_id', $member->organization_id)
            ->where('cooperative_member_id', $member->id)
            ->first();
    }
}
