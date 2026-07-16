<?php

namespace App\Services\Security;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class UserRoleManagementService
{
    /**
     * @param  array{name: string, email: string, password: string, role: string, organization_id: string}  $data
     */
    public function createUserWithAudit(array $data, User $actor, AuditContext $context): User
    {
        $this->assertActorContext($actor, $context);

        return DB::transaction(function () use ($data, $actor, $context): User {
            $previousRoles = [];

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'organization_id' => $data['organization_id'],
            ]);

            $role = Role::query()->where('name', $data['role'])->firstOrFail();
            $user->assignRole($role);

            $resultingRoles = $user->getRoleNames()->values()->all();

            $this->audit->log('user.role.mutated', 'security.users', $user, [
                'old' => [
                    'previous_roles' => $previousRoles,
                    'previous_organization_id' => null,
                ],
                'new' => [
                    'operation' => 'create',
                    'resulting_roles' => $resultingRoles,
                    'affected_user_id' => $user->getKey(),
                    'resulting_organization_id' => $user->organization_id,
                    'role_assigned' => $data['role'],
                    'actor_id' => $actor->getKey(),
                    'actor_roles' => $actor->getRoleNames()->values()->all(),
                ],
                'reason' => 'User created with role assignment.',
            ], $context);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, password?: string, role: string, organization_id: string}  $data
     */
    public function updateUserWithAudit(User $user, array $data, User $actor, AuditContext $context): User
    {
        $this->assertActorContext($actor, $context);

        return DB::transaction(function () use ($user, $data, $actor, $context): User {
            $previousRoles = $user->getRoleNames()->values()->all();
            $previousOrganizationId = $user->organization_id;

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'organization_id' => $data['organization_id'],
            ]);

            if (isset($data['password']) && $data['password'] !== '') {
                $user->update(['password' => Hash::make($data['password'])]);
            }

            $user->syncRoles([$data['role']]);

            $resultingRoles = $user->fresh()->getRoleNames()->values()->all();

            $this->audit->log('user.role.mutated', 'security.users', $user, [
                'old' => [
                    'previous_roles' => $previousRoles,
                    'previous_organization_id' => $previousOrganizationId,
                ],
                'new' => [
                    'operation' => 'update',
                    'resulting_roles' => $resultingRoles,
                    'affected_user_id' => $user->getKey(),
                    'resulting_organization_id' => $user->organization_id,
                    'role_assigned' => $data['role'],
                    'credential_updated' => isset($data['password']) && $data['password'] !== '',
                    'actor_id' => $actor->getKey(),
                    'actor_roles' => $actor->getRoleNames()->values()->all(),
                ],
                'reason' => 'User updated with role synchronization.',
            ], $context);

            return $user->fresh();
        });
    }

    public function deleteUserWithAudit(User $user, User $actor, AuditContext $context): void
    {
        $this->assertActorContext($actor, $context);

        DB::transaction(function () use ($user, $actor, $context): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $previousRoles = $lockedUser->getRoleNames()->values()->all();
            $previousOrganizationId = $lockedUser->organization_id;

            $lockedUser->delete();

            $this->audit->log('user.deleted', 'security.users', $lockedUser, [
                'old' => [
                    'roles' => $previousRoles,
                    'organization_id' => $previousOrganizationId,
                    'affected_user_id' => $lockedUser->getKey(),
                ],
                'new' => [
                    'operation' => 'delete',
                    'deleted' => true,
                    'actor_id' => $actor->getKey(),
                    'actor_roles' => $actor->getRoleNames()->values()->all(),
                ],
                'reason' => 'User deleted through privileged account management.',
            ], $context);
        });
    }

    private function assertActorContext(User $actor, AuditContext $context): void
    {
        if ((string) $context->actorId !== (string) $actor->getKey()) {
            throw new InvalidArgumentException('Audit context actor does not match the mutation actor.');
        }

        if ($actor->organization_id !== null && (string) $context->organizationId !== (string) $actor->organization_id) {
            throw new InvalidArgumentException('Audit context organization does not match the mutation actor.');
        }

        if ($context->actorRoles !== $actor->getRoleNames()->values()->all()) {
            throw new InvalidArgumentException('Audit context roles do not match the mutation actor.');
        }
    }

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}
}
