<?php

namespace Tests\Feature;

use App\Enums\LoanStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativeMemberDocument;
use App\Models\CooperativePayment;
use App\Models\CooperativeReceipt;
use App\Models\Loan;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint5KojayakuUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_onboarding_status_api_tracks_live_and_manual_steps(): void
    {
        [$user, $member] = $this->memberUser([
            'phone' => '08123456789',
            'identity_number' => '3201010101010001',
            'address' => 'Jl. Koperasi No. 1',
        ]);
        CooperativeMemberDocument::query()->create([
            'cooperative_member_id' => $member->id,
            'type' => 'KTP',
            'file_path' => 'kyc/member-1.pdf',
        ]);

        Sanctum::actingAs($user, ['member:read', 'member:write']);

        $this->getJson('/api/v1/member/onboarding/status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.completed_steps', 2)
            ->assertJsonPath('data.steps.0.completed', true)
            ->assertJsonPath('data.steps.1.completed', true);

        $this->postJson('/api/v1/member/onboarding/steps', ['step' => 'loans'])
            ->assertOk()
            ->assertJsonPath('data.steps.3.completed', true);
    }

    public function test_member_dashboard_api_returns_payment_loan_and_reward_journeys(): void
    {
        [$user, $member] = $this->memberUser();
        $this->seedJourneyData($member, $user);

        Sanctum::actingAs($user, ['member:read']);

        $this->getJson('/api/v1/member/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.journeys.payment.steps.3.completed', true)
            ->assertJsonPath('data.journeys.loan.current_status', 'ACTIVE')
            ->assertJsonPath('data.journeys.reward.current_status', 'PROCESSING');
    }

    public function test_member_web_pages_receive_onboarding_and_journey_props(): void
    {
        [$user, $member] = $this->memberUser();
        $this->seedJourneyData($member, $user);

        $this->actingAs($user)
            ->get('/member')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Dashboard')
                ->has('journeys.payment.steps', 4)
                ->has('journeys.loan.steps', 5)
                ->has('journeys.reward.steps', 3)
            );

        $this->actingAs($user)
            ->get('/member/onboarding')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Kojayaku/Onboarding')
                ->has('onboarding.steps', 5)
            );
    }

    /**
     * @param  array<string, mixed>  $memberAttributes
     * @return array{0: User, 1: CooperativeMember}
     */
    private function memberUser(array $memberAttributes = []): array
    {
        $user = User::factory()->create();
        $member = CooperativeMember::factory()->active()->create(array_merge([
            'user_id' => $user->id,
            'email' => $user->email,
        ], $memberAttributes));

        return [$user, $member];
    }

    private function seedJourneyData(CooperativeMember $member, User $user): void
    {
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB-SPRINT5',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 100000,
            'paid_amount' => 100000,
            'due_date' => today()->addWeek(),
            'status' => 'PAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => today(),
            'status' => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);
        CooperativeReceipt::factory()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_payment_id' => $payment->id,
        ]);

        Loan::factory()->active()->create([
            'cooperative_member_id' => $member->id,
            'organization_id' => $member->organization_id,
            'user_id' => $user->id,
            'status' => LoanStatus::Active,
            'approved_at' => now()->subDays(2),
            'disbursed_at' => now()->subDay(),
        ]);

        RewardRedemption::factory()->create([
            'cooperative_member_id' => $member->id,
            'status' => 'PROCESSING',
            'processed_at' => now(),
        ]);
    }
}
