<?php

namespace Tests\Feature\Cooperative;

use App\Enums\MemberStoreAccountStatus;
use App\Enums\MemberStoreDelegateStatus;
use App\Enums\MemberStoreFundingMethod;
use App\Enums\MemberStoreFundingStatus;
use App\Enums\MemberStoreLedgerEffect;
use App\Enums\MemberStoreLedgerEntryType;
use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreDelegate;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditFundingService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class StoreCreditFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Organization $otherOrganization;

    protected User $adminUser;

    protected User $pengurusUser;

    protected User $cashierUser;

    protected User $otherOrgUser;

    protected User $memberUserA;

    protected CooperativeMember $memberA;

    protected User $memberUserB;

    protected CooperativeMember $memberB;

    protected CooperativeMember $otherOrgMember;

    protected PosProduct $posProduct;

    protected StoreCreditLedgerService $ledgerService;

    protected StoreCreditFundingService $fundingService;

    protected StoreCreditDelegateService $delegateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');

        $this->ledgerService = $this->app->make(StoreCreditLedgerService::class);
        $this->fundingService = $this->app->make(StoreCreditFundingService::class);
        $this->delegateService = $this->app->make(StoreCreditDelegateService::class);

        $this->organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Koperasi Primer Sejahtera',
        ]);

        $this->otherOrganization = Organization::factory()->create([
            'code' => 'KOP-002',
            'name' => 'Koperasi Sekunder Mandiri',
        ]);

        // Admin Koperasi in KOP-001: manage_store_credit, view_store_credit
        $this->adminUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Admin Koperasi Store',
            'email' => 'admin.store@kojaya.test',
        ]);
        $this->adminUser->assignRole('Admin Koperasi');
        $this->adminUser->givePermissionTo([
            'manage_store_credit',
            'view_store_credit',
            'view_cooperative_member',
        ]);

        // Pengurus Koperasi in KOP-001: elevated permissions including manage_store_credit_limit, approve_store_credit_transfer, adjust_store_credit
        $this->pengurusUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Pengurus Koperasi Store',
            'email' => 'pengurus.store@kojaya.test',
        ]);
        $this->pengurusUser->assignRole('Pengurus Koperasi');
        $this->pengurusUser->givePermissionTo([
            'manage_store_credit',
            'manage_store_credit_limit',
            'approve_store_credit_transfer',
            'adjust_store_credit',
            'view_store_credit',
            'view_cooperative_member',
        ]);

        // Kasir Koperasi in KOP-001: access_cooperative_pos, cashier_store_credit
        $this->cashierUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Kasir Koperasi Store',
            'email' => 'kasir.store@kojaya.test',
        ]);
        $this->cashierUser->assignRole('Kasir Koperasi');
        $this->cashierUser->givePermissionTo([
            'access_cooperative_pos',
            'cashier_store_credit',
            'view_store_credit',
        ]);

        // User in other organization KOP-002
        $this->otherOrgUser = User::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Other Org User',
            'email' => 'other.org@kojaya.test',
        ]);
        $this->otherOrgUser->assignRole('Admin Koperasi');
        $this->otherOrgUser->givePermissionTo([
            'manage_store_credit',
            'view_store_credit',
            'approve_store_credit_transfer',
        ]);

        // Member A in KOP-001
        $this->memberUserA = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Member A Test',
            'email' => 'member.a@kojaya.test',
        ]);
        $this->memberUserA->assignRole('Anggota');
        $this->memberA = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->memberUserA->id,
            'name' => 'Member A Test',
            'member_no' => 'MBR-001-A',
            'no_anggota' => 'MBR-001-A',
        ]);

        // Member B in KOP-001
        $this->memberUserB = User::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Member B Test',
            'email' => 'member.b@kojaya.test',
        ]);
        $this->memberUserB->assignRole('Anggota');
        $this->memberB = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->memberUserB->id,
            'name' => 'Member B Test',
            'member_no' => 'MBR-001-B',
            'no_anggota' => 'MBR-001-B',
        ]);

        // Member in other organization KOP-002
        $otherMemberUser = User::factory()->create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Other Member Test',
            'email' => 'other.member@kojaya.test',
        ]);
        $otherMemberUser->assignRole('Anggota');
        $this->otherOrgMember = CooperativeMember::factory()->active()->create([
            'organization_id' => $this->otherOrganization->id,
            'user_id' => $otherMemberUser->id,
            'name' => 'Other Member Test',
            'member_no' => 'MBR-002-X',
            'no_anggota' => 'MBR-002-X',
        ]);

        // POS Product in KOP-001
        $this->posProduct = PosProduct::factory()->create([
            'organization_id' => $this->organization->id,
            'cost_price' => 42000,
            'sale_price' => 50000,
            'stock' => 20,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // STORE-001: Member Store Account Opening & Setup
    // =========================================================================

    public function test_store001_open_account_success_with_credit_limit_and_opening_balance(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('cooperative.store-credit.store'), [
            'cooperative_member_id' => $this->memberA->id,
            'credit_limit' => 500000,
            'opening_balance' => 150000,
            'reason' => 'Pembukaan akun awal anggota',
        ]);

        $account = MemberStoreAccount::query()
            ->where('cooperative_member_id', $this->memberA->id)
            ->first();

        $this->assertNotNull($account);
        $response->assertRedirectToRoute('cooperative.store-credit.show', $account);
        $response->assertSessionHas('success', 'Akun saldo toko anggota dibuka.');

        $this->assertSame($this->organization->id, $account->organization_id);
        $this->assertSame(MemberStoreAccountStatus::Active, $account->status);
        $this->assertSame(500000, $account->credit_limit);
        $this->assertSame(150000, $account->balance);
        $this->assertSame(150000, $account->signedBalance());
        $this->assertSame(650000, $account->availableCredit());
        $this->assertNotNull($account->opened_at);

        // Ledger opening balance entry
        $this->assertDatabaseHas('member_store_ledger_entries', [
            'account_id' => $account->id,
            'organization_id' => $this->organization->id,
            'entry_type' => MemberStoreLedgerEntryType::OpeningBalance->value,
            'effect' => MemberStoreLedgerEffect::Credit->value,
            'amount' => 150000,
            'balance_before' => 0,
            'balance_after' => 150000,
        ]);

        // Audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_store_credit.account.opened',
            'module' => 'store-credit',
            'subject_type' => MemberStoreAccount::class,
            'subject_id' => $account->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_store001_duplicate_account_opening_prevention_fails_with_zero_mutations(): void
    {
        // Pre-create account for memberA
        $account = $this->openTestAccount($this->memberA, 100000, 300000);
        $initialAccountCount = MemberStoreAccount::query()->count();
        $initialLedgerCount = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count();
        $initialAuditCount = AuditLog::query()->where('subject_type', MemberStoreAccount::class)->count();

        // Attempt second account opening for same member
        $response = $this->actingAs($this->adminUser)->postJson(route('cooperative.store-credit.store'), [
            'cooperative_member_id' => $this->memberA->id,
            'credit_limit' => 999999,
            'opening_balance' => 999999,
            'reason' => 'Akun duplikat tidak sah',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'cooperative_member_id' => 'Anggota tersebut sudah memiliki akun Saldo Toko.',
            ]);

        // Zero mutation assertions
        $this->assertSame($initialAccountCount, MemberStoreAccount::query()->count());
        $account->refresh();
        $this->assertSame(300000, $account->credit_limit);
        $this->assertSame(100000, $account->balance);
        $this->assertSame($initialLedgerCount, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
        $this->assertSame($initialAuditCount, AuditLog::query()->where('subject_type', MemberStoreAccount::class)->count());
    }

    public function test_store001_cross_organization_member_tampering_denied_with_zero_mutations(): void
    {
        $initialAccountCount = MemberStoreAccount::query()->count();

        // Admin from KOP-001 attempts to open account for member of KOP-002
        $response = $this->actingAs($this->adminUser)->postJson(route('cooperative.store-credit.store'), [
            'cooperative_member_id' => $this->otherOrgMember->id,
            'credit_limit' => 500000,
            'opening_balance' => 100000,
        ]);

        $response->assertNotFound();
        $this->assertSame($initialAccountCount, MemberStoreAccount::query()->count());
        $this->assertDatabaseMissing('member_store_accounts', [
            'cooperative_member_id' => $this->otherOrgMember->id,
        ]);
    }

    public function test_store001_negative_credit_limit_or_balance_validation_fails_closed(): void
    {
        $initialAccountCount = MemberStoreAccount::query()->count();

        $response = $this->actingAs($this->adminUser)->postJson(route('cooperative.store-credit.store'), [
            'cooperative_member_id' => $this->memberA->id,
            'credit_limit' => -50000,
            'opening_balance' => -10000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credit_limit', 'opening_balance']);

        $this->assertSame($initialAccountCount, MemberStoreAccount::query()->count());
    }

    // =========================================================================
    // STORE-002: Cash Deposit (Top-up Balance)
    // =========================================================================

    public function test_store002_cash_funding_credits_balance_and_posts_ledger_entry(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000, creditLimit: 100000);

        $response = $this->actingAs($this->cashierUser)->post(
            route('cooperative.store-credit.cash-funding', $account->id),
            [
                'amount' => 200000,
                'reference_no' => 'CASH-DEP-001',
                'notes' => 'Setoran tunai via kasir',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Setoran tunai diposting.');

        $account->refresh();
        $this->assertSame(250000, $account->balance);
        $this->assertSame(250000, $account->signedBalance());
        $this->assertSame(350000, $account->availableCredit());

        // Verify MemberStoreFundingRequest created with status approved
        $funding = MemberStoreFundingRequest::query()
            ->where('account_id', $account->id)
            ->where('method', MemberStoreFundingMethod::Cash->value)
            ->first();

        $this->assertNotNull($funding);
        $this->assertSame(MemberStoreFundingStatus::Approved, $funding->status);
        $this->assertSame(200000, (int) $funding->amount);
        $this->assertSame($this->cashierUser->id, $funding->reviewed_by);
        $this->assertNotNull($funding->posted_ledger_entry_id);

        // Verify MemberStoreLedgerEntry
        $entry = MemberStoreLedgerEntry::query()->find($funding->posted_ledger_entry_id);
        $this->assertNotNull($entry);
        $this->assertSame(MemberStoreLedgerEntryType::CashFunding, $entry->entry_type);
        $this->assertSame(MemberStoreLedgerEffect::Credit, $entry->effect);
        $this->assertSame(200000, $entry->amount);
        $this->assertSame(50000, $entry->balance_before);
        $this->assertSame(250000, $entry->balance_after);
        $this->assertSame($this->cashierUser->id, $entry->actor_user_id);
    }

    public function test_store002_cash_funding_idempotency_prevents_duplicate_credits(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);
        $idempotencyKey = 'idempotent-cash-key-999';

        $payload = [
            'amount' => 150000,
            'reference_no' => 'CASH-IDEM-001',
            'idempotency_key' => $idempotencyKey,
        ];

        $first = $this->actingAs($this->cashierUser)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('cooperative.store-credit.cash-funding', $account->id), $payload);

        $second = $this->actingAs($this->cashierUser)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post(route('cooperative.store-credit.cash-funding', $account->id), $payload);

        $first->assertRedirect();
        $second->assertRedirect();

        $this->assertSame(250000, $account->refresh()->balance);
        $this->assertSame(1, MemberStoreFundingRequest::query()->where('account_id', $account->id)->count());
        $this->assertSame(2, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count()); // 1 opening + 1 cash
    }

    public function test_store002_zero_or_negative_cash_funding_rejected_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);
        $initialBalance = $account->balance;
        $initialLedgerCount = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count();

        $responseZero = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.cash-funding', $account->id),
            ['amount' => 0]
        );
        $responseZero->assertStatus(422)->assertJsonValidationErrors('amount');

        $responseNegative = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.cash-funding', $account->id),
            ['amount' => -50000]
        );
        $responseNegative->assertStatus(422)->assertJsonValidationErrors('amount');

        $this->assertSame($initialBalance, $account->refresh()->balance);
        $this->assertSame($initialLedgerCount, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
        $this->assertSame(0, MemberStoreFundingRequest::query()->where('account_id', $account->id)->count());
    }

    public function test_store002_large_deposit_exceeding_max_threshold_rejected_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);
        $initialBalance = $account->balance;

        // Threshold in StoreCashFundingRequest is max:100000000000 (100 billion)
        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.cash-funding', $account->id),
            ['amount' => 100000000001]
        );

        $response->assertStatus(422)->assertJsonValidationErrors('amount');
        $this->assertSame($initialBalance, $account->refresh()->balance);
    }

    public function test_store002_unauthorized_user_denied_cash_funding_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);
        $initialBalance = $account->balance;

        // Member User without cashier_store_credit attempts cash funding
        $response = $this->actingAs($this->memberUserA)->postJson(
            route('cooperative.store-credit.cash-funding', $account->id),
            ['amount' => 50000]
        );

        $response->assertForbidden();
        $this->assertSame($initialBalance, $account->refresh()->balance);
        $this->assertSame(0, MemberStoreFundingRequest::query()->where('account_id', $account->id)->count());
    }

    public function test_store002_cash_funding_on_closed_account_fails_closed(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 0);
        $this->ledgerService->close($account, $this->adminUser, 'Tutup akun');
        $this->assertSame(MemberStoreAccountStatus::Closed, $account->refresh()->status);

        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.cash-funding', $account->id),
            ['amount' => 50000]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account' => 'Akun yang ditutup tidak dapat menerima setoran.']);

        $this->assertSame(0, $account->refresh()->balance);
    }

    // =========================================================================
    // STORE-003: Credit Limit Adjustment
    // =========================================================================

    public function test_store003_authorized_pengurus_can_adjust_credit_limit_with_audit_log(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 0, creditLimit: 200000);

        $response = $this->actingAs($this->pengurusUser)->post(
            route('cooperative.store-credit.limit', $account->id),
            [
                'credit_limit' => 500000,
                'reason' => 'Peningkatan plafon kredit disetujui Pengurus',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Limit kredit diperbarui.');

        $this->assertSame(500000, $account->refresh()->credit_limit);

        // Audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_store_credit.limit.changed',
            'module' => 'store-credit',
            'subject_type' => MemberStoreAccount::class,
            'subject_id' => $account->id,
            'user_id' => $this->pengurusUser->id,
        ]);
    }

    public function test_store003_unauthorized_admin_and_cashier_roles_denied_limit_adjustment_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 0, creditLimit: 200000);
        $initialLimit = $account->credit_limit;
        $initialAuditCount = AuditLog::query()->where('action', 'member_store_credit.limit.changed')->count();

        // Admin Koperasi does NOT have manage_store_credit_limit
        $adminResponse = $this->actingAs($this->adminUser)->postJson(
            route('cooperative.store-credit.limit', $account->id),
            [
                'credit_limit' => 800000,
                'reason' => 'Admin mencoba naikkan limit',
            ]
        );
        $adminResponse->assertForbidden();

        // Kasir Koperasi does NOT have manage_store_credit_limit
        $cashierResponse = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.limit', $account->id),
            [
                'credit_limit' => 800000,
                'reason' => 'Kasir mencoba naikkan limit',
            ]
        );
        $cashierResponse->assertForbidden();

        // Zero mutation assertions
        $this->assertSame($initialLimit, $account->refresh()->credit_limit);
        $this->assertSame($initialAuditCount, AuditLog::query()->where('action', 'member_store_credit.limit.changed')->count());
    }

    public function test_store003_negative_credit_limit_rejected_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 0, creditLimit: 200000);

        $response = $this->actingAs($this->pengurusUser)->postJson(
            route('cooperative.store-credit.limit', $account->id),
            [
                'credit_limit' => -100000,
                'reason' => 'Limit negatif tidak valid',
            ]
        );

        $response->assertStatus(422)->assertJsonValidationErrors('credit_limit');
        $this->assertSame(200000, $account->refresh()->credit_limit);
    }

    public function test_store003_limit_decrease_below_current_debt_requires_override_flag(): void
    {
        // Account has debt of 300,000 (balance = -300000, credit_limit = 500000)
        $account = $this->openTestAccount($this->memberA, openingBalance: 0, creditLimit: 500000);
        $this->createPosPurchaseDirect($account, 300000);
        $this->assertSame(-300000, $account->refresh()->signedBalance());

        // Attempt to lower limit to 100,000 (below debt 300,000) without override
        $responseFail = $this->actingAs($this->pengurusUser)->postJson(
            route('cooperative.store-credit.limit', $account->id),
            [
                'credit_limit' => 100000,
                'reason' => 'Turunkan limit di bawah utang tanpa override',
                'override_below_debt' => false,
            ]
        );

        $responseFail->assertStatus(422)
            ->assertJsonValidationErrors([
                'credit_limit' => 'Limit baru lebih rendah dari utang saat ini. Ubah membutuhkan elevated permission.',
            ]);
        $this->assertSame(500000, $account->refresh()->credit_limit);

        // Lower limit with override_below_debt = true
        $responseSuccess = $this->actingAs($this->pengurusUser)->post(
            route('cooperative.store-credit.limit', $account->id),
            [
                'credit_limit' => 100000,
                'reason' => 'Turunkan limit di bawah utang dengan persetujuan Pengurus',
                'override_below_debt' => true,
            ]
        );

        $responseSuccess->assertRedirect();
        $this->assertSame(100000, $account->refresh()->credit_limit);
    }

    // =========================================================================
    // STORE-004: Store Credit POS Purchase Deduction
    // =========================================================================

    public function test_store004_store_credit_purchase_within_balance_succeeds_and_depletes_stock(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 200000, creditLimit: 100000);
        $initialStock = $this->posProduct->stock;

        // Buy 2 units of posProduct @ 50,000 = 100,000
        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-STORE-001',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Member A Sendiri',
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 2],
                ],
            ]
        );

        $response->assertSuccessful();

        $account->refresh();
        $this->assertSame(100000, $account->balance);
        $this->assertSame(100000, $account->signedBalance());
        $this->assertSame(200000, $account->availableCredit());

        // Stock depleted by 2
        $this->assertSame($initialStock - 2, (int) $this->posProduct->refresh()->stock);

        // POS transaction created
        $this->assertDatabaseHas('pos_transactions', [
            'client_reference' => 'POS-TX-STORE-001',
            'cooperative_member_id' => $this->memberA->id,
            'total_amount' => 100000,
            'status' => 'COMPLETED',
        ]);

        // Ledger entry created
        $this->assertDatabaseHas('member_store_ledger_entries', [
            'account_id' => $account->id,
            'entry_type' => MemberStoreLedgerEntryType::PosPurchase->value,
            'effect' => MemberStoreLedgerEffect::Debit->value,
            'amount' => 100000,
            'balance_before' => 200000,
            'balance_after' => 100000,
            'purchaser_name' => 'Member A Sendiri',
        ]);
    }

    public function test_store004_store_credit_purchase_at_exact_available_limit_boundary_succeeds(): void
    {
        // Balance = 50,000, credit_limit = 100,000 => Available total = 150,000
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000, creditLimit: 100000);

        // Buy 3 units of posProduct @ 50,000 = exactly 150,000
        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-EXACT-BOUNDARY',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Member A Sendiri',
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 3],
                ],
            ]
        );

        $response->assertSuccessful();

        $account->refresh();
        $this->assertSame(-100000, $account->balance);
        $this->assertSame(-100000, $account->signedBalance());
        $this->assertSame(0, $account->availableCredit());

        $this->assertDatabaseHas('member_store_ledger_entries', [
            'account_id' => $account->id,
            'amount' => 150000,
            'balance_before' => 50000,
            'balance_after' => -100000,
        ]);
    }

    public function test_store004_store_credit_purchase_exceeding_limit_fails_atomically_with_zero_mutations(): void
    {
        // Balance = 50,000, credit_limit = 100,000 => Available total = 150,000
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000, creditLimit: 100000);
        $initialStock = (int) $this->posProduct->stock;
        $initialLedgerCount = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count();

        // Buy 4 units @ 50,000 = 200,000 (exceeds available 150,000 by 50,000)
        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-OVER-LIMIT',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Member A Sendiri',
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 4],
                ],
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account' => 'Saldo toko tidak mencukupi. Proyeksi saldo melebihi limit kredit anggota.']);

        // Zero mutation assertions
        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'POS-TX-OVER-LIMIT')->count());
        $this->assertSame($initialStock, (int) $this->posProduct->refresh()->stock, 'Stock must not be depleted on failed store credit purchase.');
        $this->assertSame(50000, $account->refresh()->balance, 'Account balance must remain unchanged.');
        $this->assertSame($initialLedgerCount, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
    }

    public function test_store004_duplicate_pos_purchase_replay_is_idempotent(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 500000);
        $initialStock = (int) $this->posProduct->stock;

        $payload = [
            'client_reference' => 'POS-TX-DUP-IDEM',
            'cooperative_member_id' => $this->memberA->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Member A Sendiri',
            'items' => [
                ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
            ],
        ];

        $first = $this->actingAs($this->cashierUser)->postJson(route('cooperative.pos.transactions.store'), $payload);
        $second = $this->actingAs($this->cashierUser)->postJson(route('cooperative.pos.transactions.store'), $payload);

        $first->assertSuccessful();
        $second->assertSuccessful();

        $this->assertSame(1, PosTransaction::query()->where('client_reference', 'POS-TX-DUP-IDEM')->count());
        $this->assertSame($initialStock - 1, (int) $this->posProduct->refresh()->stock);
        $this->assertSame(450000, $account->refresh()->balance);
        $this->assertSame(1, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->count());
    }

    // =========================================================================
    // STORE-005: Family / Delegate Authorization
    // =========================================================================

    public function test_store005_member_can_create_update_and_revoke_delegate_via_api(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 500000);
        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        // 1. Create delegate
        $createResponse = $this->postJson('/api/v1/member/store-account/delegates', [
            'display_name' => 'Adik Kandung (Budi)',
            'per_transaction_limit' => 100000,
            'daily_limit' => 200000,
            'valid_from' => Carbon::today()->toDateString(),
            'expires_at' => Carbon::tomorrow()->toDateString(),
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.display_name', 'Adik Kandung (Budi)')
            ->assertJsonPath('data.per_transaction_limit', 100000)
            ->assertJsonPath('data.daily_limit', 200000)
            ->assertJsonPath('data.status', MemberStoreDelegateStatus::Active->value);

        $delegateId = $createResponse->json('data.id');
        $delegate = MemberStoreDelegate::query()->findOrFail($delegateId);
        $this->assertSame($account->id, $delegate->account_id);
        $this->assertNotEmpty($delegate->code);

        // 2. Update delegate
        $updateResponse = $this->putJson("/api/v1/member/store-account/delegates/{$delegate->id}", [
            'display_name' => 'Budi Hendra (Adik)',
            'per_transaction_limit' => 150000,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.display_name', 'Budi Hendra (Adik)')
            ->assertJsonPath('data.per_transaction_limit', 150000);

        // 3. Revoke delegate
        $revokeResponse = $this->postJson("/api/v1/member/store-account/delegates/{$delegate->id}/revoke");
        $revokeResponse->assertNoContent();

        $this->assertSame(MemberStoreDelegateStatus::Revoked, $delegate->refresh()->status);
        $this->assertNotNull($delegate->revoked_at);
        $this->assertSame($this->memberUserA->id, $delegate->revoked_by);
    }

    public function test_store005_active_delegate_purchase_at_pos_succeeds_with_attribution(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 500000);
        $delegate = $this->delegateService->create($account, [
            'display_name' => 'Istri Member A',
            'per_transaction_limit' => 200000,
        ], $this->memberUserA);

        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-DELEGATE-OK',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Istri Member A',
                'store_delegate_code' => $delegate->code,
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 2],
                ],
            ]
        );

        $response->assertSuccessful();

        $entry = MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->where('entry_type', MemberStoreLedgerEntryType::PosPurchase->value)
            ->firstOrFail();

        $this->assertSame($delegate->id, $entry->delegate_id);
        $this->assertSame('Istri Member A', $entry->purchaser_name);
        $this->assertSame(400000, $account->refresh()->balance);
    }

    public function test_store005_revoked_or_expired_delegate_purchase_fails_closed_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 500000);
        $initialStock = (int) $this->posProduct->stock;

        // Create and revoke delegate
        $delegate = $this->delegateService->create($account, [
            'display_name' => 'Pihak Ketiga',
        ], $this->memberUserA);
        $this->delegateService->revoke($delegate, $this->memberUserA);
        $this->assertFalse($delegate->refresh()->isCurrentlyActive());

        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-DELEGATE-REVOKED',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Pihak Ketiga',
                'store_delegate_code' => $delegate->code,
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
                ],
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delegate' => 'Delegate tidak aktif, sudah kedaluwarsa, atau telah dicabut.']);

        // Zero mutations
        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'POS-TX-DELEGATE-REVOKED')->count());
        $this->assertSame(500000, $account->refresh()->balance);
        $this->assertSame($initialStock, (int) $this->posProduct->refresh()->stock);
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->count());
    }

    public function test_store005_delegate_transaction_and_daily_limit_violations_fail_closed_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 500000);

        $delegate = $this->delegateService->create($account, [
            'display_name' => 'Delegate Berlimit',
            'per_transaction_limit' => 75000, // max 75,000 per tx
            'daily_limit' => 100000,          // max 100,000 daily
        ], $this->memberUserA);

        // Attempt purchase of 100,000 (2 items @ 50,000) => exceeds per_transaction_limit (75,000)
        $responsePerTx = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-DELEGATE-OVER-TX',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Delegate Berlimit',
                'store_delegate_code' => $delegate->code,
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 2],
                ],
            ]
        );

        $responsePerTx->assertStatus(422)
            ->assertJsonValidationErrors(['delegate' => 'Nilai pembelian melebihi limit per transaksi delegate.']);
        $this->assertSame(500000, $account->refresh()->balance);

        // First purchase within per_tx limit: 1 item @ 50,000
        $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-DELEGATE-TX-1',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Delegate Berlimit',
                'store_delegate_code' => $delegate->code,
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
                ],
            ]
        )->assertSuccessful();
        $this->assertSame(450000, $account->refresh()->balance);

        // Second purchase of 1 item (50,000) brings total today to 100,000 (reaches daily limit)
        $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-DELEGATE-TX-2',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Delegate Berlimit',
                'store_delegate_code' => $delegate->code,
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
                ],
            ]
        )->assertSuccessful();
        $this->assertSame(400000, $account->refresh()->balance);

        // Third purchase of 1 item (50,000) exceeds daily limit (100,000)
        $responseDaily = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-DELEGATE-OVER-DAILY',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Delegate Berlimit',
                'store_delegate_code' => $delegate->code,
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
                ],
            ]
        );

        $responseDaily->assertStatus(422)
            ->assertJsonValidationErrors(['delegate' => 'Pembelian akan melebihi limit harian delegate.']);
        $this->assertSame(400000, $account->refresh()->balance);
    }

    public function test_store005_cross_member_and_cross_org_delegate_tampering_denied(): void
    {
        $accountA = $this->openTestAccount($this->memberA, openingBalance: 100000);
        $accountB = $this->openTestAccount($this->memberB, openingBalance: 100000);

        $delegateB = $this->delegateService->create($accountB, [
            'display_name' => 'Delegate Milik Member B',
        ], $this->memberUserB);

        // Member A attempts to update Member B's delegate
        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);
        $responseUpdate = $this->putJson("/api/v1/member/store-account/delegates/{$delegateB->id}", [
            'display_name' => 'Hacked Delegate Name',
        ]);
        $responseUpdate->assertNotFound();

        // Member A attempts to revoke Member B's delegate
        $responseRevoke = $this->postJson("/api/v1/member/store-account/delegates/{$delegateB->id}/revoke");
        $responseRevoke->assertNotFound();

        $this->assertSame(MemberStoreDelegateStatus::Active, $delegateB->refresh()->status);
        $this->assertSame('Delegate Milik Member B', $delegateB->display_name);

        // Member A attempts to create a delegate linked to a user in otherOrganization
        $responseCrossOrg = $this->postJson('/api/v1/member/store-account/delegates', [
            'display_name' => 'Cross Org Delegate',
            'user_id' => $this->otherOrgUser->id,
        ]);
        $responseCrossOrg->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    // =========================================================================
    // STORE-006: Store Account Suspension & Reactivation
    // =========================================================================

    public function test_store006_staff_can_suspend_and_reactivate_account_with_audit_trail(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 300000);

        // Suspend
        $suspendResponse = $this->actingAs($this->adminUser)->post(
            route('cooperative.store-credit.suspend', $account->id),
            ['reason' => 'Verifikasi dokumen anggota']
        );
        $suspendResponse->assertRedirect();
        $suspendResponse->assertSessionHas('success', 'Akun ditangguhkan.');

        $account->refresh();
        $this->assertSame(MemberStoreAccountStatus::Suspended, $account->status);
        $this->assertNotNull($account->suspended_at);
        $this->assertFalse($account->canPurchase());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_store_credit.account.suspended',
            'module' => 'store-credit',
            'subject_id' => $account->id,
            'user_id' => $this->adminUser->id,
        ]);

        // Reactivate
        $reactivateResponse = $this->actingAs($this->adminUser)->post(
            route('cooperative.store-credit.reactivate', $account->id),
            ['reason' => 'Verifikasi selesai, aktifkan kembali']
        );
        $reactivateResponse->assertRedirect();
        $reactivateResponse->assertSessionHas('success', 'Akun diaktifkan kembali.');

        $account->refresh();
        $this->assertSame(MemberStoreAccountStatus::Active, $account->status);
        $this->assertNull($account->suspended_at);
        $this->assertTrue($account->canPurchase());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_store_credit.account.reactivated',
            'module' => 'store-credit',
            'subject_id' => $account->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_store006_purchase_attempt_on_suspended_account_fails_closed_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 300000);
        $this->ledgerService->suspend($account, $this->adminUser, 'Ditangguhkan untuk investigasi');
        $this->assertSame(MemberStoreAccountStatus::Suspended, $account->refresh()->status);
        $initialStock = (int) $this->posProduct->stock;

        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-SUSPENDED-FAIL',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Member A Sendiri',
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
                ],
            ]
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account' => 'Akun yang ditangguhkan tidak dapat melakukan pembelian baru.']);

        // Zero mutation assertions
        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'POS-TX-SUSPENDED-FAIL')->count());
        $this->assertSame(300000, $account->refresh()->balance);
        $this->assertSame($initialStock, (int) $this->posProduct->refresh()->stock);
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->count());
    }

    public function test_store006_reactivated_account_restores_purchasing_power_without_corruption(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 300000);
        $this->ledgerService->suspend($account, $this->adminUser, 'Suspend sementara');
        $this->ledgerService->reactivate($account, $this->adminUser, 'Reaktivasi selesai');
        $this->assertTrue($account->refresh()->canPurchase());

        $response = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.pos.transactions.store'),
            [
                'client_reference' => 'POS-TX-REACTIVATED-OK',
                'cooperative_member_id' => $this->memberA->id,
                'payment_method' => 'MEMBER_STORE_ACCOUNT',
                'purchaser_name' => 'Member A Sendiri',
                'items' => [
                    ['pos_product_id' => $this->posProduct->id, 'quantity' => 1],
                ],
            ]
        );

        $response->assertSuccessful();
        $this->assertSame(250000, $account->refresh()->balance);
        $this->assertSame(1, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->count());
    }

    public function test_store006_unauthorized_user_denied_suspend_and_reactivate_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);

        // Cashier user does NOT have manage_store_credit
        $suspendResponse = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.suspend', $account->id),
            ['reason' => 'Kasir suspend']
        );
        $suspendResponse->assertForbidden();
        $this->assertSame(MemberStoreAccountStatus::Active, $account->refresh()->status);

        // Suspend legally, then cashier attempts reactivate
        $this->ledgerService->suspend($account, $this->adminUser, 'Suspend admin');
        $reactivateResponse = $this->actingAs($this->cashierUser)->postJson(
            route('cooperative.store-credit.reactivate', $account->id),
            ['reason' => 'Kasir reactivate']
        );
        $reactivateResponse->assertForbidden();
        $this->assertSame(MemberStoreAccountStatus::Suspended, $account->refresh()->status);
    }

    // =========================================================================
    // STORE-007: Store Balance Transfer (Bank Transfer Funding Semantics)
    // =========================================================================

    public function test_store007_transfer_funding_submission_creates_pending_request_without_balance_credit(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000);
        Sanctum::actingAs($this->memberUserA, ['member:read', 'member:write']);

        $proofFile = UploadedFile::fake()->create('bukti_transfer.jpg', 150, 'image/jpeg');

        $response = $this->postJson('/api/v1/member/store-account/transfers', [
            'amount' => 200000,
            'bank_reference' => 'BCA-TRF-20260901',
            'proof_file' => $proofFile,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', 200000)
            ->assertJsonPath('data.status', MemberStoreFundingStatus::Pending->value)
            ->assertJsonPath('data.bank_reference', 'BCA-TRF-20260901');

        $fundingId = $response->json('data.id');
        $funding = MemberStoreFundingRequest::query()->findOrFail($fundingId);

        // Balance must remain UNCHANGED during pending stage
        $this->assertSame(50000, $account->refresh()->balance);
        $this->assertNull($funding->posted_ledger_entry_id);
        $this->assertNull($funding->reviewed_by);

        // Proof must be stored privately
        $this->assertNotEmpty($funding->getRawOriginal('proof_path'));
        Storage::disk('local')->assertExists($funding->getRawOriginal('proof_path'));
    }

    public function test_store007_transfer_funding_maker_checker_prevents_submitter_self_approval(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000);

        // Pengurus user submits a transfer funding
        $funding = $this->fundingService->submitTransferFunding(
            account: $account,
            amount: 300000,
            submitter: $this->pengurusUser,
            bankReference: 'REF-MAKER-CHECKER'
        );

        $this->assertSame(MemberStoreFundingStatus::Pending, $funding->status);

        // Same Pengurus user attempts to approve their own transfer
        $response = $this->actingAs($this->pengurusUser)->postJson(
            route('cooperative.store-credit.transfers.process', $funding->id),
            ['decision' => 'approve']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reviewer' => 'Reviewer tidak boleh sama dengan pemohon (maker-checker).']);

        // Zero mutation assertions
        $this->assertSame(MemberStoreFundingStatus::Pending, $funding->refresh()->status);
        $this->assertSame(50000, $account->refresh()->balance);
        $this->assertNull($funding->posted_ledger_entry_id);
    }

    public function test_store007_authorized_reviewer_can_approve_transfer_and_credit_balance(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000);

        // MemberUserA submitted
        $funding = $this->fundingService->submitTransferFunding(
            account: $account,
            amount: 300000,
            submitter: $this->memberUserA,
            bankReference: 'REF-OK-APPROVED'
        );

        // Independent reviewer (Pengurus) approves
        $response = $this->actingAs($this->pengurusUser)->post(
            route('cooperative.store-credit.transfers.process', $funding->id),
            ['decision' => 'approve']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Setoran transfer diproses.');

        $funding->refresh();
        $this->assertSame(MemberStoreFundingStatus::Approved, $funding->status);
        $this->assertSame($this->pengurusUser->id, $funding->reviewed_by);
        $this->assertNotNull($funding->reviewed_at);
        $this->assertNotNull($funding->posted_ledger_entry_id);

        // Balance credited
        $this->assertSame(350000, $account->refresh()->balance);

        // Ledger entry posted
        $this->assertDatabaseHas('member_store_ledger_entries', [
            'id' => $funding->posted_ledger_entry_id,
            'account_id' => $account->id,
            'entry_type' => MemberStoreLedgerEntryType::TransferFunding->value,
            'effect' => MemberStoreLedgerEffect::Credit->value,
            'amount' => 300000,
            'balance_before' => 50000,
            'balance_after' => 350000,
            'actor_user_id' => $this->pengurusUser->id,
        ]);
    }

    public function test_store007_transfer_funding_rejection_leaves_balance_unchanged(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000);

        $funding = $this->fundingService->submitTransferFunding(
            account: $account,
            amount: 300000,
            submitter: $this->memberUserA,
            bankReference: 'REF-FAKE-PROOF'
        );

        $response = $this->actingAs($this->pengurusUser)->post(
            route('cooperative.store-credit.transfers.process', $funding->id),
            [
                'decision' => 'reject',
                'rejection_reason' => 'Bukti mutasi bank tidak terverifikasi di rekening koran.',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Setoran transfer diproses.');

        $funding->refresh();
        $this->assertSame(MemberStoreFundingStatus::Rejected, $funding->status);
        $this->assertSame('Bukti mutasi bank tidak terverifikasi di rekening koran.', $funding->rejection_reason);
        $this->assertSame($this->pengurusUser->id, $funding->reviewed_by);
        $this->assertNull($funding->posted_ledger_entry_id);

        // Balance remains unchanged
        $this->assertSame(50000, $account->refresh()->balance);
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('entry_type', 'transfer_funding')->count());
    }

    public function test_store007_approved_transfer_cannot_be_processed_again_fails_closed(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000);

        $funding = $this->fundingService->submitTransferFunding(
            account: $account,
            amount: 100000,
            submitter: $this->memberUserA,
            bankReference: 'REF-ONCE-ONLY'
        );

        $this->fundingService->approveTransfer($funding, $this->pengurusUser);
        $this->assertSame(150000, $account->refresh()->balance);

        // Attempt second approval
        $response = $this->actingAs($this->pengurusUser)->postJson(
            route('cooperative.store-credit.transfers.process', $funding->id),
            ['decision' => 'approve']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Setoran yang sudah diproses tidak dapat diproses ulang.']);

        $this->assertSame(150000, $account->refresh()->balance, 'Balance must not be double-credited on repeated approval.');
    }

    public function test_store007_unauthorized_user_denied_transfer_approval_with_zero_mutations(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 50000);

        $funding = $this->fundingService->submitTransferFunding(
            account: $account,
            amount: 100000,
            submitter: $this->memberUserA,
            bankReference: 'REF-UNAUTH'
        );

        // AdminUser does NOT have approve_store_credit_transfer
        $response = $this->actingAs($this->adminUser)->postJson(
            route('cooperative.store-credit.transfers.process', $funding->id),
            ['decision' => 'approve']
        );

        $response->assertForbidden();
        $this->assertSame(MemberStoreFundingStatus::Pending, $funding->refresh()->status);
        $this->assertSame(50000, $account->refresh()->balance);
    }

    // =========================================================================
    // STORE-008: Ledger Balance Reconstruction Audit
    // =========================================================================

    public function test_store008_mathematical_balance_invariant_matches_ledger_sum_across_full_lifecycle(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000, creditLimit: 500000);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Step 1: Cash deposit +200,000 => balance 300,000
        $this->fundingService->submitCashFunding($account, 200000, $this->cashierUser, 'CASH-ST8-1');
        $this->assertSame(300000, $account->refresh()->balance);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Step 2: Transfer deposit +150,000 => balance 450,000
        $transfer = $this->fundingService->submitTransferFunding($account, 150000, $this->memberUserA, 'TRF-ST8-1');
        $this->fundingService->approveTransfer($transfer, $this->pengurusUser);
        $this->assertSame(450000, $account->refresh()->balance);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Step 3: POS purchase -180,000 => balance 270,000
        $purchase = $this->createPosPurchaseDirect($account, 180000);
        $this->assertSame(270000, $account->refresh()->balance);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Step 4: POS refund +50,000 => balance 320,000
        $posTx = PosTransaction::query()->findOrFail($purchase->reference_id);
        $this->ledgerService->postRefund($account, $posTx, 50000, $this->cashierUser);
        $this->assertSame(320000, $account->refresh()->balance);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Step 5: Manual adjustment debit -70,000 => balance 250,000
        $this->ledgerService->adjust(
            account: $account,
            amount: 70000,
            effect: MemberStoreLedgerEffect::Debit,
            actor: $this->pengurusUser,
            reason: 'Penyesuaian koreksi debet audit'
        );
        $this->assertSame(250000, $account->refresh()->balance);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Step 6: Large purchase that pushes balance into negative territory (-150,000)
        // 250,000 - 400,000 = -150,000 (within credit limit 500,000)
        $this->createPosPurchaseDirect($account, 400000);
        $this->assertSame(-150000, $account->refresh()->balance);
        $this->assertSame(-150000, $account->refresh()->signedBalance());
        $this->assertSame(350000, $account->refresh()->availableCredit());
        $this->assertExactMathematicalBalanceMatchesLedger($account);
    }

    public function test_store008_ledger_entry_immutability_prevents_update_and_deletion(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);
        $entry = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->firstOrFail();

        // 1. Direct update attempt throws RuntimeException
        try {
            $entry->amount = 999999;
            $entry->save();
            $this->fail('Expected RuntimeException on ledger entry update.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // 2. Direct delete attempt throws RuntimeException
        try {
            $entry->delete();
            $this->fail('Expected RuntimeException on ledger entry delete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // 3. Direct forceDelete attempt throws RuntimeException
        try {
            $entry->forceDelete();
            $this->fail('Expected RuntimeException on ledger entry forceDelete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // Verify entry remains unchanged in DB
        $freshEntry = MemberStoreLedgerEntry::query()->findOrFail($entry->id);
        $this->assertSame(100000, $freshEntry->amount);
    }

    public function test_store008_reversal_workflow_restores_mathematical_balance_with_idempotency(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 200000, creditLimit: 200000);
        $purchase = $this->createPosPurchaseDirect($account, 75000);
        $this->assertSame(125000, $account->refresh()->balance);

        // Reversal of the purchase entry
        $reversal = $this->ledgerService->reverseEntry(
            original: $purchase,
            actor: $this->pengurusUser,
            reason: 'Koreksi kesalahan input transaksi'
        );

        $this->assertNotNull($reversal);
        $this->assertSame(MemberStoreLedgerEntryType::Reversal, $reversal->entry_type);
        $this->assertSame(MemberStoreLedgerEffect::Credit, $reversal->effect);
        $this->assertSame(75000, $reversal->amount);
        $this->assertSame($purchase->id, $reversal->reversal_of_entry_id);
        $this->assertSame(200000, $reversal->balance_after);

        // Balance restored to 200,000
        $this->assertSame(200000, $account->refresh()->balance);
        $this->assertExactMathematicalBalanceMatchesLedger($account);

        // Idempotency: Repeating reversal of same entry returns existing reversal without double-crediting
        $repeatedReversal = $this->ledgerService->reverseEntry(
            original: $purchase,
            actor: $this->pengurusUser,
            reason: 'Reversal ulang'
        );

        $this->assertSame($reversal->id, $repeatedReversal->id);
        $this->assertSame(200000, $account->refresh()->balance);
    }

    public function test_store008_drift_detection_causes_transaction_rollback_and_runtime_exception(): void
    {
        $account = $this->openTestAccount($this->memberA, openingBalance: 100000);

        // Intentionally simulate an out-of-band balance drift in the database
        DB::table('member_store_accounts')
            ->where('id', $account->id)
            ->update(['balance' => 999999]); // drifted from actual ledger sum 100,000

        // Attempting to post any subsequent ledger mutation must detect drift, throw RuntimeException and rollback
        try {
            $this->ledgerService->adjust(
                account: $account->refresh(),
                amount: 10000,
                effect: MemberStoreLedgerEffect::Credit,
                actor: $this->pengurusUser,
                reason: 'Mencoba transaksi saat saldo drift'
            );
            $this->fail('Expected RuntimeException on balance mismatch drift.');
        } catch (RuntimeException $e) {
            $this->assertSame('Saldo akun tidak sesuai dengan jumlah ledger entry.', $e->getMessage());
        }

        // Ledger entry count must remain 1 (no partial entry committed)
        $this->assertSame(1, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
    }

    // =========================================================================
    // Helper Methods & Assertions
    // =========================================================================

    private function openTestAccount(CooperativeMember $member, int $openingBalance = 0, int $creditLimit = 0): MemberStoreAccount
    {
        return $this->ledgerService->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            creditLimit: $creditLimit,
            openingBalance: $openingBalance,
            openedBy: $this->adminUser,
            reason: 'Test account setup',
        ));
    }

    private function createPosPurchaseDirect(MemberStoreAccount $account, int $amount): MemberStoreLedgerEntry
    {
        $transaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-TX-DIR-'.uniqid(),
            'subtotal' => $amount,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'status' => 'COMPLETED',
            'sold_at' => now()->toDateString(),
        ]);

        return $this->ledgerService->postPurchase(
            account: $account,
            transaction: $transaction,
            amount: $amount,
            cashier: $this->cashierUser,
            delegate: null,
            purchaserName: 'Pembeli Direct',
        );
    }

    private function assertExactMathematicalBalanceMatchesLedger(MemberStoreAccount $account): void
    {
        $account->refresh();

        $ledgerSum = (int) MemberStoreLedgerEntry::query()
            ->where('account_id', $account->id)
            ->sum(DB::raw("CASE WHEN effect = 'credit' THEN amount ELSE -amount END"));

        $this->assertSame(
            $ledgerSum,
            (int) $account->balance,
            "Mathematical invariant violated: cached balance ({$account->balance}) does not match ledger sum ({$ledgerSum})."
        );

        $this->assertSame(
            $ledgerSum,
            $account->signedBalance(),
            'signedBalance() method does not match ledger sum.'
        );
    }
}
