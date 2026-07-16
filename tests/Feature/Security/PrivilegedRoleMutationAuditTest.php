<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Security\UserRoleManagementService;
use App\Support\AuditContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class PrivilegedRoleMutationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_creating_user_with_role_creates_authoritative_audit_event(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('System Admin');

        $service = app(UserRoleManagementService::class);

        $user = $service->createUserWithAudit(
            [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password123',
                'role' => 'Admin Koperasi',
                'organization_id' => $org->id,
            ],
            $admin,
            AuditContext::forActor($admin, AuditContext::SOURCE_HTTP),
        );

        $audit = AuditLog::query()->where('action', 'user.role.mutated')->sole();

        $this->assertSame('security.users', $audit->module);
        $this->assertSame((string) $user->id, (string) $audit->subject_id);
        $this->assertSame((string) $admin->id, (string) $audit->user_id);
        $this->assertSame('create', $audit->new_values['operation']);
        $this->assertSame(['Admin Koperasi'], $audit->new_values['resulting_roles']);
        $this->assertSame($org->id, $audit->new_values['resulting_organization_id']);
        $this->assertSame([], $audit->old_values['previous_roles']);
    }

    public function test_updating_user_records_exact_old_and_new_roles(): void
    {
        $org = Organization::factory()->create();
        $target = User::factory()->create(['organization_id' => $org->id]);
        $target->assignRole('Anggota');
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('System Admin');

        $service = app(UserRoleManagementService::class);

        $service->updateUserWithAudit(
            $target,
            [
                'name' => 'Updated Name',
                'email' => $target->email,
                'role' => 'Admin Koperasi',
                'organization_id' => $org->id,
            ],
            $admin,
            AuditContext::forActor($admin, AuditContext::SOURCE_HTTP),
        );

        $audit = AuditLog::query()->where('action', 'user.role.mutated')->sole();

        $this->assertSame(['Anggota'], $audit->old_values['previous_roles']);
        $this->assertSame(['Admin Koperasi'], $audit->new_values['resulting_roles']);
        $this->assertSame('update', $audit->new_values['operation']);
        $this->assertFalse($audit->new_values['credential_updated']);
    }

    public function test_actor_and_organization_context_are_correct(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('System Admin');

        $service = app(UserRoleManagementService::class);

        $service->createUserWithAudit(
            [
                'name' => 'Ctx User',
                'email' => 'ctx@example.com',
                'password' => 'password123',
                'role' => 'Anggota',
                'organization_id' => $org->id,
            ],
            $admin,
            AuditContext::forActor($admin, AuditContext::SOURCE_HTTP),
        );

        $audit = AuditLog::query()->where('action', 'user.role.mutated')->sole();

        $this->assertSame((string) $admin->id, (string) $audit->user_id);
        $this->assertContains('System Admin', $audit->actor_roles);
        $this->assertSame($org->id, $audit->organization_id);
    }

    public function test_mandatory_audit_failure_during_create_leaves_no_user(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('System Admin');

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->andThrow(new \RuntimeException('simulated audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        $service = app(UserRoleManagementService::class);

        try {
            $service->createUserWithAudit(
                [
                    'name' => 'Fail User',
                    'email' => 'fail@example.com',
                    'password' => 'password123',
                    'role' => 'Anggota',
                    'organization_id' => $org->id,
                ],
                $admin,
                AuditContext::forActor($admin, AuditContext::SOURCE_HTTP),
            );
            $this->fail('Expected audit failure to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated audit failure', $e->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'fail@example.com']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.role.mutated']);
    }

    public function test_mandatory_audit_failure_during_update_preserves_original_state(): void
    {
        $org = Organization::factory()->create();
        $target = User::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);
        $originalPassword = $target->password;
        $target->assignRole('Anggota');
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('System Admin');

        $audit = Mockery::mock(AuditLogService::class);
        $audit->shouldReceive('log')
            ->andThrow(new \RuntimeException('simulated audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        $service = app(UserRoleManagementService::class);

        try {
            $service->updateUserWithAudit(
                $target,
                [
                    'name' => 'Changed Name',
                    'email' => 'changed@example.com',
                    'password' => 'newpassword123',
                    'role' => 'Admin Koperasi',
                    'organization_id' => $org->id,
                ],
                $admin,
                AuditContext::forActor($admin, AuditContext::SOURCE_HTTP),
            );
            $this->fail('Expected audit failure to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated audit failure', $e->getMessage());
        }

        $target->refresh();
        $this->assertSame('Original Name', $target->name);
        $this->assertSame('original@example.com', $target->email);
        $this->assertSame($originalPassword, $target->password);
        $this->assertTrue($target->hasRole('Anggota'));
        $this->assertFalse($target->hasRole('Admin Koperasi'));
    }

    public function test_audit_metadata_contains_no_password_hash_token_or_secret(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('System Admin');

        $service = app(UserRoleManagementService::class);

        $service->createUserWithAudit(
            [
                'name' => 'Secret Check',
                'email' => 'secret@example.com',
                'password' => 'supersecretpassword123',
                'role' => 'Anggota',
                'organization_id' => $org->id,
            ],
            $admin,
            AuditContext::forActor($admin, AuditContext::SOURCE_HTTP),
        );

        $audit = AuditLog::query()->where('action', 'user.role.mutated')->sole();
        $encoded = json_encode($audit->new_values).json_encode($audit->old_values).($audit->reason ?? '');

        $this->assertStringNotContainsString('supersecretpassword123', $encoded);
        $this->assertStringNotContainsString('$2y$', $encoded);
    }

    public function test_actor_context_mismatch_is_rejected_before_user_creation(): void
    {
        $org = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $org->id]);
        $actor->assignRole('System Admin');
        $otherActor = User::factory()->create(['organization_id' => $org->id]);
        $otherActor->assignRole('System Admin');

        try {
            app(UserRoleManagementService::class)->createUserWithAudit(
                [
                    'name' => 'Mismatch User',
                    'email' => 'mismatch@example.com',
                    'password' => 'password123',
                    'role' => 'Anggota',
                    'organization_id' => $org->id,
                ],
                $actor,
                AuditContext::forActor($otherActor, AuditContext::SOURCE_HTTP),
            );
            $this->fail('Expected actor/context mismatch to be rejected before user creation.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('does not match the mutation actor', $e->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.role.mutated']);
        $this->assertSame(2, User::query()->where('organization_id', $org->id)->count(), 'No partial state (role or otherwise) must remain.');
    }

    public function test_organization_move_rolls_back_when_mandatory_audit_fails(): void
    {
        $oldOrganization = Organization::factory()->create();
        $newOrganization = Organization::factory()->create();
        $target = User::factory()->create([
            'organization_id' => $oldOrganization->id,
            'name' => 'Original Move',
            'email' => 'move-rollback@example.com',
        ]);
        $originalPassword = $target->password;
        $target->assignRole('Anggota');
        $actor = User::factory()->create(['organization_id' => $oldOrganization->id]);
        $actor->assignRole('System Admin');

        // Narrow fake: only the mandatory user.role.mutated audit fails.
        $audit = Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'user.role.mutated')
            ->andThrow(new \RuntimeException('simulated mandatory mutation audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(UserRoleManagementService::class)->updateUserWithAudit(
                $target,
                [
                    'name' => 'Moved User',
                    'email' => 'moved@example.com',
                    'password' => 'newpassword123',
                    'role' => 'Admin Koperasi',
                    'organization_id' => $newOrganization->id,
                ],
                $actor,
                AuditContext::forActor($actor, AuditContext::SOURCE_HTTP),
            );
            $this->fail('Expected mandatory mutation audit failure to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated mandatory mutation audit failure', $e->getMessage());
        }

        $target->refresh();
        $this->assertSame($oldOrganization->id, $target->organization_id, 'User must remain in the previous organization after rollback.');
        $this->assertSame('Original Move', $target->name, 'Profile changes must roll back.');
        $this->assertSame('move-rollback@example.com', $target->email, 'Email changes must roll back.');
        $this->assertSame($originalPassword, $target->password, 'Credential changes must roll back.');
        $this->assertTrue($target->hasRole('Anggota'), 'Original role must be restored after rollback.');
        $this->assertFalse($target->hasRole('Admin Koperasi'), 'Requested role must not persist after rollback.');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.role.mutated']);
    }

    public function test_organization_move_records_previous_and_resulting_organization_truthfully(): void
    {
        $oldOrganization = Organization::factory()->create();
        $newOrganization = Organization::factory()->create();
        $target = User::factory()->create(['organization_id' => $oldOrganization->id]);
        $target->assignRole('Anggota');
        $actor = User::factory()->create(['organization_id' => $oldOrganization->id]);
        $actor->assignRole('System Admin');

        app(UserRoleManagementService::class)->updateUserWithAudit(
            $target,
            [
                'name' => 'Moved User',
                'email' => $target->email,
                'role' => 'Admin Koperasi',
                'organization_id' => $newOrganization->id,
            ],
            $actor,
            AuditContext::forActor($actor, AuditContext::SOURCE_HTTP),
        );

        $audit = AuditLog::query()->where('action', 'user.role.mutated')->sole();

        $this->assertSame($oldOrganization->id, $audit->old_values['previous_organization_id']);
        $this->assertSame($newOrganization->id, $audit->new_values['resulting_organization_id']);
        $this->assertSame(['Anggota'], $audit->old_values['previous_roles']);
        $this->assertSame(['Admin Koperasi'], $audit->new_values['resulting_roles']);
        $this->assertSame((string) $actor->id, (string) $audit->new_values['actor_id']);
        $this->assertSame(['System Admin'], $audit->new_values['actor_roles']);
        $this->assertSame((string) $actor->id, (string) $audit->user_id);
        $this->assertContains('System Admin', $audit->actor_roles);
    }
}
