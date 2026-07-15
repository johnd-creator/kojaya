<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDestroyEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_destroy_endpoint_revokes_tokens_and_role_via_semantic_command(): void
    {
        $org = Organization::factory()->create();

        $memberUser = User::factory()->create(['organization_id' => $org->id]);
        $memberUser->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $memberUser->id,
        ]);

        $memberUser->createToken('mobile', ['profile:read', 'member:read', 'member:write']);
        $this->assertSame(1, $memberUser->tokens()->count());
        $this->assertTrue($memberUser->hasRole('Anggota'));

        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $this->actingAs($admin)
            ->delete(route('cooperative.members.destroy', $member))
            ->assertRedirect(route('cooperative.members.index'));

        // Role removed
        $this->assertFalse(
            $memberUser->refresh()->hasRole('Anggota'),
            'Anggota role must be removed after deleteAccess.',
        );

        // Tokens revoked
        $this->assertSame(
            0,
            $memberUser->tokens()->count(),
            'All tokens must be revoked after deleteAccess.',
        );

        // Member soft-deleted
        $this->assertSoftDeleted('cooperative_members', ['id' => $member->id]);

        // Audit log written by the semantic command
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.access.deleted',
            'subject_type' => CooperativeMember::class,
            'subject_id' => $member->id,
        ]);
    }

    public function test_destroy_endpoint_requires_manage_permission(): void
    {
        $org = Organization::factory()->create();

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);

        $kasir = User::factory()->create(['organization_id' => $org->id]);
        $kasir->assignRole('Kasir Koperasi');

        $this->actingAs($kasir)
            ->delete(route('cooperative.members.destroy', $member))
            ->assertForbidden();
    }

    public function test_destroy_does_not_revoker_before_authorization_check(): void
    {
        $org = Organization::factory()->create();

        $memberUser = User::factory()->create(['organization_id' => $org->id]);
        $memberUser->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $memberUser->id,
        ]);

        $memberUser->createToken('mobile', ['profile:read', 'member:read', 'member:write']);

        $anggotaActor = User::factory()->create(['organization_id' => $org->id]);
        $anggotaActor->assignRole('Anggota');

        try {
            $this->actingAs($anggotaActor)
                ->delete(route('cooperative.members.destroy', $member));
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            // Expected
        }

        // Tokens and role must be intact — authorization failed before any side effects
        $this->assertSame(1, $memberUser->tokens()->count(), 'Tokens must survive authorization failure.');
        $this->assertTrue($memberUser->refresh()->hasRole('Anggota'), 'Role must survive authorization failure.');
    }
}
