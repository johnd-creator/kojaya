<?php

namespace Tests\Feature\Security;

use App\Contracts\OrganizationScopedQueryService;
use App\Enums\Co\Pos\BackgroundJobStatus;
use App\Enums\CooperativeShuPeriodStatus;
use App\Enums\InstallmentStatus;
use App\Enums\LoanStatus;
use App\Jobs\GeneratePosReportPdf;
use App\Models\BackgroundJob;
use App\Models\CooperativeMember;
use App\Models\CooperativeShuPeriod;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\NplTrackingService;
use App\Services\Cooperative\PosSalesReportService;
use App\Support\ReportAuthorizationScope;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CooperativeReportsOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        Permission::firstOrCreate(['name' => 'view_cooperative_report']);
        Permission::firstOrCreate(['name' => 'view_pos_reports']);
        Permission::firstOrCreate(['name' => 'view_loan_report']);
        Permission::firstOrCreate(['name' => 'view_cooperative_all']);
        Permission::firstOrCreate(['name' => 'access_cooperative_pos']);
    }

    // =========================================================================
    // GROUP 1: SUMMARY REPORT ISOLATION (Tests 1 - 12)
    // =========================================================================

    public function test_org_a_member_count_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createMembers($orgA, 3);
        $this->createMembers($orgB, 5);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.members.count', 3)
            ->assertJsonPath('data.active_members', 3);
    }

    public function test_org_b_member_count_excludes_org_a(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createMembers($orgA, 3);
        $this->createMembers($orgB, 5);

        $userB = $this->createReportUser($orgB, ['view_cooperative_report']);
        Sanctum::actingAs($userB, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.members.count', 5)
            ->assertJsonPath('data.active_members', 5);
    }

    public function test_returning_customer_count_only_uses_tenant_owned_members(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        // Member A has 2 completed transactions in Org A
        $this->createCompletedTransaction($orgA, $memberA);
        $this->createCompletedTransaction($orgA, $memberA);

        // Member B has 2 completed transactions in Org B
        $this->createCompletedTransaction($orgB, $memberB);
        $this->createCompletedTransaction($orgB, $memberB);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.returning_customers', 1);
    }

    public function test_returning_customer_qualification_only_counts_pos_transactions_whose_immutable_organization_id_matches_tenant_and_member(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        // Member A has 1 transaction in Org A and 1 in Org B
        $this->createCompletedTransaction($orgA, $memberA);
        $this->createCompletedTransaction($orgB, $memberA);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        // Needs >= 2 transactions within Org A, so returning customer count must be 0
        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.returning_customers', 0);
    }

    public function test_corrupt_foreign_transaction_relation_cannot_inflate_returning_customer_count(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);

        // 1 transaction in Org A + 2 corrupt/foreign transactions in Org B
        $this->createCompletedTransaction($orgA, $memberA);
        $this->createCompletedTransaction($orgB, $memberA);
        $this->createCompletedTransaction($orgB, $memberA);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        // Foreign transactions must NOT inflate Org A returning customer count
        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.returning_customers', 0);
    }

    public function test_active_points_member_count_is_scoped_by_member_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'points' => 100,
        ]);
        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'points' => 200,
        ]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.points.active_members', 1);
    }

    public function test_earned_points_sum_is_scoped_by_member_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'EARNED',
            'points' => 150,
        ]);
        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'transaction_type' => 'EARNED',
            'points' => 300,
        ]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.points.earned', 150);
    }

    public function test_redeemed_points_sum_is_scoped_by_member_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA);
        $memberB = $this->createMember($orgB);

        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'transaction_type' => 'REDEEMED',
            'points' => 50,
        ]);
        PointTransaction::factory()->create([
            'cooperative_member_id' => $memberB->id,
            'transaction_type' => 'REDEEMED',
            'points' => 100,
        ]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.points.redeemed', 50);
    }

    public function test_total_revenue_is_scoped_by_pos_transaction_organization_id(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 100000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 250000]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.financial.total_revenue', 100000)
            ->assertJsonPath('data.total_revenue', 100000);
    }

    public function test_gross_profit_is_scoped_by_pos_transaction_organization_id(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['gross_profit' => 25000]);
        $this->createCompletedTransaction($orgB, null, ['gross_profit' => 60000]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.financial.gross_profit', 25000)
            ->assertJsonPath('data.gross_profit', 25000);
    }

    public function test_total_outstanding_is_scoped_by_cooperative_member_organization_id(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createMember($orgA, ['outstanding_balance' => 500000]);
        $this->createMember($orgB, ['outstanding_balance' => 1200000]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.financial.total_outstanding', 500000)
            ->assertJsonPath('data.total_outstanding', 500000);
    }

    public function test_if_only_foreign_data_exists_unit_report_returns_safe_zero_or_empty_values(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        // Only Org B has data
        $memberB = $this->createMember($orgB, ['outstanding_balance' => 800000]);
        $this->createCompletedTransaction($orgB, $memberB, ['total_amount' => 500000, 'gross_profit' => 150000]);
        PointTransaction::factory()->create(['cooperative_member_id' => $memberB->id, 'transaction_type' => 'EARNED', 'points' => 200]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.members.count', 0)
            ->assertJsonPath('data.active_members', 0)
            ->assertJsonPath('data.returning_customers', 0)
            ->assertJsonPath('data.points.active_members', 0)
            ->assertJsonPath('data.points.earned', 0)
            ->assertJsonPath('data.points.redeemed', 0)
            ->assertJsonPath('data.financial.total_revenue', 0)
            ->assertJsonPath('data.financial.gross_profit', 0)
            ->assertJsonPath('data.financial.total_outstanding', 0);
    }

    // =========================================================================
    // GROUP 2: POS SALES REPORT ISOLATION (Tests 13 - 25)
    // =========================================================================

    public function test_org_a_pos_transaction_count_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null);
        $this->createCompletedTransaction($orgA, null);
        $this->createCompletedTransaction($orgB, null);
        $this->createCompletedTransaction($orgB, null);
        $this->createCompletedTransaction($orgB, null);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.count', 2);
    }

    public function test_revenue_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 150000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 300000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.revenue', 150000);
    }

    public function test_gross_profit_excludes_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['gross_profit' => 40000]);
        $this->createCompletedTransaction($orgB, null, ['gross_profit' => 80000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.gross_profit', 40000);
    }

    public function test_by_cashier_contains_no_foreign_transaction_groups(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = User::factory()->create(['organization_id' => $orgA->id, 'name' => 'Kasir A']);
        $cashierB = User::factory()->create(['organization_id' => $orgB->id, 'name' => 'Kasir B']);

        $this->createCompletedTransaction($orgA, null, ['cashier_id' => $cashierA->id]);
        $this->createCompletedTransaction($orgB, null, ['cashier_id' => $cashierB->id]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $response = $this->getJson('/api/v1/reports/sales')
            ->assertOk();

        $byCashier = $response->json('by_cashier');
        $cashierIds = collect($byCashier)->pluck('cashier_id')->all();

        $this->assertContains($cashierA->id, $cashierIds);
        $this->assertNotContains($cashierB->id, $cashierIds);
    }

    public function test_by_cashier_does_not_expose_unrelated_foreign_cashier_identity(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierB = User::factory()->create(['organization_id' => $orgB->id, 'name' => 'Secret Cashier B']);
        $this->createCompletedTransaction($orgB, null, ['cashier_id' => $cashierB->id]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $response = $this->getJson('/api/v1/reports/sales')
            ->assertOk();

        $content = $response->getContent();
        $this->assertStringNotContainsString('Secret Cashier B', $content);
    }

    public function test_foreign_cashier_id_filter_cannot_expose_foreign_data(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierB = User::factory()->create(['organization_id' => $orgB->id]);
        $this->createCompletedTransaction($orgB, null, ['cashier_id' => $cashierB->id, 'total_amount' => 500000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        // Querying Org A sales filtered by foreign cashier_id must return 0 results, never Org B data
        $this->getJson('/api/v1/reports/sales?cashier_id='.$cashierB->id)
            ->assertOk()
            ->assertJsonPath('summary.count', 0)
            ->assertJsonPath('summary.revenue', 0);
    }

    public function test_date_from_preserves_tenant_scope(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['sold_at' => now()->subDay(), 'total_amount' => 50000]);
        $this->createCompletedTransaction($orgB, null, ['sold_at' => now(), 'total_amount' => 999000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales?date_from='.now()->subDays(2)->toDateString())
            ->assertOk()
            ->assertJsonPath('summary.count', 1)
            ->assertJsonPath('summary.revenue', 50000);
    }

    public function test_date_to_preserves_tenant_scope(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['sold_at' => now()->subDays(5), 'total_amount' => 45000]);
        $this->createCompletedTransaction($orgB, null, ['sold_at' => now()->subDays(5), 'total_amount' => 888000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales?date_to='.now()->toDateString())
            ->assertOk()
            ->assertJsonPath('summary.count', 1)
            ->assertJsonPath('summary.revenue', 45000);
    }

    public function test_date_range_preserves_tenant_scope(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['sold_at' => now()->subDays(2), 'total_amount' => 35000]);
        $this->createCompletedTransaction($orgB, null, ['sold_at' => now()->subDays(2), 'total_amount' => 777000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales?date_from='.now()->subDays(3)->toDateString().'&date_to='.now()->subDay()->toDateString())
            ->assertOk()
            ->assertJsonPath('summary.count', 1)
            ->assertJsonPath('summary.revenue', 35000);
    }

    public function test_cashier_reassignment_does_not_change_historical_transaction_ownership(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashier = User::factory()->create(['organization_id' => $orgA->id]);

        $tx = $this->createCompletedTransaction($orgA, null, ['cashier_id' => $cashier->id]);

        // Cashier moves to Org B
        $cashier->update(['organization_id' => $orgB->id]);

        $this->assertSame($orgA->id, $tx->fresh()->organization_id);
    }

    public function test_transaction_stamped_org_a_remains_org_a_report_row_even_if_cashier_later_moves_to_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashier = User::factory()->create(['organization_id' => $orgA->id]);
        $this->createCompletedTransaction($orgA, null, ['cashier_id' => $cashier->id, 'total_amount' => 120000]);

        // Reassign cashier to Org B
        $cashier->update(['organization_id' => $orgB->id]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.count', 1)
            ->assertJsonPath('summary.revenue', 120000);
    }

    public function test_transaction_stamped_org_b_never_becomes_visible_to_org_a_merely_because_cashier_later_moves_to_org_a(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashier = User::factory()->create(['organization_id' => $orgB->id]);
        $this->createCompletedTransaction($orgB, null, ['cashier_id' => $cashier->id, 'total_amount' => 220000]);

        // Reassign cashier to Org A
        $cashier->update(['organization_id' => $orgA->id]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.count', 0)
            ->assertJsonPath('summary.revenue', 0);
    }

    public function test_legacy_null_org_transaction_does_not_leak_into_unit_report(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();

        // Transaction with null organization_id
        PosTransaction::query()->create([
            'transaction_no' => 'TRX-NULL-ORG',
            'organization_id' => null,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'gross_profit' => 15000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.count', 0)
            ->assertJsonPath('summary.revenue', 0);
    }

    // =========================================================================
    // GROUP 3: NPL REPORT ISOLATION (Tests 26 - 35)
    // =========================================================================

    public function test_org_a_aging_buckets_exclude_org_b_loans_and_installments(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createOverdueLoanWithInstallment($orgA, 15, 100000);
        $this->createOverdueLoanWithInstallment($orgB, 15, 250000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.buckets.0.outstanding_amount', 100000)
            ->assertJsonPath('data.buckets.0.installment_count', 1);
    }

    public function test_org_b_aging_buckets_exclude_org_a(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createOverdueLoanWithInstallment($orgA, 15, 100000);
        $this->createOverdueLoanWithInstallment($orgB, 15, 250000);

        $userB = $this->createReportUser($orgB, ['view_cooperative_report']);
        Sanctum::actingAs($userB, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.buckets.0.outstanding_amount', 250000)
            ->assertJsonPath('data.buckets.0.installment_count', 1);
    }

    public function test_current_bucket_or_active_loan_outstanding_scoped_correctly(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createActiveLoan($orgA, 1000000);
        $this->createActiveLoan($orgB, 2500000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.active_loan_outstanding', 1000000);
    }

    public function test_1_to_30_bucket_scoped_correctly(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createOverdueLoanWithInstallment($orgA, 15, 50000);
        $this->createOverdueLoanWithInstallment($orgB, 20, 80000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.buckets.0.bucket', '1-30')
            ->assertJsonPath('data.buckets.0.outstanding_amount', 50000);
    }

    public function test_31_to_60_bucket_scoped_correctly(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createOverdueLoanWithInstallment($orgA, 45, 60000);
        $this->createOverdueLoanWithInstallment($orgB, 40, 90000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.buckets.1.bucket', '31-60')
            ->assertJsonPath('data.buckets.1.outstanding_amount', 60000);
    }

    public function test_61_to_90_and_90_plus_scoped_correctly(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createOverdueLoanWithInstallment($orgA, 75, 70000);
        $this->createOverdueLoanWithInstallment($orgA, 100, 110000);
        $this->createOverdueLoanWithInstallment($orgB, 75, 200000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.buckets.2.bucket', '61-90')
            ->assertJsonPath('data.buckets.2.outstanding_amount', 70000)
            ->assertJsonPath('data.buckets.3.bucket', '91-120')
            ->assertJsonPath('data.buckets.3.outstanding_amount', 110000);
    }

    public function test_npl_ratio_uses_only_tenant_owned_loan_ids(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        // Org A: active loan outstanding = 1,000,000, NPL installment = 200,000 (100 days overdue >= threshold 90) -> ratio = 0.2
        $this->createOverdueLoanWithInstallment($orgA, 100, 200000, 1000000);
        // Org B: active loan outstanding = 2,000,000, NPL installment = 1,000,000 -> ratio = 0.5
        $this->createOverdueLoanWithInstallment($orgB, 100, 1000000, 2000000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.npl_outstanding', 200000)
            ->assertJsonPath('data.active_loan_outstanding', 1000000)
            ->assertJsonPath('data.npl_ratio', 0.2);
    }

    public function test_foreign_installment_cannot_affect_own_org_bucket_totals(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        // Org A has loan with NO overdue installments
        $this->createActiveLoan($orgA, 1000000);
        // Org B has overdue loan
        $this->createOverdueLoanWithInstallment($orgB, 25, 300000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.buckets.0.outstanding_amount', 0)
            ->assertJsonPath('data.buckets.0.installment_count', 0)
            ->assertJsonPath('data.npl_outstanding', 0);
    }

    public function test_direct_npl_tracking_service_unit_context_caller_cannot_retrieve_global_data(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createOverdueLoanWithInstallment($orgA, 15, 120000, 500000);
        $this->createOverdueLoanWithInstallment($orgB, 15, 300000, 800000);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        $service = app(NplTrackingService::class);

        $report = $service->agingReport($userA);
        $this->assertSame(500000.0, (float) $report['active_loan_outstanding']);
        $this->assertSame(120000.0, (float) $report['buckets'][0]['outstanding_amount']);
        $this->assertSame(1, $report['buckets'][0]['installment_count']);
    }

    public function test_null_org_non_global_actor_fails_closed_in_npl_service_and_endpoint(): void
    {
        $nullOrgUser = User::factory()->create([
            'organization_id' => null,
            'name' => 'Null Org Non Global User',
        ]);
        $nullOrgUser->givePermissionTo('view_cooperative_report');

        // Endpoint check
        Sanctum::actingAs($nullOrgUser, ['reports:read']);
        $response = $this->getJson('/api/v1/reports/npl-aging');
        $response->assertForbidden();

        // Direct service check
        $this->expectException(AuthorizationException::class);
        $service = app(NplTrackingService::class);
        $service->agingReport($nullOrgUser);
    }

    // =========================================================================
    // GROUP 4: FUNCTIONAL PERMISSION MATRIX (Tests 36 - 44)
    // =========================================================================

    public function test_view_cooperative_report_with_unit_actor_sees_own_summary_only(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createMembers($orgA, 4);
        $this->createMembers($orgB, 6);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.members.count', 4);
    }

    public function test_view_cooperative_report_without_view_cooperative_all_is_never_global(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createMembers($orgA, 2);
        $this->createMembers($orgB, 7);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        $this->assertFalse($userA->can('view_cooperative_all'));

        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.members.count', 2);
    }

    public function test_view_cooperative_all_without_view_cooperative_report_denies_summary(): void
    {
        [$orgA] = $this->createOrganizations();
        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $user->givePermissionTo('view_cooperative_all');

        Sanctum::actingAs($user, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertForbidden();
    }

    public function test_view_pos_reports_with_unit_actor_sees_own_pos_report_only(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 100000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 200000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.revenue', 100000);
    }

    public function test_view_cooperative_all_without_view_pos_reports_denies_pos_report(): void
    {
        [$orgA] = $this->createOrganizations();
        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $user->givePermissionTo('view_cooperative_all');

        Sanctum::actingAs($user, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertForbidden();
    }

    public function test_global_actor_with_view_cooperative_all_and_view_cooperative_report_sees_global_summary(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createMembers($orgA, 3);
        $this->createMembers($orgB, 4);

        $globalUser = $this->createGlobalReportUser(['view_cooperative_report', 'view_cooperative_all']);
        Sanctum::actingAs($globalUser, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.members.count', 7);
    }

    public function test_global_actor_with_view_cooperative_all_and_view_pos_reports_sees_global_pos_report(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 110000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 220000]);

        $globalUser = $this->createGlobalReportUser(['view_pos_reports', 'view_cooperative_all']);
        Sanctum::actingAs($globalUser, ['reports:read']);

        $this->getJson('/api/v1/reports/sales')
            ->assertOk()
            ->assertJsonPath('summary.count', 2)
            ->assertJsonPath('summary.revenue', 330000);
    }

    public function test_global_actor_with_view_cooperative_all_and_view_loan_report_sees_global_npl_report(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createActiveLoan($orgA, 1000000);
        $this->createActiveLoan($orgB, 2500000);

        $globalUser = $this->createGlobalReportUser(['view_loan_report', 'view_cooperative_all']);
        Sanctum::actingAs($globalUser, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.active_loan_outstanding', 3500000);
    }

    public function test_view_loan_report_without_global_visibility_sees_own_org_npl_only(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createActiveLoan($orgA, 1000000);
        $this->createActiveLoan($orgB, 2500000);

        $userA = $this->createReportUser($orgA, ['view_loan_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $this->getJson('/api/v1/reports/npl-aging')
            ->assertOk()
            ->assertJsonPath('data.active_loan_outstanding', 1000000);
    }

    // =========================================================================
    // GROUP 5: SIDE-EFFECT / MUTATION MATRIX (Tests 45 - 46)
    // =========================================================================

    public function test_report_queries_do_not_mutate_member_point_transaction_loan_or_installment_state(): void
    {
        [$orgA] = $this->createOrganizations();
        $member = $this->createMember($orgA, ['outstanding_balance' => 100000]);
        $tx = $this->createCompletedTransaction($orgA, $member, ['total_amount' => 50000, 'gross_profit' => 10000]);
        $pt = PointTransaction::factory()->create(['cooperative_member_id' => $member->id, 'points' => 100]);
        $loan = $this->createOverdueLoanWithInstallment($orgA, 30, 20000, 100000);

        // Record initial state
        $memberCountBefore = CooperativeMember::query()->count();
        $txCountBefore = PosTransaction::query()->count();
        $ptCountBefore = PointTransaction::query()->count();
        $loanCountBefore = Loan::query()->count();
        $installmentCountBefore = LoanInstallment::query()->count();

        $memberOutstandingBefore = $member->fresh()->outstanding_balance;
        $loanOutstandingBefore = $loan->fresh()->outstanding_amount;

        $user = $this->createReportUser($orgA, ['view_cooperative_report', 'view_pos_reports', 'view_loan_report']);
        Sanctum::actingAs($user, ['reports:read']);

        // Query all report endpoints
        $this->getJson('/api/v1/reports/cooperative-summary')->assertOk();
        $this->getJson('/api/v1/reports/sales')->assertOk();
        $this->getJson('/api/v1/reports/npl-aging')->assertOk();

        // Verify exact same states afterwards
        $this->assertSame($memberCountBefore, CooperativeMember::query()->count());
        $this->assertSame($txCountBefore, PosTransaction::query()->count());
        $this->assertSame($ptCountBefore, PointTransaction::query()->count());
        $this->assertSame($loanCountBefore, Loan::query()->count());
        $this->assertSame($installmentCountBefore, LoanInstallment::query()->count());

        $this->assertSame($memberOutstandingBefore, $member->fresh()->outstanding_balance);
        $this->assertSame($loanOutstandingBefore, $loan->fresh()->outstanding_amount);
    }

    public function test_report_access_denial_has_zero_mutation_side_effects(): void
    {
        [$orgA] = $this->createOrganizations();
        $member = $this->createMember($orgA);

        $memberCountBefore = CooperativeMember::query()->count();
        $txCountBefore = PosTransaction::query()->count();

        $unauthorizedUser = User::factory()->create(['organization_id' => $orgA->id]);
        Sanctum::actingAs($unauthorizedUser, ['reports:read']);

        $this->getJson('/api/v1/reports/cooperative-summary')->assertForbidden();
        $this->getJson('/api/v1/reports/sales')->assertForbidden();
        $this->getJson('/api/v1/reports/npl-aging')->assertForbidden();

        $this->assertSame($memberCountBefore, CooperativeMember::query()->count());
        $this->assertSame($txCountBefore, PosTransaction::query()->count());
    }

    // =========================================================================
    // GROUP 6: REVISION 1 CANONICAL SCOPE & REMAINING ISOLATION (Tests 47 - 59)
    // =========================================================================

    public function test_pos_report_page_does_not_expose_category_used_only_by_org_b_products_to_org_a(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $catA = PosCategory::factory()->create(['name' => 'Kategori Org A']);
        $catB = PosCategory::factory()->create(['name' => 'Kategori Org B']);

        PosProduct::factory()->create(['organization_id' => $orgA->id, 'pos_category_id' => $catA->id]);
        PosProduct::factory()->create(['organization_id' => $orgB->id, 'pos_category_id' => $catB->id]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->get(route('cooperative.pos.reports.index'));
        $response->assertOk();

        $categories = $response->viewData('page')['props']['categories'];
        $categoryIds = collect($categories)->pluck('id')->all();

        $this->assertContains($catA->id, $categoryIds);
        $this->assertNotContains($catB->id, $categoryIds);
    }

    public function test_category_shared_by_an_org_a_product_remains_visible_to_org_a(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $sharedCat = PosCategory::factory()->create(['name' => 'Kategori Bersama']);
        $catBOnly = PosCategory::factory()->create(['name' => 'Kategori Org B Khusus']);

        PosProduct::factory()->create(['organization_id' => $orgA->id, 'pos_category_id' => $sharedCat->id]);
        PosProduct::factory()->create(['organization_id' => $orgB->id, 'pos_category_id' => $sharedCat->id]);
        PosProduct::factory()->create(['organization_id' => $orgB->id, 'pos_category_id' => $catBOnly->id]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->get(route('cooperative.pos.reports.index'));
        $response->assertOk();

        $categories = $response->viewData('page')['props']['categories'];
        $categoryIds = collect($categories)->pluck('id')->all();

        $this->assertContains($sharedCat->id, $categoryIds);
        $this->assertNotContains($catBOnly->id, $categoryIds);
    }

    public function test_direct_pos_sales_report_service_invocation_as_org_a_cannot_include_org_b_transactions(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 100000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 300000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        $service = app(PosSalesReportService::class);

        $summary = $service->summaryForPeriod($userA, now()->toDateString(), now()->toDateString());
        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(100000.0, (float) $summary['gross_sales']);
    }

    public function test_direct_pos_sales_report_service_yearly_summary_cannot_become_global(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 120000, 'sold_at' => now()]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 450000, 'sold_at' => now()]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        $service = app(PosSalesReportService::class);

        $yearlySummary = $service->summaryForYear($userA, now()->year);
        $this->assertSame(1, $yearlySummary['transactions']);
        $this->assertSame(120000.0, (float) $yearlySummary['gross_sales']);
    }

    public function test_direct_product_sales_for_year_cannot_become_global(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $catA = PosCategory::factory()->create();
        $catB = PosCategory::factory()->create();
        $pA = PosProduct::factory()->create(['organization_id' => $orgA->id, 'pos_category_id' => $catA->id, 'sale_price' => 5000]);
        $pB = PosProduct::factory()->create(['organization_id' => $orgB->id, 'pos_category_id' => $catB->id, 'sale_price' => 10000]);

        $txA = $this->createCompletedTransaction($orgA, null, ['total_amount' => 10000, 'sold_at' => now()]);
        PosTransactionItem::query()->create([
            'pos_transaction_id' => $txA->id,
            'pos_product_id' => $pA->id,
            'quantity' => 2,
            'unit_price' => 5000,
            'cost_price' => 1000,
            'unit_profit' => 4000,
            'line_total' => 10000,
            'line_profit' => 8000,
        ]);

        $txB = $this->createCompletedTransaction($orgB, null, ['total_amount' => 30000, 'sold_at' => now()]);
        PosTransactionItem::query()->create([
            'pos_transaction_id' => $txB->id,
            'pos_product_id' => $pB->id,
            'quantity' => 3,
            'unit_price' => 10000,
            'cost_price' => 2000,
            'unit_profit' => 8000,
            'line_total' => 30000,
            'line_profit' => 24000,
        ]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        $service = app(PosSalesReportService::class);

        $yearlyProductSales = $service->productSalesForYear($userA, now()->year);
        $productIds = $yearlyProductSales->pluck('pos_product_id')->all();

        $this->assertContains($pA->id, $productIds);
        $this->assertNotContains($pB->id, $productIds);
    }

    public function test_caller_supplied_foreign_organization_id_cannot_override_actor_scope(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 100000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 400000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        $service = app(PosSalesReportService::class);

        $summary = $service->summaryForPeriod($userA, now()->toDateString(), now()->toDateString(), [
            'organization_id' => $orgB->id,
        ]);

        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(100000.0, (float) $summary['gross_sales']);
    }

    public function test_unit_actor_returns_report_remains_scoped_through_pos_transaction_organization_id(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $txA = $this->createCompletedTransaction($orgA, null, ['total_amount' => 20000]);
        $txB = $this->createCompletedTransaction($orgB, null, ['total_amount' => 30000]);

        PosReturn::query()->create([
            'return_no' => 'RET-A-'.uniqid(),
            'pos_transaction_id' => $txA->id,
            'status' => 'COMPLETED',
            'total_amount' => 10000,
            'returned_at' => now(),
        ]);

        PosReturn::query()->create([
            'return_no' => 'RET-B-'.uniqid(),
            'pos_transaction_id' => $txB->id,
            'status' => 'COMPLETED',
            'total_amount' => 20000,
            'returned_at' => now(),
        ]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports']);
        $service = app(PosSalesReportService::class);

        $summary = $service->summaryForPeriod($userA, now()->toDateString(), now()->toDateString());
        $this->assertSame(1, $summary['returns']['count']);
        $this->assertSame(10000.0, (float) $summary['returns']['total']);
    }

    public function test_csv_export_cannot_cross_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 50000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 80000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->get(route('cooperative.pos.reports.export.csv'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('50000', $content);
        $this->assertStringNotContainsString('80000', $content);
    }

    public function test_pdf_queue_execution_cannot_trust_crafted_foreign_organization_id_from_metadata(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 15000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 95000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $job = BackgroundJob::factory()->create([
            'user_id' => $userA->id,
            'type' => 'pos.report.pdf',
            'metadata' => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
                'filters' => [
                    'organization_id' => $orgB->id,
                ],
            ],
        ]);

        $serviceSpy = \Mockery::spy(app(PosSalesReportService::class));

        (new GeneratePosReportPdf($job->id))->handle(
            $serviceSpy,
            app(AuditLogService::class)
        );

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);

        $serviceSpy->shouldHaveReceived('summaryForPeriod')->with(
            \Mockery::on(fn ($u) => $u->id === $userA->id && (int) $u->organization_id === (int) $orgA->id),
            \Mockery::any(),
            \Mockery::any(),
            \Mockery::on(fn ($f) => ! isset($f['organization_id']))
        );
    }

    public function test_pdf_queue_fails_closed_if_owning_actor_cannot_establish_authorized_organization_scope(): void
    {
        $nullOrgUser = User::factory()->create(['organization_id' => null]);
        $job = BackgroundJob::factory()->create([
            'user_id' => $nullOrgUser->id,
            'type' => 'pos.report.pdf',
            'metadata' => [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ],
        ]);

        $this->expectException(AuthorizationException::class);

        (new GeneratePosReportPdf($job->id))->handle(
            app(PosSalesReportService::class),
            app(AuditLogService::class),
            app(OrganizationScopedQueryService::class)
        );
    }

    public function test_unit_cooperative_summary_does_not_expose_unsupported_global_cooperative_shu_period_values(): void
    {
        [$orgA] = $this->createOrganizations();
        CooperativeShuPeriod::query()->create([
            'year' => 2025,
            'status' => CooperativeShuPeriodStatus::Closed,
            'cooperative_pool' => 50000000,
            'pos_profit_pool' => 25000000,
        ]);

        $userA = $this->createReportUser($orgA, ['view_cooperative_report']);
        Sanctum::actingAs($userA, ['reports:read']);

        $response = $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.latest_shu_year', null);

        $this->assertSame(0.0, (float) $response->json('data.latest_shu_total'));
    }

    public function test_global_actor_with_correct_functional_permission_sees_global_cooperative_shu_period_values(): void
    {
        CooperativeShuPeriod::query()->create([
            'year' => 2025,
            'status' => CooperativeShuPeriodStatus::Closed,
            'cooperative_pool' => 50000000,
            'pos_profit_pool' => 25000000,
        ]);

        $globalUser = $this->createGlobalReportUser(['view_cooperative_report', 'view_cooperative_all']);
        Sanctum::actingAs($globalUser, ['reports:read']);

        $response = $this->getJson('/api/v1/reports/cooperative-summary')
            ->assertOk()
            ->assertJsonPath('data.latest_shu_year', 2025);

        $this->assertSame(75000000.0, (float) $response->json('data.latest_shu_total'));
    }

    public function test_production_report_files_do_not_contain_direct_view_cooperative_all_checks(): void
    {
        $files = [
            app_path('Http/Controllers/Cooperative/CooperativeReportController.php'),
            app_path('Http/Controllers/Cooperative/PosReportController.php'),
            app_path('Services/Cooperative/NplTrackingService.php'),
            app_path('Services/Cooperative/PosSalesReportService.php'),
            app_path('Jobs/GeneratePosReportPdf.php'),
        ];

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                'view_cooperative_all',
                $content,
                "File [{$file}] contains unexpected direct 'view_cooperative_all' permission reference."
            );
        }
    }

    // =========================================================================
    // GROUP 6: R2 BACKGROUND REPORT SCOPE BINDING & REAUTHORIZATION (Tests 60 - 79)
    // =========================================================================

    public function test_r2_01_enqueued_unit_report_stores_canonical_org_a_scope(): void
    {
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf', [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
        ]);

        $response->assertStatus(202);
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $this->assertSame([
            'version' => 1,
            'mode' => 'organization',
            'organization_id' => (string) $orgA->id,
        ], $job->metadata['report_scope']);
    }

    public function test_r2_02_client_cannot_inject_org_b_as_report_scope(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf', [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'organization_id' => $orgB->id,
            'report_scope' => [
                'version' => 1,
                'mode' => 'organization',
                'organization_id' => (string) $orgB->id,
            ],
            'filters' => [
                'organization_id' => $orgB->id,
                'report_scope' => ['mode' => 'global'],
            ],
        ]);

        $response->assertStatus(202);
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $this->assertSame((string) $orgA->id, $job->metadata['report_scope']['organization_id']);
        $this->assertSame('organization', $job->metadata['report_scope']['mode']);
        $this->assertArrayNotHasKey('organization_id', $job->metadata['filters']);
    }

    public function test_r2_03_unit_a_remains_unit_a_before_execution_pdf_succeeds_and_is_org_a_only(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 10000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 50000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);
        Storage::disk($job->disk)->assertExists($job->file_path);

        $service = app(PosSalesReportService::class)->setScopeCeiling(
            ReportAuthorizationScope::fromArray($job->metadata['report_scope'])
        );
        $summary = $service->summaryForPeriod($userA, $job->metadata['from'], $job->metadata['to']);
        $this->assertSame(10000.0, (float) $summary['gross_sales']);
    }

    public function test_r2_04_unit_a_becomes_global_before_execution_pdf_remains_org_a_only_no_widening(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 10000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 50000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $userA->givePermissionTo('view_cooperative_all');

        (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);
        $this->assertSame('organization', $job->metadata['report_scope']['mode']);
        $this->assertSame((string) $orgA->id, $job->metadata['report_scope']['organization_id']);

        $service = app(PosSalesReportService::class)->setScopeCeiling(
            ReportAuthorizationScope::fromArray($job->metadata['report_scope'])
        );
        $summary = $service->summaryForPeriod($userA, $job->metadata['from'], $job->metadata['to']);
        $this->assertSame(10000.0, (float) $summary['gross_sales']);
    }

    public function test_r2_05_unit_a_reassigned_to_org_b_before_execution_fails_closed_no_pdf(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf');
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $userA->update(['organization_id' => $orgB->id]);

        try {
            (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Stored report organization scope does not match current user organization', $e->getMessage());
        }

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Failed, $job->status);
        $this->assertNull($job->file_path);
    }

    public function test_r2_06_unit_a_null_org_non_global_before_execution_fails_closed(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf');
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $userA->update(['organization_id' => null]);

        try {
            (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            // Expected
        }

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Failed, $job->status);
        $this->assertNull($job->file_path);
    }

    public function test_r2_07_global_remains_global_pdf_succeeds(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 10000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 50000]);

        $globalUser = $this->createGlobalReportUser(['view_pos_reports', 'view_cooperative_all', 'access_cooperative_pos']);

        $response = $this->actingAs($globalUser)->postJson('/cooperative/pos/reports/export.pdf', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
        ]);
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $this->assertSame('global', $job->metadata['report_scope']['mode']);
        $this->assertNull($job->metadata['report_scope']['organization_id']);

        (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);
        Storage::disk($job->disk)->assertExists($job->file_path);

        $service = app(PosSalesReportService::class)->setScopeCeiling(
            ReportAuthorizationScope::fromArray($job->metadata['report_scope'])
        );
        $summary = $service->summaryForPeriod($globalUser, $job->metadata['from'], $job->metadata['to']);
        $this->assertSame(60000.0, (float) $summary['gross_sales']);
    }

    public function test_r2_08_global_loses_view_cooperative_all_before_execution_fails_closed(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $globalUser = $this->createGlobalReportUser(['view_pos_reports', 'view_cooperative_all', 'access_cooperative_pos']);

        $response = $this->actingAs($globalUser)->postJson('/cooperative/pos/reports/export.pdf');
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $globalUser->revokePermissionTo('view_cooperative_all');
        $globalUser->update(['organization_id' => $orgA->id]);

        try {
            (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Stored global report scope exceeds current organization scope', $e->getMessage());
        }

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Failed, $job->status);
        $this->assertNull($job->file_path);
    }

    public function test_r2_09_user_loses_view_pos_reports_before_worker_execution_fails_closed(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf');
        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();

        $userA->revokePermissionTo('view_pos_reports');

        try {
            (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Owning user lacks view_pos_reports permission', $e->getMessage());
        }

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Failed, $job->status);
        $this->assertNull($job->file_path);
    }

    public function test_r2_10_completed_org_a_pdf_and_owner_still_org_a_download_succeeds(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $userA->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'organization',
                    'organization_id' => (string) $orgA->id,
                ],
            ],
        ]);

        $this->actingAs($userA)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertOk();
    }

    public function test_r2_11_completed_org_a_pdf_and_owner_becomes_global_download_succeeds(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $userA->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'organization',
                    'organization_id' => (string) $orgA->id,
                ],
            ],
        ]);

        $userA->givePermissionTo('view_cooperative_all');

        $this->actingAs($userA)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertOk();
    }

    public function test_r2_12_completed_org_a_pdf_and_owner_reassigned_org_b_download_denied(): void
    {
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $userA->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'organization',
                    'organization_id' => (string) $orgA->id,
                ],
            ],
        ]);

        $userA->update(['organization_id' => $orgB->id]);

        $this->actingAs($userA)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertNotFound();

        $this->actingAs($userA)
            ->getJson(route('cooperative.pos.reports.export.pdf.status', $job))
            ->assertNotFound();
    }

    public function test_r2_13_completed_global_pdf_and_owner_still_global_download_succeeds(): void
    {
        Storage::fake('local');
        $globalUser = $this->createGlobalReportUser(['view_pos_reports', 'view_cooperative_all', 'access_cooperative_pos']);

        $relativePath = "reports/test-global-{$globalUser->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $globalUser->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'global',
                    'organization_id' => null,
                ],
            ],
        ]);

        $this->actingAs($globalUser)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertOk();
    }

    public function test_r2_14_completed_global_pdf_and_owner_loses_global_visibility_download_denied(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $globalUser = $this->createGlobalReportUser(['view_pos_reports', 'view_cooperative_all', 'access_cooperative_pos']);

        $relativePath = "reports/test-global-{$globalUser->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $globalUser->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'global',
                    'organization_id' => null,
                ],
            ],
        ]);

        $globalUser->revokePermissionTo('view_cooperative_all');
        $globalUser->update(['organization_id' => $orgA->id]);

        $this->actingAs($globalUser)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertNotFound();

        $this->actingAs($globalUser)
            ->getJson(route('cooperative.pos.reports.export.pdf.status', $job))
            ->assertNotFound();
    }

    public function test_r2_15_user_loses_view_pos_reports_after_completion_status_and_download_denied(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $userA->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'organization',
                    'organization_id' => (string) $orgA->id,
                ],
            ],
        ]);

        $userA->revokePermissionTo('view_pos_reports');

        $this->actingAs($userA)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertForbidden();

        $this->actingAs($userA)
            ->getJson(route('cooperative.pos.reports.export.pdf.status', $job))
            ->assertForbidden();
    }

    public function test_r2_16_missing_report_scope_metadata_download_fails_closed(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-missing-scope-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()
            ->withoutReportScope()
            ->completed($relativePath)
            ->create([
                'user_id' => $userA->id,
                'metadata' => [
                    'from' => '2026-06-01',
                    'to' => '2026-06-30',
                    '__omit_report_scope__' => true,
                ],
            ]);

        $this->actingAs($userA)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertNotFound();

        $this->actingAs($userA)
            ->getJson(route('cooperative.pos.reports.export.pdf.status', $job))
            ->assertNotFound();
    }

    public function test_r2_17_malformed_report_scope_metadata_download_fails_closed(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-malformed-scope-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $userA->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 999,
                    'mode' => 'corrupted_mode',
                ],
            ],
        ]);

        $this->actingAs($userA)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertNotFound();

        $this->actingAs($userA)
            ->getJson(route('cooperative.pos.reports.export.pdf.status', $job))
            ->assertNotFound();
    }

    public function test_r2_18_foreign_owner_still_404_denied(): void
    {
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);
        $userB = $this->createReportUser($orgB, ['view_pos_reports', 'access_cooperative_pos']);

        $relativePath = "reports/test-{$userA->id}.pdf";
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake test');

        $job = BackgroundJob::factory()->completed($relativePath)->create([
            'user_id' => $userA->id,
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'organization',
                    'organization_id' => (string) $orgA->id,
                ],
            ],
        ]);

        $this->actingAs($userB)
            ->get(route('cooperative.pos.reports.export.pdf.download', $job))
            ->assertNotFound();

        $this->actingAs($userB)
            ->getJson(route('cooperative.pos.reports.export.pdf.status', $job))
            ->assertNotFound();
    }

    public function test_r2_19_crafted_filters_organization_id_cannot_influence_artifact_scope(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$orgA, $orgB] = $this->createOrganizations();
        $this->createCompletedTransaction($orgA, null, ['total_amount' => 20000]);
        $this->createCompletedTransaction($orgB, null, ['total_amount' => 80000]);

        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $response = $this->actingAs($userA)->postJson('/cooperative/pos/reports/export.pdf', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'filters' => [
                'organization_id' => $orgB->id,
            ],
        ]);

        $job = BackgroundJob::query()->where('uuid', $response->json('job_id'))->firstOrFail();
        $this->assertSame((string) $orgA->id, $job->metadata['report_scope']['organization_id']);

        (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Completed, $job->status);

        $service = app(PosSalesReportService::class)->setScopeCeiling(
            ReportAuthorizationScope::fromArray($job->metadata['report_scope'])
        );
        $summary = $service->summaryForPeriod($userA, $job->metadata['from'], $job->metadata['to']);
        $this->assertSame(20000.0, (float) $summary['gross_sales']);
    }

    public function test_r2_20_unexpected_background_job_type_fails_closed(): void
    {
        Storage::fake('local');
        [$orgA] = $this->createOrganizations();
        $userA = $this->createReportUser($orgA, ['view_pos_reports', 'access_cooperative_pos']);

        $job = BackgroundJob::factory()->create([
            'user_id' => $userA->id,
            'type' => 'payroll.export',
            'metadata' => [
                'from' => '2026-06-01',
                'to' => '2026-06-30',
                'report_scope' => [
                    'version' => 1,
                    'mode' => 'organization',
                    'organization_id' => (string) $orgA->id,
                ],
            ],
        ]);

        try {
            (new GeneratePosReportPdf($job->id))->handle(app(PosSalesReportService::class));
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('Invalid job type [payroll.export]', $e->getMessage());
        }

        $job->refresh();
        $this->assertSame(BackgroundJobStatus::Failed, $job->status);
        $this->assertNull($job->file_path);
    }

    // =========================================================================
    // HELPER FIXTURES
    // =========================================================================

    /**
     * @return array{0: Organization, 1: Organization}
     */
    private function createOrganizations(): array
    {
        return [
            Organization::factory()->create(['name' => 'Koperasi Unit A']),
            Organization::factory()->create(['name' => 'Koperasi Unit B']),
        ];
    }

    private function createReportUser(Organization $org, array $perms = ['view_cooperative_report']): User
    {
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Report User '.$org->name,
        ]);
        $user->givePermissionTo($perms);

        return $user;
    }

    private function createGlobalReportUser(array $perms = ['view_cooperative_report', 'view_cooperative_all']): User
    {
        $user = User::factory()->create([
            'organization_id' => null,
            'name' => 'Global Report User',
        ]);
        $user->givePermissionTo($perms);

        return $user;
    }

    private function createMember(Organization $org, array $attrs = []): CooperativeMember
    {
        return CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            ...$attrs,
        ]);
    }

    /**
     * @return array<int, CooperativeMember>
     */
    private function createMembers(Organization $org, int $count): array
    {
        $members = [];
        for ($i = 0; $i < $count; $i++) {
            $members[] = $this->createMember($org);
        }

        return $members;
    }

    private function createCompletedTransaction(Organization $org, ?CooperativeMember $member = null, array $attrs = []): PosTransaction
    {
        return PosTransaction::query()->create([
            'transaction_no' => 'TRX-'.uniqid(),
            'organization_id' => $org->id,
            'cooperative_member_id' => $member?->id,
            'cashier_id' => $attrs['cashier_id'] ?? null,
            'subtotal' => $attrs['total_amount'] ?? 100000,
            'discount_amount' => 0,
            'total_amount' => $attrs['total_amount'] ?? 100000,
            'gross_profit' => $attrs['gross_profit'] ?? 25000,
            'status' => 'COMPLETED',
            'sold_at' => $attrs['sold_at'] ?? now(),
        ]);
    }

    private function createActiveLoan(Organization $org, float $outstanding = 1000000): Loan
    {
        $member = $this->createMember($org);
        $loanType = LoanType::factory()->create(['npl_threshold_days' => 90]);

        return Loan::factory()->active()->create([
            'organization_id' => $org->id,
            'loan_type_id' => $loanType->id,
            'cooperative_member_id' => $member->id,
            'outstanding_amount' => $outstanding,
            'status' => LoanStatus::Active,
        ]);
    }

    private function createOverdueLoanWithInstallment(Organization $org, int $daysOverdue, float $overdueAmount, float $outstanding = 1000000): Loan
    {
        $member = $this->createMember($org);
        $loanType = LoanType::factory()->create(['npl_threshold_days' => 90]);

        $loan = Loan::factory()->active()->create([
            'organization_id' => $org->id,
            'loan_type_id' => $loanType->id,
            'cooperative_member_id' => $member->id,
            'outstanding_amount' => $outstanding,
            'status' => LoanStatus::Active,
        ]);

        LoanInstallment::factory()->create([
            'loan_id' => $loan->id,
            'due_date' => today()->subDays($daysOverdue)->toDateString(),
            'amount_due' => $overdueAmount,
            'amount_paid' => 0,
            'status' => InstallmentStatus::Overdue,
        ]);

        return $loan;
    }
}
