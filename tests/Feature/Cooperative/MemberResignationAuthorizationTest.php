<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\MemberResignationRequest;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberResignationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_anggota_role_is_forbidden_from_admin_resignation_index(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.members.resignations.index'))
            ->assertForbidden();
    }

    public function test_anggota_cannot_view_other_member_resignation_request(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);

        $otherUser = User::factory()->create(['organization_id' => $organization->id]);
        $otherUser->assignRole('Anggota');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
        ]);

        $request = MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $owner->id,
            'status' => 'PENDING',
            'reason' => 'Pindah domisili',
            'effective_date' => now(),
            'requested_at' => now(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('cooperative.members.resignations.index'))
            ->assertForbidden();
    }

    public function test_pengurus_can_view_resignation_index(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);

        $this->actingAs($pengurus)
            ->get(route('cooperative.members.resignations.index'))
            ->assertOk();
    }

    public function test_admin_koperasi_can_view_resignation_index(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->roleUser('Admin Koperasi', $organization);

        $this->actingAs($admin)
            ->get(route('cooperative.members.resignations.index'))
            ->assertOk();
    }

    public function test_kasir_without_review_permission_is_forbidden(): void
    {
        $organization = Organization::factory()->create();
        $kasir = $this->roleUser('Kasir Koperasi', $organization);

        $this->actingAs($kasir)
            ->get(route('cooperative.members.resignations.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('cooperative.members.resignations.index'))
            ->assertRedirect('/login');
    }

    public function test_process_endpoint_requires_review_permission(): void
    {
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);

        $request = MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $member->user_id ?? User::factory()->create()->id,
            'status' => 'PENDING',
            'reason' => 'Mengundurkan diri',
            'effective_date' => now(),
            'requested_at' => now(),
        ]);

        $anggotaUser = User::factory()->create(['organization_id' => $organization->id]);
        $anggotaUser->assignRole('Anggota');

        $this->actingAs($anggotaUser)
            ->post(route('cooperative.members.resignations.process', $request), [
                'decision' => 'APPROVE',
                'review_notes' => 'test',
            ])
            ->assertForbidden();
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }
}
