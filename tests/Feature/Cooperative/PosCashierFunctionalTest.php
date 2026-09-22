<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\Organization;
use App\Models\PosAuditLog;
use App\Models\PosCashierShift;
use App\Models\PosCategory;
use App\Models\PosInventoryLocation;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Services\Cooperative\PosCashierShiftService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosCashierFunctionalTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_pos001_shift_opening_is_cashier_unique_and_audited(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $locationA = $this->location('POS001-A');
        $locationB = $this->location('POS001-B');

        $this->actingAs($cashier)
            ->post(route('cooperative.pos.shifts.open'), [
                'opening_cash' => 125000,
                'pos_inventory_location_id' => $locationA->id,
            ])
            ->assertRedirect();

        $shift = PosCashierShift::query()->firstOrFail();
        $auditCount = PosAuditLog::query()
            ->where('event', 'shift.opened')
            ->where('entity_id', $shift->id)
            ->count();

        $this->assertSame(125000.0, (float) $shift->opening_cash);
        $this->assertSame(PosCashierShift::STATUS_OPEN, $shift->status);
        $this->assertSame($cashier->id, $shift->cashier_id);
        $this->assertSame(1, $auditCount);

        $this->actingAs($cashier)
            ->post(route('cooperative.pos.shifts.open'), [
                'opening_cash' => 90000,
                'pos_inventory_location_id' => $locationB->id,
            ])
            ->assertSessionHasErrors('shift');

        $this->assertSame(1, PosCashierShift::query()->count());
        $this->assertSame($auditCount, PosAuditLog::query()->where('event', 'shift.opened')->count());

        $unauthorized = $this->user($organization);
        $this->actingAs($unauthorized)
            ->post(route('cooperative.pos.shifts.open'), ['opening_cash' => 1000])
            ->assertForbidden();
        $this->assertSame(1, PosCashierShift::query()->count());
    }

    public function test_pos002_canonical_web_and_api_catalogs_exclude_inactive_products(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $category = PosCategory::factory()->create(['organization_id' => $organization->id]);

        $active = $this->product($organization, $category, [
            'name' => 'POS002 Active',
            'is_active' => true,
            'is_discontinued' => false,
        ]);
        $inactive = $this->product($organization, $category, [
            'name' => 'POS002 Inactive',
            'is_active' => false,
        ]);
        $foreign = $this->product($otherOrganization, null, [
            'name' => 'POS002 Foreign',
            'is_active' => true,
        ]);

        $this->actingAs($cashier)
            ->get(route('cooperative.pos.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('products', fn ($products): bool => collect($products)->pluck('id')->contains($active->id)
                    && ! collect($products)->pluck('id')->contains($inactive->id)
                    && ! collect($products)->pluck('id')->contains($foreign->id))
            );

        Sanctum::actingAs($cashier, ['pos:read']);
        $this->getJson('/api/v1/pos/products?search=POS002')
            ->assertOk()
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonMissing(['id' => $inactive->id])
            ->assertJsonMissing(['id' => $foreign->id]);
    }

    public function test_pos003_cash_boundaries_are_authoritative_and_atomic(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $product = $this->product($organization, null, [
            'sale_price' => 5000,
            'cost_price' => 3000,
            'stock' => 10,
        ]);

        $this->actingAs($cashier)
            ->postJson(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'POS003-ZERO-TENDER',
                'payment_method' => 'CASH',
                'cash_received' => 0,
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cash_received');

        $this->assertSame(10, (int) $product->refresh()->stock);
        $this->assertSame(0, PosTransaction::query()->count());
        $this->assertSame(0, PosTransactionItem::query()->count());
        $this->assertSame(0, PosPayment::query()->count());

        $this->actingAs($cashier)
            ->postJson(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'POS003-EXACT-TENDER',
                'payment_method' => 'CASH',
                'cash_received' => 5000,
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertCreated();

        $exact = PosTransaction::query()->where('client_reference', 'POS003-EXACT-TENDER')->firstOrFail();
        $this->assertSame(5000.0, (float) $exact->total_amount);
        $this->assertSame(5000.0, (float) $exact->cash_received);
        $this->assertSame(0.0, (float) $exact->cash_change);
        $this->assertSame(1, $exact->items()->count());
        $this->assertSame(1, $exact->payments()->count());
        $this->assertSame(9, (int) $product->refresh()->stock);

        $this->actingAs($cashier)
            ->postJson(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'POS003-EXCESS-TENDER',
                'payment_method' => 'CASH',
                'cash_received' => 7000,
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertCreated();

        $excess = PosTransaction::query()->where('client_reference', 'POS003-EXCESS-TENDER')->firstOrFail();
        $this->assertSame(2000.0, (float) $excess->cash_change);
        $this->assertSame(8, (int) $product->refresh()->stock);
    }

    public function test_pos005_receipt_and_pdf_are_read_only_without_reprint_audit(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $product = $this->product($organization, null, ['sale_price' => 7500, 'stock' => 5]);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS005-RECEIPT',
            'payment_method' => 'CASH',
            'cash_received' => 10000,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $transaction = PosTransaction::query()
            ->where('client_reference', 'POS005-RECEIPT')
            ->firstOrFail();
        $beforeAuditCount = PosAuditLog::query()
            ->where('entity_type', PosTransaction::class)
            ->where('entity_id', $transaction->id)
            ->count();

        $this->actingAs($cashier)
            ->get(route('cooperative.pos.transactions.receipt', $transaction->id))
            ->assertOk()
            ->assertSeeText($transaction->transaction_no);

        $this->actingAs($cashier)
            ->get(route('cooperative.pos.transactions.receipt.pdf', $transaction->id))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="receipt-'.$transaction->transaction_no.'.html"')
            ->assertSeeText($transaction->transaction_no);

        $this->assertSame(1, PosTransaction::query()->count());
        $this->assertSame(1, PosTransactionItem::query()->count());
        $this->assertSame(1, PosPayment::query()->count());
        $this->assertSame(4, (int) $product->refresh()->stock);
        $this->assertSame($beforeAuditCount, PosAuditLog::query()
            ->where('entity_type', PosTransaction::class)
            ->where('entity_id', $transaction->id)
            ->count());
    }

    public function test_pos006_void_after_shift_close_is_controlled_until_daily_closing(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $supervisor = $this->user($organization, ['access_cooperative_pos', 'approve_pos_void']);
        $product = $this->product($organization, null, ['sale_price' => 5000, 'cost_price' => 2500, 'stock' => 10]);
        $shift = app(PosCashierShiftService::class)->openShift($cashier, 100000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS006-CLOSED-SHIFT',
            'pos_cashier_shift_id' => $shift->id,
            'payment_method' => 'CASH',
            'cash_received' => 5000,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $transaction = PosTransaction::query()->where('client_reference', 'POS006-CLOSED-SHIFT')->firstOrFail();
        app(PosCashierShiftService::class)->closeShift($shift, 105000);

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Void sebelum daily closing',
        ])->assertRedirect();
        $voidRequest = $transaction->voidRequests()->firstOrFail();

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        $this->assertSame('CLOSED', $shift->refresh()->status);
        $this->assertSame('VOIDED', $transaction->refresh()->status);
        $this->assertSame(10, (int) $product->refresh()->stock);
        $this->assertSame(1, PosStockMovement::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $transaction->id)
            ->where('movement_type', 'VOID')
            ->count());
        $this->assertSame(1, CooperativeLedgerEntry::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $transaction->id)
            ->where('entry_type', 'POS_SALE_REVERSAL')
            ->count());
    }

    public function test_pos007_cumulative_return_quantity_cannot_exceed_original_sale(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $product = $this->product($organization, null, ['sale_price' => 5000, 'stock' => 10]);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'POS007-CUMULATIVE',
            'payment_method' => 'CASH',
            'cash_received' => 25000,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 5]],
        ])->assertCreated();
        $transaction = PosTransaction::query()->where('client_reference', 'POS007-CUMULATIVE')->firstOrFail();
        $item = $transaction->items()->firstOrFail();

        $this->actingAs($cashier)->post(route('cooperative.pos.returns.store', $transaction->id), [
            'reason' => 'Retur sebagian pertama',
            'items' => [['pos_transaction_item_id' => $item->id, 'quantity' => 3]],
        ])->assertRedirect();

        $returnCount = PosReturn::query()->count();
        $returnStock = (int) $product->refresh()->stock;
        $returnJournalCount = CooperativeLedgerEntry::query()
            ->where('source_type', PosReturn::class)
            ->count();

        $this->actingAs($cashier)->post(route('cooperative.pos.returns.store', $transaction->id), [
            'reason' => 'Retur kedua melebihi sisa',
            'items' => [['pos_transaction_item_id' => $item->id, 'quantity' => 3]],
        ])->assertSessionHasErrors('items');

        $this->assertSame($returnCount, PosReturn::query()->count());
        $this->assertSame($returnStock, (int) $product->refresh()->stock);
        $this->assertSame($returnJournalCount, CooperativeLedgerEntry::query()
            ->where('source_type', PosReturn::class)
            ->count());
        $this->assertSame(3, (int) PosReturnItem::query()
            ->where('pos_transaction_item_id', $item->id)
            ->sum('quantity'));
    }

    public function test_pos008_closed_shift_checkout_is_rejected_without_mutation(): void
    {
        $organization = Organization::factory()->create();
        $cashier = $this->user($organization, ['access_cooperative_pos']);
        $product = $this->product($organization, null, ['sale_price' => 5000, 'stock' => 10]);
        $shift = app(PosCashierShiftService::class)->openShift($cashier, 100000);
        app(PosCashierShiftService::class)->closeShift($shift, 100000);

        $this->actingAs($cashier)
            ->postJson(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'POS008-CLOSED-SHIFT',
                'pos_cashier_shift_id' => $shift->id,
                'payment_method' => 'CASH',
                'cash_received' => 5000,
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pos_cashier_shift_id');

        $this->assertSame(0, PosTransaction::query()->count());
        $this->assertSame(0, PosPayment::query()->count());
        $this->assertSame(10, (int) $product->refresh()->stock);
        $this->assertSame(PosCashierShift::STATUS_CLOSED, $shift->refresh()->status);
        $this->assertSame(0, CooperativeLedgerEntry::query()->count());
    }

    public function test_pos008_checkout_permission_boundary_is_fail_closed(): void
    {
        $organization = Organization::factory()->create();
        $unauthorized = $this->user($organization);
        $product = $this->product($organization, null, ['sale_price' => 5000, 'stock' => 10]);

        $this->actingAs($unauthorized)
            ->postJson(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'POS008-UNAUTHORIZED',
                'payment_method' => 'CASH',
                'cash_received' => 5000,
                'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            ])
            ->assertForbidden();

        $this->assertSame(0, PosTransaction::query()->count());
        $this->assertSame(10, (int) $product->refresh()->stock);
    }

    public function test_pos009_daily_closing_duplicate_is_rejected_without_second_journal(): void
    {
        $organization = Organization::factory()->create();
        $manager = $this->user($organization, ['access_cooperative_pos', 'view_pos_reports', 'manage_pos_products']);
        $product = $this->product($organization, null, ['sale_price' => 5000, 'stock' => 10]);
        $shift = app(PosCashierShiftService::class)->openShift($manager, 100000);

        app(PosTransactionService::class)->create([
            'pos_cashier_shift_id' => $shift->id,
            'client_reference' => 'POS009-CLOSING',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $manager);
        app(PosCashierShiftService::class)->closeShift($shift, 105000);

        $date = now()->toDateString();
        $this->actingAs($manager)
            ->post(route('cooperative.pos.closings.close'), ['date' => $date])
            ->assertRedirect();

        $closing = \App\Models\PosDailyClosing::query()->firstOrFail();
        $journalCount = CooperativeLedgerEntry::query()
            ->where('source_type', \App\Models\PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->count();

        $this->actingAs($manager)
            ->post(route('cooperative.pos.closings.close'), ['date' => $date])
            ->assertSessionHasErrors('date');

        $this->assertSame(1, \App\Models\PosDailyClosing::query()->count());
        $this->assertSame($journalCount, CooperativeLedgerEntry::query()
            ->where('source_type', \App\Models\PosDailyClosing::class)
            ->where('source_id', $closing->id)
            ->count());
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function user(Organization $organization, array $permissions = []): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    private function location(string $code): PosInventoryLocation
    {
        return PosInventoryLocation::query()->create([
            'code' => $code,
            'name' => $code,
            'location_type' => 'STORE',
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(Organization $organization, ?PosCategory $category, array $attributes = []): PosProduct
    {
        return PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'pos_category_id' => $category?->id,
            ...$attributes,
        ]);
    }
}
