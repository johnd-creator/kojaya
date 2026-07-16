<?php

namespace Tests\Feature\Cooperative;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\MemberAccountLinkService;
use App\Support\AuditContext;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class MemberAccountLinkAuthorizationTest extends TestCase
{
    public function test_same_organization_verified_ordinary_user_can_be_linked_explicitly(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);

        $this->actingAs($actor)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $target->id,
                'reason' => 'business_verification',
            ])
            ->assertRedirect();

        $this->assertSame($target->id, $member->fresh()->user_id);
        $this->assertTrue($target->fresh()->hasRole('Anggota'));
    }

    public function test_cross_organization_link_is_rejected_without_mutation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organizationA->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organizationB->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organizationA->id,
            'user_id' => null,
        ]);

        $this->actingAs($actor)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $target->id,
                'reason' => 'business_verification',
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertNull($member->fresh()->user_id);
        $this->assertFalse($target->fresh()->hasRole('Anggota'));
    }

    public function test_account_link_candidates_are_exact_and_organization_scoped(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organizationA->id]);
        $actor->assignRole('Admin Koperasi');
        $candidate = User::factory()->create([
            'organization_id' => $organizationA->id,
            'email' => 'candidate@example.com',
        ]);
        User::factory()->create([
            'organization_id' => $organizationB->id,
            'email' => 'other-organization@example.com',
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organizationA->id,
            'user_id' => null,
        ]);

        $this->actingAs($actor)
            ->getJson(route('cooperative.members.account-link.candidates', $member).'?email='.urlencode($candidate->email))
            ->assertOk()
            ->assertJsonPath('data.0.id', $candidate->id)
            ->assertJsonPath('data.0.email', $candidate->email)
            ->assertJsonCount(1, 'data');

        $this->actingAs($actor)
            ->getJson(route('cooperative.members.account-link.candidates', $member).'?email=other-organization%40example.com')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_only_one_link_is_retained_when_the_same_member_is_linked_twice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);

        $service = app(\App\Services\Cooperative\MemberAccountLinkService::class);
        $service->link($actor, $member, $target, 'business_verification');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->link($actor, $member->fresh(), $target, 'business_verification');
    }

    public function test_privileged_target_is_rejected_even_with_an_unusual_role_name(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $target->givePermissionTo('manage_cooperative_member');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);

        $this->actingAs($actor)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $target->id,
                'reason' => 'business_verification',
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertNull($member->fresh()->user_id);
    }

    public function test_all_privileged_and_operational_roles_are_denied_as_link_targets(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');

        foreach ([
            'System Admin',
            'Admin Pusat',
            'Pengurus Koperasi',
            'Manajer Koperasi',
            'Admin Koperasi',
            'Kasir Koperasi',
            'Finance Pusat',
            'Finance Unit',
            'HR Pusat',
            'HR Unit',
            'Employee',
            'Technician',
        ] as $roleName) {
            $target = User::factory()->create(['organization_id' => $organization->id]);
            $target->assignRole($roleName);
            $member = CooperativeMember::factory()->active()->create([
                'organization_id' => $organization->id,
                'user_id' => null,
            ]);

            $this->actingAs($actor)
                ->post(route('cooperative.members.account-link.store', $member), [
                    'user_id' => $target->id,
                    'reason' => 'business_verification',
                ])
                ->assertSessionHasErrors('user_id');

            $this->assertNull($member->fresh()->user_id, "Role [{$roleName}] was linked unexpectedly.");
        }
    }

    public function test_unsupported_reason_code_is_rejected_without_mutation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);

        $this->actingAs($actor)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $target->id,
                'reason' => 'free-form-sensitive-reason',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertNull($member->fresh()->user_id);
    }

    public function test_unlink_revokes_member_tokens_but_preserves_other_app_tokens(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $target->id,
        ]);

        $memberToken = $target->createToken('member', ['profile:read', 'member:read']);
        $memberToken->accessToken->forceFill(['token_app' => 'member', 'token_version' => 'v1'])->save();
        $adminToken = $target->createToken('admin', ['profile:read']);
        $adminToken->accessToken->forceFill(['token_app' => 'admin', 'token_version' => 'v1'])->save();

        $this->actingAs($actor)
            ->delete(route('cooperative.members.account-link.destroy', $member), [
                'reason' => 'member_correction',
            ])
            ->assertRedirect();

        $this->assertNull($member->fresh()->user_id);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $memberToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $adminToken->accessToken->id, 'token_app' => 'admin']);
    }

    public function test_link_writes_requested_and_completed_with_one_http_context(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);
        $correlationId = '11112222-3333-4444-5555-666677778888';

        $this->actingAs($actor)
            ->withHeader('X-Correlation-ID', $correlationId)
            ->post(route('cooperative.members.account-link.store', $member), [
                'user_id' => $target->id,
                'reason' => 'business_verification',
            ])
            ->assertRedirect();

        $audits = AuditLog::query()
            ->whereIn('action', ['member.account.link.requested', 'member.account.link.completed'])
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $audits);
        $this->assertSame($correlationId, $audits[0]->correlation_id);
        $this->assertSame($correlationId, $audits[1]->correlation_id);
        $this->assertSame((string) $actor->id, (string) $audits[0]->user_id);
        $this->assertSame((string) $actor->id, (string) $audits[1]->user_id);
        $this->assertSame($organization->id, $audits[1]->organization_id);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.linked']);
    }

    public function test_link_completed_audit_failure_rolls_back_member_and_anggota_role_and_writes_truthful_failure(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);

        $audit = \Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'member.account.link.completed')
            ->andThrow(new \RuntimeException('simulated link completion audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(MemberAccountLinkService::class)->link(
                $actor,
                $member,
                $target,
                'business_verification',
                AuditContext::forActor($actor),
            );
            $this->fail('Expected completion audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated link completion audit failure', $exception->getMessage());
        }

        $this->assertNull($member->fresh()->user_id);
        $this->assertFalse($target->fresh()->hasRole('Anggota'));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.link.completed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.account.link.failed']);
    }

    public function test_link_rejects_audit_context_actor_mismatch_before_any_mutation(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $otherActor = User::factory()->create(['organization_id' => $organization->id]);
        $otherActor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);

        try {
            app(MemberAccountLinkService::class)->link(
                $actor,
                $member,
                $target,
                'business_verification',
                AuditContext::forActor($otherActor),
            );
            $this->fail('Expected actor/context mismatch rejection.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('does not match', $exception->getMessage());
        }

        $this->assertNull($member->fresh()->user_id);
        $this->assertFalse($target->fresh()->hasRole('Anggota'));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.link.requested']);
    }

    public function test_unlink_completed_audit_failure_rolls_back_member_role_and_member_token(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Admin Koperasi');
        $target = User::factory()->create(['organization_id' => $organization->id]);
        $target->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $target->id,
        ]);
        $token = $target->createToken('member', ['member:read']);
        $tokenId = $token->accessToken->id;

        $audit = \Mockery::mock(AuditLogService::class)->makePartial();
        $audit->shouldReceive('log')
            ->withArgs(fn (string $action): bool => $action === 'member.account.unlink.completed')
            ->andThrow(new \RuntimeException('simulated unlink completion audit failure'));
        $this->app->instance(AuditLogService::class, $audit);

        try {
            app(MemberAccountLinkService::class)->unlink(
                $actor,
                $member,
                'member_correction',
                AuditContext::forActor($actor),
            );
            $this->fail('Expected completion audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('simulated unlink completion audit failure', $exception->getMessage());
        }

        $this->assertSame($target->id, $member->fresh()->user_id);
        $this->assertTrue($target->fresh()->hasRole('Anggota'));
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.account.unlink.completed']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'member.access.revoked']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'member.account.unlink.failed']);
    }
}
