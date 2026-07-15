<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
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
}
