<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\MemberResignationRequest;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberResignationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_authorized_user_can_view_resignation_requests(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->roleUser('Pengurus Koperasi', $organization);

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);

        MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $admin->id,
            'status' => 'PENDING',
            'reason' => 'Pindah domisili',
            'effective_date' => now(),
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get('/cooperative/members/resignations');

        $response->assertOk();
    }

    public function test_unauthorized_user_cannot_view_resignation_requests(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->get('/cooperative/members/resignations');

        $response->assertForbidden();
    }

    public function test_manager_can_approve_resignation(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);

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

        $response = $this->actingAs($pengurus)
            ->post("/cooperative/members/resignations/{$request->id}/process", [
                'decision' => 'APPROVE',
                'review_notes' => 'Disetujui untuk diproses',
            ]);

        $response->assertSessionHas('success');
        $this->assertSame('APPROVED', $request->fresh()->status);
        $this->assertSame('RESIGNED', $member->fresh()->status);
    }

    public function test_manager_can_reject_resignation(): void
    {
        $organization = Organization::factory()->create();
        $pengurus = $this->roleUser('Pengurus Koperasi', $organization);

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

        $response = $this->actingAs($pengurus)
            ->post("/cooperative/members/resignations/{$request->id}/process", [
                'decision' => 'REJECT',
                'review_notes' => 'Harap lunasi kewajiban terlebih dahulu',
            ]);

        $response->assertSessionHas('success');
        $this->assertSame('REJECTED', $request->fresh()->status);
        $this->assertSame('ACTIVE', $member->fresh()->status);
    }

    private function roleUser(string $roleName, Organization $organization): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($roleName);

        return $user;
    }
}
