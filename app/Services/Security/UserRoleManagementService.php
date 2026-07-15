<?php

namespace App\Services\Security;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\AuditContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRoleManagementService
{
    /**
     * @param  array{name: string, email: string, password: string, role: string, organization_id: string}  $data
     */
    public function createUserWithAudit(array $data, User $actor, AuditContext $context): User
    {
        return DB::transaction(function () use ($data, $context): User {
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
                ],
                'new' => [
                    'operation' => 'create',
                    'resulting_roles' => $resultingRoles,
                    'affected_user_id' => $user->getKey(),
                    'organization_id' => $data['organization_id'],
                    'role_assigned' => $data['role'],
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
        return DB::transaction(function () use ($user, $data, $context): User {
            $previousRoles = $user->getRoleNames()->values()->all();

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
                    'previous_organization_id' => $user->getOriginal('organization_id'),
                ],
                'new' => [
                    'operation' => 'update',
                    'resulting_roles' => $resultingRoles,
                    'affected_user_id' => $user->getKey(),
                    'organization_id' => $data['organization_id'],
                    'role_assigned' => $data['role'],
                    'credential_updated' => isset($data['password']) && $data['password'] !== '',
                ],
                'reason' => 'User updated with role synchronization.',
            ], $context);

            return $user->fresh();
        });
    }

    public function __construct(
        private readonly AuditLogService $audit,
    ) {}
}
