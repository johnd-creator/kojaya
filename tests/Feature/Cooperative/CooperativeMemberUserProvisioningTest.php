<?php

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Support\AuditContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CooperativeMemberUserProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_provision_creates_user_assigns_anggota_role_links_member_and_audits_atomically(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
            'email' => 'provisioned@example.com',
        ]);

        $user = app(CooperativeMemberUserProvisioningService::class)->provision($member, 'secret-password');

        $this->assertNotNull($user);
        $this->assertTrue($user->fresh()->hasRole('Anggota'));
        $this->assertSame($user->id, $member->fresh()->user_id);

        $audit = AuditLog::query()->where('action', 'member.account.link.completed')->sole();
        $this->assertSame((string) $member->id, (string) $audit->subject_id);
        $this->assertSame('link', $audit->new_values['operation']);
        $this->assertTrue($audit->new_values['link_changed']);
        $this->assertTrue($audit->new_values['user_created']);
        $this->assertTrue($audit->new_values['role_assigned']);
        $this->assertSame((string) $user->id, (string) $audit->new_values['affected_user_id']);
    }

    public function test_provision_reconciles_anggota_role_for_existing_linked_user_without_writing_false_link_completed(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        // User is linked but does not have the Anggota role yet.
        $this->assertFalse($user->fresh()->hasRole('Anggota'));

        app(CooperativeMemberUserProvisioningService::class)->provision($member);

        $this->assertTrue($user->fresh()->hasRole('Anggota'), 'Anggota role must be assigned by reconciliation.');
        $this->assertSame($user->id, $member->fresh()->user_id, 'Member link must not change during reconciliation.');

        $audit = AuditLog::query()->where('action', 'member.role.reconciled')->sole();
        $this->assertSame((string) $member->id, (string) $audit->subject_id);
        $this->assertSame('reconcile_role', $audit->new_values['operation']);
        $this->assertTrue($audit->new_values['role_assigned']);
        $this->assertFalse($audit->new_values['link_changed']);
        $this->assertFalse($audit->new_values['user_created']);
        $this->assertSame((string) $user->id, (string) $audit->new_values['affected_user_id']);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.link.completed']);
    }

    public function test_reconciliation_audit_failure_rolls_back_role_assignment(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $audit = Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'member.role.reconciled')
            ->andThrow(new \RuntimeException('simulated mandatory reconciliation audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(CooperativeMemberUserProvisioningService::class)->provision($member);
            $this->fail('Expected mandatory reconciliation audit failure to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated mandatory reconciliation audit failure', $exception->getMessage());
        }

        $this->assertFalse($user->fresh()->hasRole('Anggota'), 'Role assignment must roll back on audit failure.');
        $this->assertSame($user->id, $member->fresh()->user_id, 'Member link must be unchanged.');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.role.reconciled']);
    }

    public function test_provision_noop_when_member_already_linked_and_user_already_has_anggota_role(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        app(CooperativeMemberUserProvisioningService::class)->provision($member);

        $this->assertTrue($user->fresh()->hasRole('Anggota'));
        $this->assertSame($user->id, $member->fresh()->user_id);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.link.completed']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.role.reconciled']);
    }

    public function test_provision_persists_audit_context_actor_organization_and_correlation_id(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $correlationId = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $context = AuditContext::forActor($actor, AuditContext::SOURCE_DOMAIN, correlationId: $correlationId);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
            'email' => 'context@example.com',
        ]);

        app(CooperativeMemberUserProvisioningService::class)->provision($member, 'secret-password', $context);

        $audit = AuditLog::query()->where('action', 'member.account.link.completed')->sole();
        $this->assertSame($correlationId, $audit->correlation_id, 'Correlation ID must come from the provided context.');
        $this->assertSame((string) $actor->id, (string) $audit->user_id, 'Actor must come from the provided context.');
        $this->assertSame((string) $organization->id, (string) $audit->organization_id, 'Organization must come from the provided context.');
        $this->assertContains('Admin Koperasi', $audit->actor_roles, 'Actor roles must come from the provided context.');
    }

    public function test_mandatory_completed_audit_failure_rolls_back_user_creation_role_assignment_and_member_link(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
            'email' => 'rollback@example.com',
        ]);

        $audit = Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'member.account.link.completed')
            ->andThrow(new \RuntimeException('simulated mandatory provisioning audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(CooperativeMemberUserProvisioningService::class)->provision($member, 'secret-password');
            $this->fail('Expected mandatory provisioning audit failure to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated mandatory provisioning audit failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.com']);
        $this->assertNull($member->fresh()->user_id, 'Member link must roll back.');
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.link.completed']);
    }
}
