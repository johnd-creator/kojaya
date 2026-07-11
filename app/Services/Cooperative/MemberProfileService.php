<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
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
            $before = $member->only(['name', 'email', 'phone', 'identity_number', 'npwp', 'no_rekening']);

            if ($member->sso_provider !== null && $user->email !== $attributes['email']) {
                throw ValidationException::withMessages([
                    'email' => 'Email akun SSO hanya dapat diubah melalui flow verifikasi akun.',
                ]);
            }

            $user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ]);

            $member->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'address' => $attributes['address'] ?? null,
                'jenis_kelamin' => $attributes['gender'] ?? null,
                'tanggal_lahir' => $attributes['birth_date'] ?? null,
                'tempat_lahir' => $attributes['birth_place'] ?? null,
                'pekerjaan' => $attributes['occupation'] ?? null,
                'npwp' => $attributes['npwp'] ?? null,
                'nama_bank' => $attributes['bank_name'] ?? null,
                'no_rekening' => $attributes['bank_account_number'] ?? null,
                'nama_pemilik_rekening' => $attributes['bank_account_holder'] ?? null,
            ]);

            $member = $member->refresh();
            $this->audit->log('member.profile.updated', 'cooperative.member', $member, [
                'old' => $before,
                'new' => $member->only(['name', 'email', 'phone', 'identity_number', 'npwp', 'no_rekening']),
                'reason' => 'Member updated own profile.',
            ]);

            return $member;
        });
    }
}
