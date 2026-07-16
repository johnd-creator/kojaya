<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CooperativeMemberUserProvisioningService
{
    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * Provision (or reconcile) the user account for a cooperative member.
     *
     * User creation, Anggota role assignment, member linking, and the mandatory
     * audit for each mutation are executed inside a single transaction with one
     * shared AuditContext, so a mandatory audit failure rolls back every partial
     * mutation (no orphaned users, role assignments, or member links).
     *
     * The event name is truthful to the operation that occurred:
     * - member.account.link.completed when the member link changed;
     * - member.role.reconciled when an existing linked user gained the Anggota role;
     * - no audit when nothing changed.
     */
    public function provision(CooperativeMember $member, ?string $plainPassword = null, ?AuditContext $context = null): ?User
    {
        if (! $member->email && ! $member->user_id) {
            return null;
        }

        $context ??= AuditContext::fromCurrentRequest();

        return DB::transaction(function () use ($member, $plainPassword, $context): ?User {
            $user = $member->user;
            $userCreated = false;

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
                $userCreated = true;
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

            $roleAssigned = false;
            if (! $user->hasRole('Anggota')) {
                $user->assignRole('Anggota');
                $roleAssigned = true;
            }

            $linkChanged = (int) $member->user_id !== (int) $user->id;

            if ($linkChanged) {
                $member->forceFill(['user_id' => $user->id])->save();

                $this->audit->log('member.account.link.completed', 'cooperative.member', $member, [
                    'new' => [
                        'affected_user_id' => $user->getKey(),
                        'role_assigned' => $roleAssigned,
                        'link_changed' => true,
                        'user_created' => $userCreated,
                        'operation' => 'link',
                    ],
                    'reason' => 'Cooperative member user provisioning completed.',
                ], $context);
            } elseif ($roleAssigned) {
                $this->audit->log('member.role.reconciled', 'cooperative.member', $member, [
                    'new' => [
                        'affected_user_id' => $user->getKey(),
                        'role_assigned' => true,
                        'link_changed' => false,
                        'user_created' => false,
                        'operation' => 'reconcile_role',
                    ],
                    'reason' => 'Cooperative member Anggota role reconciled for an existing linked user.',
                ], $context);
            }

            return $user;
        });
    }

    private function isPrivilegedUser(User $user): bool
    {
        return $user->hasAnyRole(['System Admin', 'Admin Pusat']);
    }
}
