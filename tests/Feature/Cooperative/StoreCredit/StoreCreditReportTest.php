<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Enums\MemberStoreLedgerEffect;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Services\Cooperative\StoreCreditReportService;
use App\Support\MemberStoreAccountContext;
use App\Support\OrganizationVisibility;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditReportTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Carbon::setTestNow('2026-07-19 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_summary_totals_are_correct(): void
    {
        $organization = Organization::factory()->create();
        $reports = $this->app->make(StoreCreditReportService::class);
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();

        $accountA = $this->account($organization, $ledger, 500000);
        $accountB = $this->account($organization, $ledger, 0, 200000);

        $transaction = $this->makeTransaction();
        $ledger->postPurchase($accountB, $transaction, 75000, $actor, null);

        $summary = $reports->summary(OrganizationVisibility::organization($organization->id));

        $this->assertSame(500000, $summary['positive_deposit_liability']);
        $this->assertSame(75000, $summary['negative_receivable']);
        $this->assertSame(1, $summary['positive_account_count']);
        $this->assertSame(1, $summary['negative_account_count']);
        $this->assertSame(0, $summary['zero_account_count']);
    }

    public function test_fifo_debt_age_returns_oldest_uncovered_purchase(): void
    {
        $organization = Organization::factory()->create();
        $reports = $this->app->make(StoreCreditReportService::class);
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();

        $account = $this->account($organization, $ledger, 0, 500000);

        Carbon::setTestNow('2026-06-01 10:00:00');
        $oldPurchase = PosTransaction::query()->create([
            'transaction_no' => 'RPT-OLD', 'subtotal' => 100000, 'discount_amount' => 0,
            'total_amount' => 100000, 'status' => 'COMPLETED', 'sold_at' => '2026-06-01',
        ]);
        $ledger->postPurchase($account, $oldPurchase, 100000, $actor, null);

        Carbon::setTestNow('2026-07-01 10:00:00');
        $newPurchase = PosTransaction::query()->create([
            'transaction_no' => 'RPT-NEW', 'subtotal' => 100000, 'discount_amount' => 0,
            'total_amount' => 100000, 'status' => 'COMPLETED', 'sold_at' => '2026-07-01',
        ]);
        $ledger->postPurchase($account, $newPurchase, 100000, $actor, null);

        Carbon::setTestNow('2026-07-19 12:00:00');
        $ledger->adjust($account, 100000, MemberStoreLedgerEffect::Credit, $actor, 'repay oldest');

        $oldest = $reports->oldestUncoveredDebtDate($account->refresh());

        $this->assertNotNull($oldest);
        $this->assertSame('2026-07-01', $oldest->toDateString());
    }

    public function test_high_utilization_accounts_listed(): void
    {
        $organization = Organization::factory()->create();
        $reports = $this->app->make(StoreCreditReportService::class);
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();

        $account = $this->account($organization, $ledger, 0, 100000);
        $transaction = $this->makeTransaction();
        $ledger->postPurchase($account, $transaction, 90000, $actor, null);

        $summary = $reports->summary(OrganizationVisibility::organization($organization->id), ['utilization_threshold' => 0.8]);

        $this->assertCount(1, $summary['high_utilization_accounts']);
        $this->assertSame(0.9, $summary['high_utilization_accounts'][0]['utilization']);
    }

    public function test_suspended_and_zero_accounts_classified_correctly(): void
    {
        $organization = Organization::factory()->create();
        $reports = $this->app->make(StoreCreditReportService::class);
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();

        $zeroAccount = $this->account($organization, $ledger);
        $suspendedAccount = $this->account($organization, $ledger, 0, 100000);
        $transaction = $this->makeTransaction();
        $ledger->postPurchase($suspendedAccount, $transaction, 40000, $actor, null);
        $ledger->suspend($suspendedAccount, $actor, 'test');

        $summary = $reports->summary(OrganizationVisibility::organization($organization->id));

        $this->assertSame(1, $summary['zero_account_count']);
        $this->assertSame(1, $summary['suspended_account_count']);
    }

    public function test_global_summary_aggregates_accounts_and_debt_age_across_organizations(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $reports = $this->app->make(StoreCreditReportService::class);
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $actor = User::factory()->create();

        $positiveAccount = $this->account($organizationA, $ledger, 500000);
        $debtAccount = $this->account($organizationB, $ledger, 0, 100000);
        $suspendedAccount = $this->account($organizationB, $ledger);

        Carbon::setTestNow('2026-06-01 10:00:00');
        $purchase = $this->makeTransaction();
        $ledger->postPurchase($debtAccount, $purchase, 90000, $actor, null);
        $ledger->suspend($suspendedAccount, $actor, 'test');

        $global = $reports->summary(OrganizationVisibility::global());
        $scoped = $reports->summary(OrganizationVisibility::organization($organizationA->id));

        $this->assertNull($global['organization_id']);
        $this->assertSame(500000, $global['positive_deposit_liability']);
        $this->assertSame(90000, $global['negative_receivable']);
        $this->assertSame(1, $global['positive_account_count']);
        $this->assertSame(1, $global['negative_account_count']);
        $this->assertSame(1, $global['suspended_account_count']);
        $this->assertCount(1, $global['high_utilization_accounts']);
        $this->assertSame('2026-06-01', $global['oldest_uncovered_debt_date']);

        $this->assertSame($organizationA->id, $scoped['organization_id']);
        $this->assertSame(1, $scoped['positive_account_count']);
        $this->assertSame(0, $scoped['negative_account_count']);
        $this->assertSame(0, $scoped['suspended_account_count']);
        $this->assertCount(0, $scoped['high_utilization_accounts']);
        $this->assertNull($scoped['oldest_uncovered_debt_date']);
    }

    public function test_global_report_route_returns_summary_for_multiple_organizations(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $admin = User::factory()->create(['organization_id' => $organizationA->id]);
        $admin->givePermissionTo(['report_store_credit', 'view_store_credit_all']);
        $actor = User::factory()->create();

        $this->account($organizationA, $ledger, 250000);
        $debtAccount = $this->account($organizationB, $ledger, 0, 100000);
        Carbon::setTestNow('2026-06-15 10:00:00');
        $ledger->postPurchase($debtAccount, $this->makeTransaction(), 90000, $actor, null);

        $this->actingAs($admin)
            ->get(route('cooperative.store-credit.report'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cooperative/StoreCredit/Report')
                ->where('summary.organization_id', null)
                ->where('summary.positive_account_count', 1)
                ->where('summary.negative_account_count', 1)
                ->where('summary.high_utilization_accounts.0.cooperative_member_id', $debtAccount->cooperative_member_id)
            );
    }

    private function account(Organization $organization, StoreCreditLedgerService $ledger, int $opening = 0, int $limit = 0): MemberStoreAccount
    {
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: $organization->id,
            cooperativeMemberId: $member->id,
            creditLimit: $limit,
            openingBalance: $opening,
            openedBy: User::factory()->create(),
        ));
    }

    private function makeTransaction(): PosTransaction
    {
        return PosTransaction::query()->create([
            'transaction_no' => 'RPT-'.uniqid(),
            'subtotal' => 100000, 'discount_amount' => 0,
            'total_amount' => 100000, 'status' => 'COMPLETED', 'sold_at' => now()->toDateString(),
        ]);
    }
}
