<?php

namespace Tests\Feature\MemberPortal;

use App\Models\CooperativeMember;
use App\Models\MemberOnboardingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberOnboardingReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Anggota']);
    }

    public function test_member_loan_and_reward_get_pages_do_not_mark_onboarding_steps(): void
    {
        [$user, $member] = $this->makeActiveMember();

        $this->actingAs($user)->get(route('member.loans'))->assertOk();
        $this->actingAs($user)->get(route('member.rewards'))->assertOk();

        $this->assertDatabaseMissing('member_onboarding_progress', [
            'cooperative_member_id' => $member->id,
        ]);
    }

    public function test_repeated_member_get_pages_remain_idempotent_for_onboarding_progress(): void
    {
        [$user, $member] = $this->makeActiveMember();
        $progress = MemberOnboardingProgress::query()->create([
            'cooperative_member_id' => $member->id,
        ]);
        $before = $progress->fresh()->getAttributes();

        foreach ([route('member.loans'), route('member.rewards')] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
            $this->actingAs($user)->get($url)->assertOk();
        }

        $this->assertSame($before, $progress->fresh()->getAttributes());
    }

    public function test_explicit_onboarding_step_post_is_idempotent_and_marks_the_requested_step(): void
    {
        [$user, $member] = $this->makeActiveMember();

        $this->actingAs($user)
            ->post(route('member.onboarding.steps'), ['step' => 'loans'])
            ->assertRedirect();

        $progress = MemberOnboardingProgress::query()
            ->where('cooperative_member_id', $member->id)
            ->firstOrFail();

        $this->assertNotNull($progress->loan_intro_seen_at);
        $this->assertNull($progress->reward_intro_seen_at);
        $markedAt = $progress->loan_intro_seen_at;

        $this->actingAs($user)
            ->post(route('member.onboarding.steps'), ['step' => 'loans'])
            ->assertRedirect();

        $this->assertTrue($markedAt->equalTo($progress->fresh()->loan_intro_seen_at));
        $this->assertSame(1, MemberOnboardingProgress::query()
            ->where('cooperative_member_id', $member->id)
            ->count());
    }

    public function test_audited_member_get_pages_do_not_change_existing_onboarding_progress(): void
    {
        [$user, $member] = $this->makeActiveMember();
        $progress = MemberOnboardingProgress::query()->create([
            'cooperative_member_id' => $member->id,
            'dismissed_at' => now()->subMinute(),
        ]);
        $before = $progress->fresh()->getAttributes();

        foreach ([
            'member.dashboard',
            'member.savings',
            'member.loans',
            'member.points',
            'member.rewards',
            'member.transactions',
            'member.store-account',
            'member.profile',
            'member.notifications',
        ] as $routeName) {
            $this->actingAs($user)->get(route($routeName))->assertSuccessful();
        }

        $this->assertSame($before, $progress->fresh()->getAttributes());
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function makeActiveMember(): array
    {
        $member = CooperativeMember::factory()->active()->create();
        $user = $member->user;
        $user->assignRole('Anggota');

        return [$user, $member];
    }
}
