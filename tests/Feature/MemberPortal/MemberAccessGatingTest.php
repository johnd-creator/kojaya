<?php

namespace Tests\Feature\MemberPortal;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberAccessGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Anggota']);
    }

    public function test_pending_member_cannot_access_savings(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('member.savings'))
            ->assertRedirect(route('member.onboarding'))
            ->assertSessionHas('warning');

    }

    public function test_revision_member_cannot_access_loans(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_REVISION);

        $this->actingAs($user)
            ->get(route('member.loans'))
            ->assertRedirect(route('member.onboarding'))
            ->assertSessionHas('warning');
    }

    public function test_active_member_can_access_savings(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $this->actingAs($user)
            ->get(route('member.savings'))
            ->assertOk();
    }

    public function test_pending_member_can_only_access_onboarding_page(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('member.profile'))
            ->assertRedirect(route('member.onboarding'))
            ->assertSessionHas('warning');

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertRedirect(route('member.onboarding'))
            ->assertSessionHas('warning');
    }

    public function test_rejected_member_is_blocked_from_loans_and_savings(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_REJECTED);

        $this->actingAs($user)
            ->get(route('member.loans'))
            ->assertRedirect(route('member.onboarding'));

        $this->actingAs($user)
            ->get(route('member.savings'))
            ->assertRedirect(route('member.onboarding'));
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function makeMember(string $validationStatus): array
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => $validationStatus,
            'status' => $validationStatus === CooperativeMember::VALIDATION_ACTIVE ? 'ACTIVE' : 'PENDING',
            'onboarding_submitted_at' => $validationStatus === CooperativeMember::VALIDATION_PENDING ? null : now(),
        ]);

        return [$user, $member];
    }
}
