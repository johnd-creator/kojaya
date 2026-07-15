<?php

namespace App\Services\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemberProfileService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, CooperativeMember $member, array $attributes): CooperativeMember
    {
        return DB::transaction(function () use ($user, $member, $attributes): CooperativeMember {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $sensitiveInput = ['npwp', 'bank_account_number'];
            if (array_intersect(array_keys($attributes), $sensitiveInput) !== []
                && $member->user_id !== $user->id
                && ! $user->can(PermissionEnum::COOPERATIVE_MEMBER_PII_WRITE->value)) {
                throw new AuthorizationException('Sensitive member data requires dedicated authorization.');
            }

            if ($member->sso_provider !== null && $user->email !== $attributes['email']) {
                throw ValidationException::withMessages([
                    'email' => 'Email akun SSO hanya dapat diubah melalui flow verifikasi akun.',
                ]);
            }

            $user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ]);

            $memberAttributes = [
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'address' => $attributes['address'] ?? null,
                'jenis_kelamin' => $attributes['gender'] ?? null,
                'tanggal_lahir' => $attributes['birth_date'] ?? null,
                'tempat_lahir' => $attributes['birth_place'] ?? null,
                'pekerjaan' => $attributes['occupation'] ?? null,
                'nama_bank' => $attributes['bank_name'] ?? null,
                'nama_pemilik_rekening' => $attributes['bank_account_holder'] ?? null,
            ];

            if (array_key_exists('npwp', $attributes)) {
                $memberAttributes['npwp'] = $attributes['npwp'];
            }

            if (array_key_exists('bank_account_number', $attributes)) {
                $memberAttributes['no_rekening'] = $attributes['bank_account_number'];
            }

            $member->update($memberAttributes);
            $changedFields = array_values(array_unique(array_keys($member->getChanges())));

            $member = $member->refresh();
            $this->audit->log('member.profile.updated', 'cooperative.member', $member, [
                'new' => [
                    'fields' => $changedFields,
                ],
                'reason' => 'Member updated own profile.',
            ], AuditContext::forActor($user));

            return $member;
        });
    }
}
