<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberOnboardingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Anggota']);
    }

    public function test_admin_verified_member_without_submission_sees_draft_review_state(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'status' => 'PENDING',
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'draft')
                ->where('validation_status', 'PENDING_VALIDATION')
                ->where('submitted', false)
            );
    }

    public function test_admin_verified_member_with_submission_sees_review_review_state(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'status' => 'PENDING',
            'onboarding_submitted_at' => now(),
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'review')
                ->where('validation_status', 'PENDING_VALIDATION')
                ->where('submitted', true)
            );
    }

    public function test_pending_member_without_submission_sees_draft_review_state(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'status' => 'PENDING',
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'draft')
                ->where('validation_status', 'PENDING')
                ->where('submitted', false)
            );
    }

    public function test_active_member_without_submission_can_fill_onboarding_form(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'draft')
                ->where('validation_status', 'PENDING')
                ->where('submitted', false)
            )
            ->assertDontSee('member-admission-waiting');
    }

    public function test_active_member_submission_keeps_lifecycle_active_while_waiting_final_approval(): void
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING,
            'status' => CooperativeMember::VALIDATION_ACTIVE,
            'onboarding_submitted_at' => null,
            'identity_number' => null,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Merdeka No. 10',
                'identity_number' => '3201234567890001',
                'jenis_kelamin' => 'L',
                'kategori' => 'IP',
            ])
            ->assertRedirect('/member/onboarding')
            ->assertSessionHas('success');

        $fresh = $member->fresh();

        $this->assertSame(CooperativeMember::VALIDATION_ACTIVE, $fresh->status);
        $this->assertSame(CooperativeMember::VALIDATION_PENDING_REVIEW, $fresh->validation_status);
    }

    public function test_admin_verified_member_can_submit_onboarding(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'status' => 'PENDING',
            'onboarding_submitted_at' => null,
            'identity_number' => null,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->from('/member/onboarding')
            ->post(route('member.onboarding.submit'), [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'phone' => '08123456789',
                'address' => 'Jl. Merdeka No. 10',
                'identity_number' => '3201234567890001',
                'jenis_anggota' => 'AB',
                'jenis_kelamin' => 'L',
                'kategori' => 'IP',
            ])
            ->assertRedirect('/member/onboarding')
            ->assertSessionHas('success');
    }

    public function test_revision_member_without_submission_sees_draft_review_state(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
            'status' => 'PENDING',
            'onboarding_submitted_at' => null,
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'draft')
                ->where('validation_status', 'REVISION')
                ->where('submitted', false)
            );
    }

    public function test_revision_member_with_submission_sees_revision_review_state(): void
    {
        $user = User::factory()->create();
        CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'validation_status' => CooperativeMember::VALIDATION_REVISION,
            'status' => 'PENDING',
            'onboarding_submitted_at' => now(),
        ]);
        $user->assignRole('Anggota');

        $this->actingAs($user)
            ->get(route('member.onboarding'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Onboarding')
                ->where('review_state', 'revision')
                ->where('validation_status', 'REVISION')
                ->where('submitted', true)
            );
    }
}
