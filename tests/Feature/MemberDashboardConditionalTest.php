<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MemberDashboardConditionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\CooperativeSeeder::class);
    }

    public function test_pending_member_can_access_member_dashboard(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk();
    }

    public function test_pending_member_sees_simpanan_pokok_invoice_in_props(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $type = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($user)->get(route('member.dashboard'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Kojayaku/Dashboard')
                ->has('simpanan_pokok_invoice')
                ->where('is_active_member', false)
            );
    }

    public function test_active_member_does_not_see_simpanan_pokok_invoice_if_paid(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $type = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 200000,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'PAID',
        ]);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('simpanan_pokok_invoice', null)
                ->where('simpanan_pokok_progress.is_paid', true)
                ->where('simpanan_pokok_progress.percent', 100)
                ->where('is_active_member', true)
            );
    }

    public function test_active_member_without_wajib_invoices_sees_no_wajib_card(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('simpanan_wajib_pending', null)
            );
    }

    public function test_active_member_with_wajib_pending_sees_wajib_card(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $wajibType = CooperativeContributionType::query()->where('code', 'WAJIB')->first();

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $wajibType->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('simpanan_wajib_pending')
                ->where('simpanan_wajib_pending.count', 1)
                ->where('simpanan_wajib_pending.total_amount', 100000)
            );
    }

    public function test_active_member_dashboard_no_longer_has_pending_invoices_in_summary(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('summary.pending_invoices')
            );
    }

    public function test_pending_member_dashboard_has_empty_transactions_and_loans(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('recentTransactions', [])
                ->where('recentLoans', [])
            );
    }

    public function test_dashboard_includes_onboarding_completeness(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('onboarding_completeness')
                ->has('onboarding_completeness.progress_percent')
                ->has('onboarding_completeness.is_complete')
            );
    }

    public function test_dashboard_includes_simpanan_pokok_progress(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $type = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('simpanan_pokok_progress')
                ->where('simpanan_pokok_progress.percent', 0)
                ->where('simpanan_pokok_progress.is_paid', false)
            );
    }

    public function test_dashboard_no_longer_has_journeys(): void
    {
        [$user] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->missing('journeys')
            );
    }

    public function test_pending_review_member_sees_active_member_cards(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);
        $member->update([
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'onboarding_submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('is_active_member', true)
                ->where('is_pending_review', true)
            );
    }

    public function test_pending_review_without_submission_is_not_active(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);
        $member->update([
            'validation_status' => CooperativeMember::VALIDATION_PENDING_REVIEW,
            'onboarding_submitted_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('is_active_member', false)
                ->where('is_pending_review', false)
            );
    }

    public function test_member_can_upload_payment_proof_via_web(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_PENDING);

        $type = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'UNPAID',
        ]);

        $proof = UploadedFile::fake()->image('bukti.jpg', 800, 600);

        $this->actingAs($user)
            ->post(route('member.payments.proof'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'amount' => 200000,
                'payment_method' => 'TRANSFER',
                'paid_at' => now()->toDateString(),
                'reference_no' => 'REF-001',
                'proof' => $proof,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 200000,
            'payment_method' => 'TRANSFER',
            'status' => 'PENDING',
            'reference_no' => 'REF-001',
        ]);
    }

    public function test_member_cannot_upload_proof_for_another_members_invoice(): void
    {
        [$userA] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);
        [, $memberB] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $type = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        $invoiceB = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $memberB->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'UNPAID',
        ]);

        $proof = UploadedFile::fake()->image('bukti.jpg');

        $this->actingAs($userA)
            ->post(route('member.payments.proof'), [
                'cooperative_dues_invoice_id' => $invoiceB->id,
                'amount' => 200000,
                'payment_method' => 'TRANSFER',
                'paid_at' => now()->toDateString(),
                'proof' => $proof,
            ])
            ->assertSessionHasErrors(['cooperative_dues_invoice_id']);
    }

    public function test_member_cannot_upload_proof_for_paid_invoice(): void
    {
        [$user, $member] = $this->makeMember(CooperativeMember::VALIDATION_ACTIVE);

        $type = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 200000,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'PAID',
        ]);

        $proof = UploadedFile::fake()->image('bukti.jpg');

        $this->actingAs($user)
            ->post(route('member.payments.proof'), [
                'cooperative_dues_invoice_id' => $invoice->id,
                'amount' => 200000,
                'payment_method' => 'TRANSFER',
                'paid_at' => now()->toDateString(),
                'proof' => $proof,
            ])
            ->assertSessionHasErrors(['cooperative_dues_invoice_id']);
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function makeMember(string $validationStatus): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'validation_status' => $validationStatus,
            'status' => $validationStatus === CooperativeMember::VALIDATION_ACTIVE ? 'ACTIVE' : 'PENDING',
        ]);

        return [$user, $member];
    }
}
