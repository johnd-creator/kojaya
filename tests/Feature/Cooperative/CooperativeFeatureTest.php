<?php

namespace Tests\Feature\Cooperative;

use App\Enums\CooperativeShuPeriodStatus;
use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\User;
use App\Services\Cooperative\AnnualShuDistributionService;
use App\Services\Cooperative\DuesGenerationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CooperativeFeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_member_creation_always_uses_cooperative_head_office(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $branch = Organization::query()->create([
            'id' => fake()->uuid(),
            'code' => 'KOP-999',
            'name' => 'Cabang Test',
            'level' => 'L1',
            'type' => 'BRANCH',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cooperative.members.store'), [
            'organization_id' => $branch->id,
            'name' => 'Anggota Test',
            'email' => 'anggota@test.local',
            'phone' => '08123',
            'joined_at' => '2026-05-01',
            'status' => 'ACTIVE',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'anggota@test.local')->firstOrFail();

        $this->assertSame('KOP-001', $member->organization->code);
        $this->assertNotSame($branch->id, $member->organization_id);
    }

    public function test_member_creation_provisions_member_user_and_opening_balance(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $this->actingAs($user)->post(route('cooperative.members.store'), [
            'name' => 'Anggota Login',
            'email' => 'anggota-login@test.local',
            'phone' => '08123',
            'joined_at' => '2026-05-01',
            'status' => 'ACTIVE',
            'member_login_password' => 'password-anggota',
            'opening_saving_balance' => 125000,
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'anggota-login@test.local')->firstOrFail();
        $memberUser = User::query()->where('email', 'anggota-login@test.local')->firstOrFail();

        $this->assertSame($memberUser->id, $member->user_id);
        $this->assertTrue($memberUser->hasRole('Anggota'));
        $this->assertTrue(Hash::check('password-anggota', $memberUser->password));
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
            'credit' => 125000,
            'posted_at' => '2026-05-01 00:00:00',
        ]);
    }

    public function test_member_creation_links_existing_user_without_changing_password(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $existingUser = User::factory()->create([
            'email' => 'existing-member@test.local',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($admin)->post(route('cooperative.members.store'), [
            'name' => 'Existing Member',
            'email' => 'existing-member@test.local',
            'joined_at' => '2026-05-01',
            'status' => 'ACTIVE',
            'member_login_password' => 'new-password',
        ])->assertRedirect(route('cooperative.members.index'));

        $member = CooperativeMember::query()->where('email', 'existing-member@test.local')->firstOrFail();

        $this->assertSame($existingUser->id, $member->user_id);
        $this->assertTrue($existingUser->refresh()->hasRole('Anggota'));
        $this->assertTrue(Hash::check('old-password', $existingUser->password));
    }

    public function test_user_management_loads_and_searches_cooperative_member_links(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $memberUser = User::factory()->create(['email' => 'member-user@test.local']);
        $memberUser->assignRole('Anggota');
        $member = $this->member([
            'user_id' => $memberUser->id,
            'member_no' => 'KOP-SEARCH-001',
            'name' => 'Member Searchable',
            'email' => 'member-user@test.local',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index', ['search' => 'KOP-SEARCH-001']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/Index')
                ->where('users.data.0.cooperative_member.id', $member->id)
                ->where('users.data.0.cooperative_member.member_no', 'KOP-SEARCH-001')
            );
    }

    public function test_member_opening_balance_can_be_updated_without_duplicate_entries(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        $member = $this->member([
            'name' => 'Opening Balance',
            'email' => 'opening@test.local',
        ]);

        $this->actingAs($admin)->put(route('cooperative.members.update', $member), [
            'name' => 'Opening Balance',
            'email' => 'opening@test.local',
            'phone' => '08123',
            'identity_number' => '12345',
            'address' => 'Alamat',
            'joined_at' => '2026-05-01',
            'status' => 'ACTIVE',
            'notes' => 'Migrasi',
            'opening_saving_balance' => 150000,
        ])->assertRedirect(route('cooperative.members.index'));

        $this->actingAs($admin)->put(route('cooperative.members.update', $member), [
            'name' => 'Opening Balance',
            'email' => 'opening@test.local',
            'phone' => '08123',
            'identity_number' => '12345',
            'address' => 'Alamat',
            'joined_at' => '2026-05-01',
            'status' => 'ACTIVE',
            'notes' => 'Migrasi',
            'opening_saving_balance' => 200000,
        ])->assertRedirect(route('cooperative.members.index'));

        $this->assertSame(1, $member->ledgerEntries()->where('entry_type', 'OPENING_BALANCE')->count());
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'OPENING_BALANCE',
            'credit' => 200000,
        ]);
    }

    public function test_member_can_be_activated_and_resigned(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'PENDING', 'joined_at' => null]);

        $this->actingAs($user)->post(route('cooperative.members.activate', $member))->assertRedirect();
        $this->assertSame('ACTIVE', $member->refresh()->status);

        $this->actingAs($user)->post(route('cooperative.members.resign', $member))->assertRedirect();
        $this->assertSame('RESIGNED', $member->refresh()->status);
        $this->assertNotNull($member->resigned_at);
    }

    public function test_dues_generation_is_idempotent(): void
    {
        $member = $this->member(['status' => 'ACTIVE']);
        CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        $service = app(DuesGenerationService::class);

        $this->assertSame(1, $service->generateForPeriod('2026-05'));
        $this->assertSame(0, $service->generateForPeriod('2026-05'));
        $this->assertDatabaseCount('cooperative_dues_invoices', 1);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'period' => '2026-05',
            'status' => 'UNPAID',
        ]);
    }

    public function test_approved_payment_updates_invoice_and_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.payments.store'), [
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-01',
            'status' => 'APPROVED',
        ])->assertRedirect();

        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'SAVING_PAYMENT',
            'credit' => 50000,
        ]);
    }

    public function test_approving_same_cooperative_payment_twice_does_not_duplicate_invoice_or_ledger(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $approver = User::factory()->create();
        $approver->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-01',
            'status' => 'PENDING',
        ]);

        $this->actingAs($approver)->post(route('cooperative.payments.approve', $payment))->assertRedirect();
        $this->actingAs($approver)->post(route('cooperative.payments.approve', $payment))->assertRedirect();

        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
        $this->assertSame('APPROVED', $payment->refresh()->status);
        $this->assertSame(1, CooperativeLedgerEntry::query()->where('cooperative_payment_id', $payment->id)->count());
    }

    public function test_dues_batch_mark_paid_creates_approved_payments_and_ledger_entries(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $firstMember = $this->member(['status' => 'ACTIVE']);
        $secondMember = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $firstInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $firstMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $secondInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $secondMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 75000,
            'paid_amount' => 25000,
            'status' => 'PARTIAL',
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.mark-paid'), [
            'invoice_ids' => [$firstInvoice->id, $secondInvoice->id],
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
            'reference_no' => 'BATCH-001',
        ])->assertRedirect();

        $this->assertSame('PAID', $firstInvoice->refresh()->status);
        $this->assertSame('50000.00', $firstInvoice->paid_amount);
        $this->assertSame('PAID', $secondInvoice->refresh()->status);
        $this->assertSame('75000.00', $secondInvoice->paid_amount);
        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_dues_invoice_id' => $firstInvoice->id,
            'amount' => 50000,
            'status' => 'APPROVED',
        ]);
        $this->assertDatabaseHas('cooperative_payments', [
            'cooperative_dues_invoice_id' => $secondInvoice->id,
            'amount' => 50000,
            'status' => 'APPROVED',
        ]);
        $this->assertSame(2, $firstMember->ledgerEntries()->where('entry_type', 'SAVING_PAYMENT')->count() + $secondMember->ledgerEntries()->where('entry_type', 'SAVING_PAYMENT')->count());
    }

    public function test_dues_mark_paid_skips_already_paid_invoices_without_duplicate_payment(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $member = $this->member(['status' => 'ACTIVE']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-05',
            'amount' => 50000,
            'paid_amount' => 50000,
            'status' => 'PAID',
        ]);

        $this->actingAs($user)->post(route('cooperative.dues.mark-paid'), [
            'invoice_ids' => [$invoice->id],
            'payment_method' => 'CASH',
            'paid_at' => '2026-05-10',
        ])->assertRedirect();

        $this->assertSame(0, $invoice->payments()->count());
        $this->assertSame('PAID', $invoice->refresh()->status);
        $this->assertSame('50000.00', $invoice->paid_amount);
    }

    public function test_pos_transaction_reduces_stock_and_requires_active_member_for_credit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'PENDING']);
        $product = $this->product(['stock' => 5, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'payment_method' => 'MEMBER_CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('cooperative_member_id');

        $member->update(['status' => 'ACTIVE']);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'payment_method' => 'MEMBER_CREDIT',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(3, $product->refresh()->stock);
        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'cooperative_member_id' => $member->id,
            'entry_type' => 'POS_MEMBER_CREDIT',
            'debit' => 20000,
        ]);
    }

    public function test_pos_member_transaction_snapshots_profit_and_posts_points(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $product = $this->product(['stock' => 5, 'cost_price' => 6000, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POINT-001',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_transaction_items', [
            'pos_product_id' => $product->id,
            'cost_price' => 6000,
            'unit_profit' => 4000,
            'line_profit' => 8000,
        ]);
        $this->assertDatabaseHas('pos_transactions', [
            'client_reference' => 'POINT-001',
            'gross_profit' => 8000,
        ]);
        $this->assertDatabaseHas('pos_member_points', [
            'cooperative_member_id' => $member->id,
            'year' => 2026,
            'profit_amount' => 8000,
            'points' => 8000,
        ]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POINT-002',
            'payment_method' => 'CASH',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertSame(1, PosMemberPoint::query()->count());
    }

    public function test_pos_profit_snapshot_and_points_do_not_change_after_product_cost_changes(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $member = $this->member(['status' => 'ACTIVE']);
        $product = $this->product(['stock' => 5, 'cost_price' => 6000, 'sale_price' => 10000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SNAPSHOT-001',
            'payment_method' => 'TRANSFER',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $product->update(['cost_price' => 9000, 'sale_price' => 12000]);

        $this->assertDatabaseHas('pos_transaction_items', [
            'pos_product_id' => $product->id,
            'cost_price' => 6000,
            'unit_price' => 10000,
            'unit_profit' => 4000,
            'line_profit' => 4000,
        ]);
        $this->assertDatabaseHas('pos_member_points', [
            'cooperative_member_id' => $member->id,
            'profit_amount' => 4000,
            'points' => 4000,
        ]);
    }

    public function test_annual_shu_score_rewards_long_membership_and_complete_mandatory_dues(): void
    {
        $longMember = $this->member([
            'member_no' => 'KOP-2026-00001',
            'status' => 'ACTIVE',
            'joined_at' => '2026-01-01',
        ]);
        $newMember = $this->member([
            'member_no' => 'KOP-2026-00002',
            'status' => 'ACTIVE',
            'joined_at' => '2026-07-01',
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);

        foreach (range(1, 12) as $month) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $longMember->id,
                'cooperative_contribution_type_id' => $type->id,
                'period' => '2026-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'amount' => 50000,
                'paid_amount' => 50000,
                'status' => 'PAID',
            ]);
        }

        foreach ([7, 8, 9] as $month) {
            CooperativeDuesInvoice::query()->create([
                'cooperative_member_id' => $newMember->id,
                'cooperative_contribution_type_id' => $type->id,
                'period' => '2026-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'amount' => 50000,
                'paid_amount' => 50000,
                'status' => 'PAID',
            ]);
        }

        $preview = app(AnnualShuDistributionService::class)->preview(2026, 150000);
        $allocations = collect($preview['allocations'])->keyBy(fn (array $allocation) => $allocation['member']->id);

        $this->assertGreaterThan($allocations[$newMember->id]['shu_score'], $allocations[$longMember->id]['shu_score']);
        $this->assertGreaterThan($allocations[$newMember->id]['cooperative_shu_amount'], $allocations[$longMember->id]['cooperative_shu_amount']);
    }

    public function test_annual_pos_shu_is_allocated_by_member_profit_points_and_close_is_locked(): void
    {
        Carbon::setTestNow('2026-05-15 10:00:00');
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');
        $firstMember = $this->member(['member_no' => 'KOP-2026-00003', 'status' => 'ACTIVE']);
        $secondMember = $this->member(['member_no' => 'KOP-2026-00004', 'status' => 'ACTIVE']);
        $firstProduct = $this->product(['stock' => 5, 'cost_price' => 5000, 'sale_price' => 10000]);
        $secondProduct = $this->product(['stock' => 5, 'cost_price' => 5000, 'sale_price' => 20000]);

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS-SHU-001',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $firstMember->id,
            'items' => [
                ['pos_product_id' => $firstProduct->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS-SHU-002',
            'payment_method' => 'CASH',
            'cooperative_member_id' => $secondMember->id,
            'items' => [
                ['pos_product_id' => $secondProduct->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $service = app(AnnualShuDistributionService::class);
        $preview = $service->preview(2026);
        $allocations = collect($preview['allocations'])->keyBy(fn (array $allocation) => $allocation['member']->id);

        $this->assertSame(40000, $preview['total_pos_points']);
        $this->assertSame(40000.0, $preview['pos_profit_pool']);
        $this->assertSame(10000.0, $allocations[$firstMember->id]['pos_shu_amount']);
        $this->assertSame(30000.0, $allocations[$secondMember->id]['pos_shu_amount']);

        $period = $service->close(2026, 100000, null, $user);

        $this->assertSame(CooperativeShuPeriodStatus::Closed, $period->status);
        $this->assertDatabaseHas('cooperative_shu_periods', [
            'year' => 2026,
            'status' => 'CLOSED',
            'total_pos_points' => 40000,
        ]);
        $this->assertSame(2, CooperativeShuPeriod::query()->where('year', 2026)->firstOrFail()->allocations()->count());
        $this->expectException(ValidationException::class);

        $service->close(2026, 100000, null, $user);
    }

    public function test_pos_category_and_product_can_be_managed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $this->actingAs($user)->post(route('cooperative.pos-categories.store'), [
            'name' => 'Minuman',
            'slug' => 'minuman',
            'is_active' => true,
        ])->assertRedirect();

        $category = PosCategory::query()->where('slug', 'minuman')->firstOrFail();

        $this->actingAs($user)->post(route('cooperative.pos-products.store'), [
            'pos_category_id' => $category->id,
            'sku' => 'DRINK-001',
            'barcode' => '899000000001',
            'name' => 'Air Mineral',
            'cost_price' => 2500,
            'sale_price' => 4000,
            'stock' => 20,
            'minimum_stock' => 5,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_products', [
            'sku' => 'DRINK-001',
            'name' => 'Air Mineral',
            'stock' => 20,
        ]);

        $product = PosProduct::query()->where('sku', 'DRINK-001')->firstOrFail();

        $this->actingAs($user)->put(route('cooperative.pos-products.update', $product), [
            'pos_category_id' => $category->id,
            'sku' => 'DRINK-001',
            'barcode' => '899000000001',
            'name' => 'Air Mineral Botol',
            'cost_price' => 2600,
            'sale_price' => 4500,
            'minimum_stock' => 8,
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('pos_products', [
            'id' => $product->id,
            'name' => 'Air Mineral Botol',
            'minimum_stock' => 8,
        ]);
    }

    public function test_pos_stock_adjustment_records_movement_and_rejects_negative_stock(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('System Admin');
        $product = $this->product(['stock' => 5]);

        $this->actingAs($user)->post(route('cooperative.pos-products.adjust-stock', $product), [
            'movement_type' => 'ADJUSTMENT_IN',
            'quantity' => 7,
            'notes' => 'Restock',
        ])->assertRedirect();

        $this->assertSame(12, $product->refresh()->stock);
        $this->assertDatabaseHas('pos_stock_movements', [
            'pos_product_id' => $product->id,
            'movement_type' => 'ADJUSTMENT_IN',
            'quantity' => 7,
            'stock_before' => 5,
            'stock_after' => 12,
        ]);

        $this->actingAs($user)->post(route('cooperative.pos-products.adjust-stock', $product), [
            'movement_type' => 'ADJUSTMENT_OUT',
            'quantity' => 20,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(12, $product->refresh()->stock);
        $this->assertSame(1, PosStockMovement::query()->where('pos_product_id', $product->id)->count());
    }

    public function test_api_requires_sanctum_and_cooperative_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->getJson('/api/v1/members')->assertUnauthorized();

        $plainUser = User::factory()->create();
        Sanctum::actingAs($plainUser, ['cooperative:read']);
        $this->getJson('/api/v1/members')->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('System Admin');
        Sanctum::actingAs($admin, ['cooperative:read']);
        $this->getJson('/api/v1/members')->assertOk();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function member(array $attributes = []): CooperativeMember
    {
        $organization = Organization::query()->firstOrCreate(
            ['code' => 'KOP-001'],
            [
                'id' => fake()->uuid(),
                'name' => 'Koperasi Utama',
                'level' => 'L0',
                'type' => 'HEAD_OFFICE',
                'is_active' => true,
            ],
        );

        return CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'member_no' => 'KOP-2026-'.fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'status' => 'ACTIVE',
            'joined_at' => '2026-05-01',
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(array $attributes = []): PosProduct
    {
        $category = PosCategory::query()->create([
            'name' => 'Sembako',
            'slug' => 'sembako-'.fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
        ]);

        return PosProduct::query()->create([
            'pos_category_id' => $category->id,
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->word(),
            'sale_price' => 10000,
            'stock' => 10,
            'minimum_stock' => 1,
            'is_active' => true,
            ...$attributes,
        ]);
    }
}
