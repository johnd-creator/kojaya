<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.account.link.completed',
            'subject_type' => CooperativeMember::class,
            'subject_id' => $member->id,
        ]);
    }

    public function test_mandatory_completed_audit_failure_rolls_back_user_creation_role_assignment_and_member_link(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
            'email' => 'rollback@example.com',
        ]);

        // Narrow fake: only the mandatory member.account.link.completed audit
        // fails. Everything else runs through the real implementation, so the
        // rollback proves real user creation, role assignment, and linking are
        // reversed — not a mocked boundary.
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
