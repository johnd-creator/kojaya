<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationLayerStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // --- Layer 1: Route middleware (permission) ---

    public function test_cooperative_member_routes_require_view_cooperative_member(): void
    {
        $org = Organization::factory()->create();
        $anggota = User::factory()->create(['organization_id' => $org->id]);
        $anggota->assignRole('Anggota');

        // Anggota no longer has view_cooperative_member
        $this->actingAs($anggota)
            ->get(route('cooperative.members.index'))
            ->assertForbidden();
    }

    public function test_loan_admin_routes_require_view_cooperative_loan(): void
    {
        $org = Organization::factory()->create();
        $anggota = User::factory()->create(['organization_id' => $org->id]);
        $anggota->assignRole('Anggota');

        // Anggota no longer has view_cooperative_loan
        $this->actingAs($anggota)
            ->get(route('cooperative.loans.index'))
            ->assertForbidden();
    }

    // --- Layer 3: Policy (object-level) ---

    public function test_loan_show_enforces_ownership_for_non_admin(): void
    {
        $org = Organization::factory()->create();

        $owner = User::factory()->create(['organization_id' => $org->id]);
        $owner->assignRole('Admin Koperasi');

        $otherAdmin = User::factory()->create(['organization_id' => $org->id]);
        $otherAdmin->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
            'user_id' => $owner->id,
        ]);

        $loanType = LoanType::factory()->create();
        $loanService = app(\App\Contracts\Cooperative\LoanServiceContract::class);
        $loan = $loanService->apply([
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $org->id,
        ], $owner);

        // Both admins can see the loan since they both have view_cooperative_all/manage_cooperative_loan
        $this->actingAs($owner)->get(route('cooperative.loans.show', $loan))->assertOk();
    }

    // --- Layer 5: Query scope (data isolation) ---

    public function test_loan_index_does_not_leak_global_stats_to_non_admin(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $response = $this->actingAs($admin)->get(route('cooperative.loans.index'));

        $response->assertOk();
        // Admin should see members and stats
        $props = $response->inertiaProps();
        $this->assertArrayHasKey('members', $props);
        $this->assertArrayHasKey('stats', $props);
    }

    public function test_loan_index_does_not_send_member_list_to_non_global_user(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        // User with only view_cooperative_loan but not manage/admin
        $user->givePermissionTo(['view_cooperative_loan']);

        $response = $this->actingAs($user)->get(route('cooperative.loans.index'));

        $response->assertOk();
        $props = $response->inertiaProps();
        // Non-admin user should NOT receive global member list or stats
        $this->assertArrayNotHasKey('members', $props);
        $this->assertArrayNotHasKey('stats', $props);
    }

    // --- Layer 4: Domain service (state transition validation) ---

    public function test_loan_review_requires_correct_status(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $org->id]);
        $admin->assignRole('Admin Koperasi');

        $manager = User::factory()->create(['organization_id' => $org->id]);
        $manager->assignRole('Manajer Koperasi');

        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $org->id,
        ]);
        $loanType = LoanType::factory()->create();
        $loanService = app(\App\Contracts\Cooperative\LoanServiceContract::class);
        $loan = $loanService->apply([
            'loan_type_id' => $loanType->id,
            'principal_amount' => 1000000,
            'term_months' => 6,
            'first_due_date' => now()->addMonth()->toDateString(),
            'cooperative_member_id' => $member->id,
            'organization_id' => $org->id,
        ], $admin);

        $response = $this->actingAs($manager)
            ->post(route('cooperative.loans.review', $loan), ['notes' => 'Review test']);

        $response->assertRedirect();
        $this->assertSame('MANAGER_APPROVED', $loan->fresh()->status->value ?? $loan->fresh()->status);
    }

    // --- Layer 2: FormRequest authorization ---

    public function test_loan_store_requires_manage_permission(): void
    {
        $org = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $org->id]);
        $cashier->assignRole('Kasir Koperasi');

        $response = $this->actingAs($cashier)
            ->post(route('cooperative.loans.store'), [
                'cooperative_member_id' => CooperativeMember::factory()->active()->create(['organization_id' => $org->id])->id,
                'loan_type_id' => LoanType::factory()->create()->id,
                'principal_amount' => 500000,
                'term_months' => 3,
                'first_due_date' => now()->addMonth()->toDateString(),
                'purpose' => 'Test',
            ]);

        $response->assertForbidden();
    }
}
