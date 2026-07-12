<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CooperativeMemberUserProvisioningService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function provision(CooperativeMember $member, ?string $plainPassword = null): ?User
    {
        if (! $member->email && ! $member->user_id) {
            return null;
        }

        $user = $member->user;

        if (! $user && $member->email) {
            if (User::query()->where('email', $member->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Email sudah terdaftar. Pilih akun user secara eksplisit untuk menautkan anggota.',
                ]);
            }

            $user = User::query()->create([
                'name' => $member->name,
                'email' => $member->email,
                'password' => Hash::make($plainPassword ?: Str::password(16)),
                'organization_id' => $member->organization_id,
            ]);
        }

        if (! $user) {
            return null;
        }

        if ($user->cooperativeMember()->whereKeyNot($member->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'User ini sudah tertaut dengan anggota koperasi lain.',
            ]);
        }

        if ((string) $user->organization_id !== (string) $member->organization_id) {
            throw ValidationException::withMessages([
                'user_id' => 'User yang ditautkan harus berada dalam organisasi yang sama.',
            ]);
        }

        if ($this->isPrivilegedUser($user)) {
            throw ValidationException::withMessages([
                'user_id' => 'Akun berprivilege tidak dapat ditautkan sebagai anggota koperasi.',
            ]);
        }

        Role::query()->firstOrCreate(['name' => 'Anggota']);

        if (! $user->hasRole('Anggota')) {
            $user->assignRole('Anggota');
        }

        if ((int) $member->user_id !== (int) $user->id) {
            $member->forceFill(['user_id' => $user->id])->save();
            $this->audit->log('member.account.linked', 'cooperative.member', $member, [
                'new' => ['user_id' => $user->id],
            ]);
        }

        return $user;
    }

    private function isPrivilegedUser(User $user): bool
    {
        return $user->hasAnyRole(['System Admin', 'Admin Pusat']);
    }
}
