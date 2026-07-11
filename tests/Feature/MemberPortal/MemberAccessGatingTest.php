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

    public function test_admin_verified_member_can_access_dashboard_but_not_financial_pages(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING_REVIEW);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('member.savings'))
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

    public function test_existing_member_session_loses_all_financial_web_access_after_deactivation(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $this->actingAs($user)->get(route('member.savings'))->assertOk();

        $member->forceFill([
            'status' => CooperativeMember::VALIDATION_INACTIVE,
            'validation_status' => CooperativeMember::VALIDATION_INACTIVE,
        ])->save();

        foreach ([
            'member.savings',
            'member.loans',
            'member.points',
            'member.rewards',
            'member.transactions',
        ] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertRedirect(route('member.onboarding'));
        }
    }

    public function test_existing_member_session_loses_all_financial_web_access_after_resignation(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $member->forceFill([
            'status' => CooperativeMember::VALIDATION_RESIGNED,
            'validation_status' => CooperativeMember::VALIDATION_RESIGNED,
            'resigned_at' => now()->toDateString(),
        ])->save();

        foreach (['member.savings', 'member.loans', 'member.points', 'member.rewards', 'member.transactions'] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertRedirect(route('member.onboarding'));
        }
    }

    public function test_pending_member_can_access_dashboard_and_onboarding(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('member.profile'))
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

    public function test_active_member_is_redirected_from_erp_dashboard_to_kojayaku(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('member.dashboard'));
    }

    public function test_pending_member_is_redirected_from_erp_dashboard_to_onboarding(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('member.onboarding'));
    }

    public function test_admin_verified_member_is_redirected_from_erp_dashboard_to_onboarding(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING_REVIEW);

        $this->actingAs($user)
            ->get(route('dashboard'))
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
