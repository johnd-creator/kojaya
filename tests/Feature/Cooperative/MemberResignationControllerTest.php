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

    /** @var list<string> */
    private const SHARED_INERTIA_KEYS = [
        'active_organization',
        'appearance',
        'auth',
        'contextualHelp',
        'csrf_token',
        'errors',
        'googleSsoEnabled',
        'name',
        'notifications',
        'sidebarOpen',
        'user_organizations',
    ];

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
        $props = $response->inertiaProps();
        $propKeys = array_keys($props);
        sort($propKeys);
        $expectedPropKeys = [...self::SHARED_INERTIA_KEYS, 'filters', 'requests', 'stats'];
        sort($expectedPropKeys);
        $this->assertSame($expectedPropKeys, $propKeys);
        $requestKeys = array_keys($props['requests']['data'][0] ?? []);
        sort($requestKeys);
        $this->assertSame([
            'created_at',
            'effective_date',
            'id',
            'member',
            'reason',
            'requested_at',
            'review_notes',
            'reviewed_at',
            'reviewer',
            'status',
        ], $requestKeys);
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

    public function test_resignation_approval_revokes_member_tokens_and_audits_reviewer(): void
    {
        $organization = Organization::factory()->create();
        $reviewer = $this->roleUser('Pengurus Koperasi', $organization);
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $memberUser->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);
        $memberUser->createToken('member-mobile', ['profile:read', 'member:read', 'member:write']);

        $request = MemberResignationRequest::query()->create([
            'cooperative_member_id' => $member->id,
            'user_id' => $memberUser->id,
            'status' => 'PENDING',
            'reason' => 'Mengundurkan diri',
            'effective_date' => now(),
            'requested_at' => now(),
        ]);

        $this->actingAs($reviewer)
            ->post("/cooperative/members/resignations/{$request->id}/process", [
                'decision' => 'APPROVE',
                'review_notes' => 'Disetujui oleh reviewer.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(0, $memberUser->fresh()->tokens()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.access.revoked',
            'subject_id' => $member->id,
        ]);
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
