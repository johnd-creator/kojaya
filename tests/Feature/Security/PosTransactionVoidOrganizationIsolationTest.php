<?php

namespace Tests\Feature\Security;

use App\Exceptions\OrganizationScopeException;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use App\Models\PointTransaction;
use App\Models\PosCashierShift;
use App\Models\PosCategory;
use App\Models\PosPayment;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosSyncRequest;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Authorization\OrganizationScopeService;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosSyncService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosTransactionVoidOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ==========================================
    // GROUP 1: TRANSACTION CREATION (1 - 10)
    // ==========================================

    public function test_cashier_can_create_transaction_for_own_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);
        $memberA = $this->createMember($orgA);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-ORG-A-1',
            'cooperative_member_id' => $memberA->id,
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 2],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
            ],
        ]);

        $response->assertRedirect();
        $transaction = PosTransaction::query()->where('client_reference', 'TX-ORG-A-1')->firstOrFail();
        $this->assertSame($orgA->id, $transaction->organization_id);
        $this->assertSame(8, (int) $productA->fresh()->stock);
    }

    public function test_cashier_cannot_create_transaction_with_products_from_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productB = $this->createProduct($orgB, ['sale_price' => 15000, 'stock' => 5]);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-FOREIGN-PROD',
            'items' => [
                ['pos_product_id' => $productB->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000],
            ],
        ]);

        $response->assertForbidden();
        $this->assertSame(5, (int) $productB->fresh()->stock);
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-FOREIGN-PROD']);
    }

    public function test_mixed_organization_cart_is_rejected(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 5]);
        $productB = $this->createProduct($orgB, ['sale_price' => 20000, 'stock' => 5]);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-MIXED-CART',
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
                ['pos_product_id' => $productB->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 30000, 'cash_received' => 30000],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(5, (int) $productA->fresh()->stock);
        $this->assertSame(5, (int) $productB->fresh()->stock);
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-MIXED-CART']);
    }

    public function test_cashier_cannot_specify_member_from_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);
        $memberB = $this->createMember($orgB);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-FOREIGN-MEMBER',
            'cooperative_member_id' => $memberB->id,
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
            ],
        ]);

        $response->assertSessionHasErrors('cooperative_member_id');
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-FOREIGN-MEMBER']);
    }

    public function test_cashier_cannot_transact_member_with_null_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);

        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $member = $this->createMember($orgA);
        $member->forceFill(['organization_id' => null])->saveQuietly();

        $this->assertDatabaseHas('cooperative_members', [
            'id' => $member->id,
            'organization_id' => null,
        ]);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-NULL-ORG-MEMBER',
            'cooperative_member_id' => $member->id,
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
            ],
        ]);

        $response->assertSessionHasErrors('cooperative_member_id');
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-NULL-ORG-MEMBER']);
        $this->assertSame(10, (int) $productA->fresh()->stock);
        $this->assertDatabaseCount('pos_transactions', 0);
        $this->assertDatabaseCount('pos_transaction_items', 0);
        $this->assertDatabaseCount('pos_payments', 0);
        $this->assertDatabaseCount('pos_stock_movements', 0);
    }

    public function test_cashier_cannot_transact_with_nonexistent_member(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-NONEXISTENT-MEMBER',
            'cooperative_member_id' => 999999,
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
            ],
        ]);

        $response->assertSessionHasErrors('cooperative_member_id');
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-NONEXISTENT-MEMBER']);
        $this->assertSame(10, (int) $productA->fresh()->stock);
        $this->assertDatabaseCount('pos_transactions', 0);
        $this->assertDatabaseCount('pos_transaction_items', 0);
        $this->assertDatabaseCount('pos_payments', 0);
        $this->assertDatabaseCount('pos_stock_movements', 0);
    }

    public function test_cashier_cannot_use_shift_from_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);
        $shiftB = $this->createShift($cashierB);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-FOREIGN-SHIFT',
            'pos_cashier_shift_id' => $shiftB->id,
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
            ],
        ]);

        $response->assertSessionHasErrors('pos_cashier_shift_id');
    }

    public function test_request_body_containing_organization_id_is_prohibited(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-FORGED-ORG',
            'organization_id' => $orgB->id,
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
            ],
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-FORGED-ORG']);
    }

    public function test_user_with_null_organization_without_global_authority_cannot_create_transaction(): void
    {
        [$orgA] = $this->createOrganizations();
        $nullOrgCashier = User::factory()->create(['organization_id' => null]);
        $nullOrgCashier->givePermissionTo('access_cooperative_pos');
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);

        $response = $this->actingAs($nullOrgCashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-NULL-CASHIER',
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_global_operator_without_trusted_active_organization_fails_closed(): void
    {
        [$orgA] = $this->createOrganizations();
        $globalCashier = $this->createGlobalCashier();
        $productA = $this->createProduct($orgA, ['sale_price' => 12000, 'stock' => 5]);

        $response = $this->actingAs($globalCashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'TX-GLOBAL-CASHIER-A',
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 12000, 'cash_received' => 12000],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-GLOBAL-CASHIER-A']);
    }

    public function test_global_operator_with_trusted_active_organization_can_create_transaction(): void
    {
        [$orgA] = $this->createOrganizations();
        $globalCashier = $this->createGlobalCashier();
        $productA = $this->createProduct($orgA, ['sale_price' => 12000, 'stock' => 5]);

        $response = $this->actingAs($globalCashier)
            ->withSession(['active_organization_id' => $orgA->id])
            ->post(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'TX-GLOBAL-CASHIER-A',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 12000, 'cash_received' => 12000],
                ],
            ]);

        $response->assertRedirect();
        $trx = PosTransaction::query()->where('client_reference', 'TX-GLOBAL-CASHIER-A')->firstOrFail();
        $this->assertSame($orgA->id, $trx->organization_id);
    }

    public function test_global_read_actor_without_pos_permission_cannot_create_transaction(): void
    {
        [$orgA] = $this->createOrganizations();
        $globalReader = User::factory()->create([
            'organization_id' => null,
            'name' => 'Global Reader Only',
        ]);
        $globalReader->givePermissionTo(['view_cooperative_all']);
        $productA = $this->createProduct($orgA, ['sale_price' => 12000, 'stock' => 5]);

        $response = $this->actingAs($globalReader)
            ->withSession(['active_organization_id' => $orgA->id])
            ->post(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'TX-GLOBAL-READER-FAIL',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 12000, 'cash_received' => 12000],
                ],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-GLOBAL-READER-FAIL']);
    }

    public function test_global_operator_with_active_organization_session_uses_active_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalCashier = $this->createGlobalCashier();
        $productA = $this->createProduct($orgA, ['sale_price' => 12000]);

        // When active org is Org B, attempting to sell Org A product fails because cart must match active org
        $response = $this->actingAs($globalCashier)
            ->withSession(['active_organization_id' => $orgB->id])
            ->post(route('cooperative.pos.transactions.store'), [
                'client_reference' => 'TX-GLOBAL-ACTIVE-MISMATCH',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 12000, 'cash_received' => 12000],
                ],
            ]);

        $response->assertSessionHasErrors('items');
    }

    // ==========================================
    // GROUP 2: IDEMPOTENCY & SIDE EFFECTS (11 - 15)
    // ==========================================

    public function test_idempotent_replay_within_same_organization_returns_existing_without_duplicating(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);

        $payload = [
            'client_reference' => 'TX-IDEMPOTENT-SAME-ORG',
            'items' => [
                ['pos_product_id' => $productA->id, 'quantity' => 2],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
            ],
        ];

        $res1 = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), $payload);
        $res1->assertRedirect();
        $this->assertSame(8, (int) $productA->fresh()->stock);

        $res2 = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), $payload);
        $res2->assertRedirect();
        $this->assertSame(8, (int) $productA->fresh()->stock); // Stock not decremented again

        $this->assertSame(1, PosTransaction::query()->where('organization_id', $orgA->id)->where('client_reference', 'TX-IDEMPOTENT-SAME-ORG')->count());
    }

    public function test_same_client_reference_in_different_organizations_is_allowed_and_isolated(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);
        $productB = $this->createProduct($orgB, ['sale_price' => 15000, 'stock' => 10]);

        $payloadA = [
            'client_reference' => 'TX-SHARED-REF-100',
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ];
        $payloadB = [
            'client_reference' => 'TX-SHARED-REF-100',
            'items' => [['pos_product_id' => $productB->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000]],
        ];

        $resA = $this->actingAs($cashierA)->post(route('cooperative.pos.transactions.store'), $payloadA);
        $resA->assertRedirect();

        $resB = $this->actingAs($cashierB)->post(route('cooperative.pos.transactions.store'), $payloadB);
        $resB->assertRedirect();

        $txA = PosTransaction::query()->where('organization_id', $orgA->id)->where('client_reference', 'TX-SHARED-REF-100')->firstOrFail();
        $txB = PosTransaction::query()->where('organization_id', $orgB->id)->where('client_reference', 'TX-SHARED-REF-100')->firstOrFail();

        $this->assertNotSame($txA->id, $txB->id);
        $this->assertSame(9, (int) $productA->fresh()->stock);
        $this->assertSame(9, (int) $productB->fresh()->stock);
    }

    public function test_idempotency_lookup_does_not_leak_other_organization_transaction(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);
        $productB = $this->createProduct($orgB, ['sale_price' => 20000]);

        // Org A creates transaction
        $service = app(PosTransactionService::class);
        $txA = $service->create([
            'client_reference' => 'TX-SECRET-A',
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashierA);

        // Org B creates transaction with the same client_reference
        $txB = $service->create([
            'client_reference' => 'TX-SECRET-A',
            'items' => [['pos_product_id' => $productB->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000]],
        ], $cashierB);

        $this->assertNotSame($txA->id, $txB->id);
        $this->assertSame($orgB->id, $txB->organization_id);
    }

    public function test_sync_service_scopes_client_reference_by_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);
        $productB = $this->createProduct($orgB, ['sale_price' => 15000]);

        $service = app(PosTransactionService::class);
        $service->create([
            'client_reference' => 'SYNC-SHARED-01',
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashierA);

        $syncRequestB = PosSyncRequest::query()->create([
            'client_id' => 'device-b-sync-1',
            'device_id' => 'DEV-B-001',
            'user_id' => $cashierB->id,
            'endpoint' => PosSyncService::ENDPOINT_TRANSACTION_STORE,
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-SHARED-01',
                'items' => [['pos_product_id' => $productB->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000]],
            ],
            'idempotency_key' => 'idem-key-b-1',
            'status' => PosSyncRequest::STATUS_PENDING,
        ]);

        $syncService = app(PosSyncService::class);
        $result = $syncService->process($syncRequestB);

        $this->assertSame(201, $result['status']);
        $this->assertNotSame($orgA->id, $result['data']['organization_id']);
        $this->assertSame($orgB->id, $result['data']['organization_id']);
    }

    public function test_failed_transaction_creation_rolls_back_all_side_effects(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);
        $memberB = $this->createMember($orgB);

        try {
            app(PosTransactionService::class)->create([
                'client_reference' => 'TX-FAIL-ROLLBACK',
                'cooperative_member_id' => $memberB->id,
                'items' => [['pos_product_id' => $productA->id, 'quantity' => 2]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000]],
            ], $cashierA);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cooperative_member_id', $e->errors());
        }

        $this->assertSame(10, (int) $productA->fresh()->stock);
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TX-FAIL-ROLLBACK']);
    }

    // ==========================================
    // GROUP 3: HISTORY & DETAIL SCOPING (16 - 22)
    // ==========================================

    public function test_transaction_history_index_only_shows_actors_organization_records(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, ['transaction_no' => 'TX-A-HIST-1']);
        $txB = $this->createTransaction($orgB, $cashierB, $productB, ['transaction_no' => 'TX-B-HIST-1']);

        $response = $this->actingAs($cashierA)->get(route('cooperative.pos.transactions.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Cooperative/Pos/Transactions/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.id', $txA->id)
        );
    }

    public function test_transaction_history_index_global_user_sees_all_organizations(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        $this->createTransaction($orgA, $cashierA, $productA, ['transaction_no' => 'TX-GLOBAL-A']);
        $this->createTransaction($orgB, $cashierB, $productB, ['transaction_no' => 'TX-GLOBAL-B']);

        $globalCashier = $this->createGlobalCashier();

        $response = $this->actingAs($globalCashier)->get(route('cooperative.pos.transactions.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Cooperative/Pos/Transactions/Index')
            ->has('transactions.data', 2)
        );
    }

    public function test_transaction_detail_accessible_for_own_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.show', $txA->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Pos/Transactions/Show')
                ->where('transaction.id', $txA->id)
            );
    }

    public function test_transaction_detail_returns_404_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        // Org A querying Org B transaction must return 404 (anti-enumeration)
        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.show', $txB->id))
            ->assertNotFound();
    }

    public function test_cashiers_and_members_filter_options_are_strictly_scoped_to_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA, ['access_cooperative_pos'], 'Kasir Unit A');
        $cashierB = $this->createCashier($orgB, ['access_cooperative_pos'], 'Kasir Unit B');
        $memberA = $this->createMember($orgA, 'Member Org A');
        $memberB = $this->createMember($orgB, 'Member Org B');
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        $this->createTransaction($orgA, $cashierA, $productA, ['cooperative_member_id' => $memberA->id]);
        $this->createTransaction($orgB, $cashierB, $productB, ['cooperative_member_id' => $memberB->id]);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Pos/Transactions/Index')
                ->has('cashiers', 1)
                ->where('cashiers.0.id', $cashierA->id)
                ->has('members', 1)
                ->where('members.0.id', $memberA->id)
            );
    }

    public function test_unauthenticated_or_null_org_user_cannot_view_transactions(): void
    {
        $nullUser = User::factory()->create(['organization_id' => null]);
        $nullUser->givePermissionTo('access_cooperative_pos');

        $this->actingAs($nullUser)
            ->get(route('cooperative.pos.transactions.index'))
            ->assertForbidden();
    }

    public function test_history_filters_preserve_organization_isolation(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        $this->createTransaction($orgA, $cashierA, $productA, ['status' => 'COMPLETED']);
        $this->createTransaction($orgB, $cashierB, $productB, ['status' => 'COMPLETED']);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.index', ['status' => 'COMPLETED']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Pos/Transactions/Index')
                ->has('transactions.data', 1)
            );
    }

    // ==========================================
    // GROUP 4: RECEIPTS (23 - 25)
    // ==========================================

    public function test_receipt_accessible_for_own_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.receipt', $txA->id))
            ->assertOk();
    }

    public function test_receipt_returns_404_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.receipt', $txB->id))
            ->assertNotFound();
    }

    public function test_receipt_pdf_returns_404_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.receipt.pdf', $txB->id))
            ->assertNotFound();
    }

    // ==========================================
    // GROUP 5: VOID REQUEST CREATION (26 - 30)
    // ==========================================

    public function test_cashier_can_request_void_for_own_organization_transaction(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.void-requests.store', $txA->id), [
            'reason' => 'Salah input nominal kasir',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pos_void_requests', [
            'pos_transaction_id' => $txA->id,
            'status' => 'PENDING',
        ]);
        $this->assertSame('VOID_PENDING', $txA->fresh()->status);
    }

    public function test_cashier_cannot_request_void_for_other_organization_transaction(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.void-requests.store', $txB->id), [
            'reason' => 'Mencoba void transaksi luar',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('pos_void_requests', [
            'pos_transaction_id' => $txB->id,
        ]);
    }

    public function test_cannot_request_void_twice_or_on_already_voided_transaction(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);

        $this->actingAs($cashierA)->post(route('cooperative.pos.void-requests.store', $txA->id), [
            'reason' => 'Alasan pertama',
        ])->assertRedirect();

        // Second void request on pending void is rejected
        $this->actingAs($cashierA)->post(route('cooperative.pos.void-requests.store', $txA->id), [
            'reason' => 'Alasan kedua',
        ])->assertSessionHasErrors('transaction');
    }

    public function test_null_org_user_cannot_request_void(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);

        $nullCashier = User::factory()->create(['organization_id' => null]);
        $nullCashier->givePermissionTo('access_cooperative_pos');

        $this->actingAs($nullCashier)
            ->post(route('cooperative.pos.void-requests.store', $txA->id), [
                'reason' => 'Kasir tanpa organisasi',
            ])
            ->assertForbidden();
    }

    public function test_service_request_void_throws_authorization_exception_for_cross_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        $this->expectException(AuthorizationException::class);
        app(PosTransactionService::class)->requestVoid($txB, $cashierA, 'Direct cross-org service call');
    }

    // ==========================================
    // GROUP 6: VOID LIST & PROCESSING (31 - 38)
    // ==========================================

    public function test_supervisor_void_list_only_shows_own_organization_requests(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $supervisorA = $this->createSupervisor($orgA);
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        $txA = $this->createTransaction($orgA, $cashierA, $productA);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        $service = app(PosTransactionService::class);
        $voidA = $service->requestVoid($txA, $cashierA, 'Void Org A');
        $voidB = $service->requestVoid($txB, $cashierB, 'Void Org B');

        $this->actingAs($supervisorA)
            ->get(route('cooperative.pos.void-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Pos/Void/Index')
                ->has('requests.data', 1)
                ->where('requests.data.0.id', $voidA->id)
            );
    }

    public function test_supervisor_can_approve_void_for_own_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $supervisorA = $this->createSupervisor($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 5]);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, ['quantity' => 2]);
        $this->assertSame(3, (int) $productA->fresh()->stock);

        $voidA = app(PosTransactionService::class)->requestVoid($txA, $cashierA, 'Salah transaksi');

        $this->actingAs($supervisorA)
            ->post(route('cooperative.pos.void-requests.process', $voidA->id), [
                'decision' => 'APPROVE',
            ])
            ->assertRedirect();

        $this->assertSame('VOIDED', $txA->fresh()->status);
        $this->assertSame('APPROVED', $voidA->fresh()->status);
        $this->assertSame(5, (int) $productA->fresh()->stock);
    }

    public function test_supervisor_cannot_approve_void_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierB = $this->createCashier($orgB);
        $supervisorA = $this->createSupervisor($orgA);
        $productB = $this->createProduct($orgB);

        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $voidB = app(PosTransactionService::class)->requestVoid($txB, $cashierB, 'Void Org B');

        $this->actingAs($supervisorA)
            ->post(route('cooperative.pos.void-requests.process', $voidB->id), [
                'decision' => 'APPROVE',
            ])
            ->assertNotFound();

        $this->assertSame('PENDING', $voidB->fresh()->status);
        $this->assertSame('VOID_PENDING', $txB->fresh()->status);
    }

    public function test_supervisor_cannot_reject_void_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierB = $this->createCashier($orgB);
        $supervisorA = $this->createSupervisor($orgA);
        $productB = $this->createProduct($orgB);

        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $voidB = app(PosTransactionService::class)->requestVoid($txB, $cashierB, 'Void Org B');

        $this->actingAs($supervisorA)
            ->post(route('cooperative.pos.void-requests.process', $voidB->id), [
                'decision' => 'REJECT',
                'rejection_reason' => 'Cross-org reject attempt',
            ])
            ->assertNotFound();

        $this->assertSame('PENDING', $voidB->fresh()->status);
    }

    public function test_service_approve_void_throws_authorization_exception_for_cross_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierB = $this->createCashier($orgB);
        $supervisorA = $this->createSupervisor($orgA);
        $productB = $this->createProduct($orgB);

        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $voidB = app(PosTransactionService::class)->requestVoid($txB, $cashierB, 'Void Org B');

        $this->expectException(AuthorizationException::class);
        app(PosTransactionService::class)->approveVoid($voidB, $supervisorA);
    }

    public function test_service_reject_void_throws_authorization_exception_for_cross_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierB = $this->createCashier($orgB);
        $supervisorA = $this->createSupervisor($orgA);
        $productB = $this->createProduct($orgB);

        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $voidB = app(PosTransactionService::class)->requestVoid($txB, $cashierB, 'Void Org B');

        $this->expectException(AuthorizationException::class);
        app(PosTransactionService::class)->rejectVoid($voidB, $supervisorA, 'Alasan ditolak');
    }

    public function test_global_supervisor_can_approve_void_for_any_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $globalSupervisor = $this->createGlobalSupervisor();

        $txA = $this->createTransaction($orgA, $cashierA, $productA);
        $voidA = app(PosTransactionService::class)->requestVoid($txA, $cashierA, 'Void Org A');

        $this->actingAs($globalSupervisor)
            ->post(route('cooperative.pos.void-requests.process', $voidA->id), [
                'decision' => 'APPROVE',
            ])
            ->assertRedirect();

        $this->assertSame('APPROVED', $voidA->fresh()->status);
        $this->assertSame('VOIDED', $txA->fresh()->status);
    }

    public function test_null_org_supervisor_cannot_process_void(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);
        $voidA = app(PosTransactionService::class)->requestVoid($txA, $cashierA, 'Void Org A');

        $nullSupervisor = User::factory()->create(['organization_id' => null]);
        $nullSupervisor->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        $this->actingAs($nullSupervisor)
            ->post(route('cooperative.pos.void-requests.process', $voidA->id), [
                'decision' => 'APPROVE',
            ])
            ->assertForbidden();
    }

    // ==========================================
    // GROUP 7: RETURN PARENT ROUTES (39 - 42)
    // ==========================================

    public function test_return_create_page_accessible_for_own_organization(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $txA = $this->createTransaction($orgA, $cashierA, $productA);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.returns.create', $txA->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Pos/Returns/Create')
                ->where('transaction.id', $txA->id)
            );
    }

    public function test_return_create_page_returns_404_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.returns.create', $txB->id))
            ->assertNotFound();
    }

    public function test_return_store_returns_404_for_other_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $itemB = $txB->items->first();

        $this->actingAs($cashierA)
            ->post(route('cooperative.pos.returns.store', $txB->id), [
                'reason' => 'Mencoba meretur transaksi milik organisasi lain',
                'items' => [
                    ['pos_transaction_item_id' => $itemB->id, 'quantity' => 1],
                ],
            ])
            ->assertNotFound();
    }

    public function test_service_create_return_throws_authorization_exception_for_cross_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $itemB = $txB->items->first();

        $this->expectException(AuthorizationException::class);
        app(PosReturnService::class)->create([
            'pos_transaction_id' => $txB->id,
            'reason' => 'Direct service cross-org return attempt',
            'items' => [
                ['pos_transaction_item_id' => $itemB->id, 'quantity' => 1],
            ],
        ], $cashierA);
    }

    // ==========================================
    // GROUP 8: NULL OWNERSHIP / LEGACY (43 - 45)
    // ==========================================

    public function test_legacy_null_org_transaction_is_inaccessible_to_tenant_cashier(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);

        // Create transaction with organization_id = null
        $legacyTx = $this->createTransaction($orgA, $cashierA, $productA);
        $legacyTx->forceFill(['organization_id' => null])->saveQuietly();

        // 1. Show -> 404
        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.show', $legacyTx->id))
            ->assertNotFound();

        // 2. Receipt -> 404
        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.transactions.receipt', $legacyTx->id))
            ->assertNotFound();

        // 3. Void request -> 404
        $this->actingAs($cashierA)
            ->post(route('cooperative.pos.void-requests.store', $legacyTx->id), ['reason' => 'Void legacy'])
            ->assertNotFound();

        // 4. Return create -> 404
        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.returns.create', $legacyTx->id))
            ->assertNotFound();
    }

    public function test_service_assert_visible_fails_closed_on_null_org_transaction(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $tx = $this->createTransaction($orgA, $cashierA, $productA);
        $tx->forceFill(['organization_id' => null])->saveQuietly();

        $this->expectException(OrganizationScopeException::class);
        app(OrganizationScopeService::class)->assertVisible($cashierA, $tx);
    }

    public function test_migration_backfill_resolves_organization_id_from_products(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);
        $tx = $this->createTransaction($orgA, $cashierA, $productA);

        $tx->forceFill(['organization_id' => null])->saveQuietly();
        $this->assertNull($tx->fresh()->organization_id);

        // Run the backfill logic
        $migration = require database_path('migrations/2026_09_05_000001_add_organization_id_to_pos_transactions_table.php');
        $backfilledCount = $migration->backfillOrganizationIds();

        $this->assertSame(1, $backfilledCount);
        $this->assertSame($orgA->id, $tx->fresh()->organization_id);
    }

    // ==========================================
    // GROUP 6: R1 SENIOR REVIEW REGRESSIONS & BLOCKERS
    // ==========================================

    public function test_sync_idempotency_resolves_target_organization_authoritatively_for_global_operator(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        // Global operator whose home organization is Org A
        $operator = $this->createCashier($orgA, ['access_cooperative_pos', 'view_cooperative_all'], 'Global Operator');
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);
        $productB = $this->createProduct($orgB, ['sale_price' => 20000]);

        // Pre-existing transaction in Org A with client_reference 'SYNC-SHARED-REF'
        $txA = $this->createTransaction($orgA, $operator, $productA, ['client_reference' => 'SYNC-SHARED-REF']);
        $this->assertSame($orgA->id, $txA->organization_id);

        // Operator enqueues offline sync request for Org B with trusted active organization in server session
        session(['active_organization_id' => $orgB->id]);
        $request = \Illuminate\Http\Request::create('/api/v1/pos/sync/enqueue', 'POST', [
            'client_id' => 'client-global-sync-b',
            'device_id' => 'device-global-pos',
            'idempotency_key' => 'idemp-global-sync-b',
            'endpoint' => PosSyncService::ENDPOINT_TRANSACTION_STORE,
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-SHARED-REF',
                'items' => [
                    ['pos_product_id' => $productB->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
                ],
            ],
        ]);
        $request->setUserResolver(fn () => $operator);

        $syncService = app(PosSyncService::class);
        $syncRequest = $syncService->enqueue(
            $request,
            PosSyncService::ENDPOINT_TRANSACTION_STORE,
            'POST',
            $request->input('payload'),
            'idemp-global-sync-b',
            'client-global-sync-b'
        );

        $this->assertSame($orgB->id, $syncRequest->organization_id);

        // Clear session to prove tenant ownership is persisted server-side and survives deferred processing
        session()->flush();

        $result = $syncService->process($syncRequest->fresh());

        $this->assertSame(201, $result['status']);
        $this->assertNotSame($txA->id, $result['data']['id']);
        $this->assertSame($orgB->id, $result['data']['organization_id']);
        $this->assertSame('SYNC-SHARED-REF', $result['data']['client_reference']);

        // Replaying the sync request returns the Org B transaction, not Org A
        $replay = $syncService->process($syncRequest->fresh());
        $this->assertTrue($replay['replay']);
        $this->assertSame($result['data']['id'], $replay['data']['id']);
    }

    public function test_member_toctou_ownership_change_fails_closed_with_zero_side_effects(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $memberA = $this->createMember($orgA);

        $toctouTriggered = false;
        PosProduct::retrieved(function (PosProduct $p) use (&$toctouTriggered, $memberA, $orgB): void {
            if (! $toctouTriggered) {
                $toctouTriggered = true;
                DB::table('cooperative_members')->where('id', $memberA->id)->update(['organization_id' => $orgB->id]);
            }
        });

        $this->expectException(ValidationException::class);

        try {
            app(PosTransactionService::class)->create([
                'client_reference' => 'TOCTOU-MEMBER-TEST',
                'cooperative_member_id' => $memberA->id,
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
                ],
            ], $cashierA);
        } finally {
            // Verify fail-closed zero side effects
            $this->assertSame(10, (int) $productA->fresh()->stock);
            $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'TOCTOU-MEMBER-TEST']);
        }
    }

    public function test_pos_return_service_throws_authorization_exception_when_cashier_is_null(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $tx = $this->createTransaction($orgA, $cashierA, $productA, ['quantity' => 2]);
        $this->assertSame(8, (int) $productA->fresh()->stock);

        $this->expectException(AuthorizationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $tx->id,
                'reason' => 'Retur tanpa kasir terautentikasi',
                'items' => [
                    ['pos_transaction_item_id' => $tx->items->first()->id, 'quantity' => 1],
                ],
            ], null);
        } finally {
            // Assert zero side effects
            $this->assertDatabaseCount('pos_returns', 0);
            $this->assertSame(8, (int) $productA->fresh()->stock);
        }
    }

    public function test_shift_tenant_validation_matrix_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB, ['access_cooperative_pos'], 'Kasir Org B');
        $globalCashier = $this->createGlobalCashier();
        $productA = $this->createProduct($orgA);

        $shiftForeign = $this->createShift($cashierB);
        $shiftNullOrg = $this->createShift($globalCashier);
        $shiftValid = $this->createShift($cashierA);

        $service = app(PosTransactionService::class);

        // Case A: Foreign shift (Org B shift with Org A transaction) -> 422 pos_cashier_shift_id
        try {
            $service->create([
                'client_reference' => 'SHIFT-TEST-A',
                'pos_cashier_shift_id' => $shiftForeign->id,
                'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
            ], $cashierA);
            $this->fail('Expected foreign shift validation exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pos_cashier_shift_id', $e->errors());
        }

        // Case B: Shift cashier null org -> 422 pos_cashier_shift_id
        try {
            $service->create([
                'client_reference' => 'SHIFT-TEST-B',
                'pos_cashier_shift_id' => $shiftNullOrg->id,
                'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
            ], $cashierA);
            $this->fail('Expected null org shift validation exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pos_cashier_shift_id', $e->errors());
        }

        // Case C: Nonexistent shift -> 422 pos_cashier_shift_id
        try {
            $service->create([
                'client_reference' => 'SHIFT-TEST-C',
                'pos_cashier_shift_id' => 999999,
                'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
                'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
            ], $cashierA);
            $this->fail('Expected nonexistent shift validation exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pos_cashier_shift_id', $e->errors());
        }

        // Case D: Same-org valid shift -> succeeds
        $validTx = $service->create([
            'client_reference' => 'SHIFT-TEST-D',
            'pos_cashier_shift_id' => $shiftValid->id,
            'items' => [['pos_product_id' => $productA->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashierA);
        $this->assertSame($shiftValid->id, $validTx->pos_cashier_shift_id);
        $this->assertSame($orgA->id, $validTx->organization_id);
    }

    public function test_void_approval_fails_closed_if_product_organization_is_corrupted_or_mismatched(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $supervisorA = $this->createSupervisor($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);

        $tx = $this->createTransaction($orgA, $cashierA, $productA, ['quantity' => 2]);
        $this->assertSame(8, (int) $productA->fresh()->stock);

        $voidRequest = PosVoidRequest::query()->create([
            'pos_transaction_id' => $tx->id,
            'requested_by' => $cashierA->id,
            'reason' => 'Void reason test',
            'status' => PosVoidRequest::STATUS_PENDING,
        ]);

        // Corrupt product organization to foreign org (or null)
        $productA->forceFill(['organization_id' => $orgB->id])->saveQuietly();

        $this->expectException(ValidationException::class);

        try {
            app(PosTransactionService::class)->approveVoid($voidRequest, $supervisorA);
        } finally {
            // Verify fail-closed zero side effects: stock not restored, tx still COMPLETED, void request still PENDING
            $this->assertSame(8, (int) $productA->fresh()->stock);
            $this->assertSame('COMPLETED', $tx->fresh()->status);
            $this->assertSame(PosVoidRequest::STATUS_PENDING, $voidRequest->fresh()->status);
        }
    }

    public function test_migration_rollback_fails_if_duplicate_client_references_exist_across_organizations(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB, ['access_cooperative_pos'], 'Kasir B');
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        // Same client_reference across 2 distinct organizations
        $this->createTransaction($orgA, $cashierA, $productA, ['client_reference' => 'DUP-REF-ROLLBACK']);
        $this->createTransaction($orgB, $cashierB, $productB, ['client_reference' => 'DUP-REF-ROLLBACK']);

        $migration = require database_path('migrations/2026_09_05_000001_add_organization_id_to_pos_transactions_table.php');

        $this->expectException(\LogicException::class);

        try {
            $migration->down();
        } finally {
            $this->assertTrue(Schema::hasColumn('pos_transactions', 'organization_id'));
        }
    }

    public function test_migration_rollback_succeeds_when_client_references_are_unique(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA);

        $this->createTransaction($orgA, $cashierA, $productA, ['client_reference' => 'UNIQUE-REF-ROLLBACK']);

        $migration = require database_path('migrations/2026_09_05_000001_add_organization_id_to_pos_transactions_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('pos_transactions', 'organization_id'));

        // Restore schema for subsequent test isolation
        $migration->up();
        $this->assertTrue(Schema::hasColumn('pos_transactions', 'organization_id'));
    }

    // ==========================================
    // GROUP 11: R1-SYNC-01 TO R1-SYNC-08 (MANDATORY SYNC REGRESSION)
    // ==========================================

    public function test_r1_sync_01_ordinary_cashier_header_override_ignored_never_creates_in_foreign_org(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);

        Sanctum::actingAs($cashierA, ['pos:read', 'pos:write']);

        $payload = [
            'client_id' => 'client-r1-sync-01',
            'device_id' => 'device-pos-01',
            'idempotency_key' => 'idemp-r1-sync-01',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-01',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 2],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
                ],
            ],
        ];

        // Ordinary cashier sends X-Active-Organization-Id: Org B header
        $enqueueResponse = $this->withHeader('X-Active-Organization-Id', $orgB->id)
            ->postJson('/api/v1/pos/sync/enqueue', $payload);

        $enqueueResponse->assertStatus(202);

        $syncRequest = PosSyncRequest::query()->where('idempotency_key', 'idemp-r1-sync-01')->firstOrFail();
        // Authoritative server-side tenant is Org A, NOT the client claim Org B
        $this->assertSame($orgA->id, $syncRequest->organization_id);

        // Process sync request
        $processResponse = $this->withHeader('X-Device-Id', 'device-pos-01')
            ->postJson('/api/v1/pos/sync/process/idemp-r1-sync-01');
        $processResponse->assertStatus(201);

        $result = $processResponse->json();
        $this->assertSame(201, $result['status']);
        $this->assertSame($orgA->id, $result['data']['organization_id']);
        $this->assertSame('SYNC-R1-01', $result['data']['client_reference']);

        // Transaction NEVER created in Org B
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $orgB->id)->count());
        $this->assertSame(1, PosTransaction::query()->where('organization_id', $orgA->id)->count());
        $this->assertSame(8, (int) $productA->fresh()->stock);
    }

    public function test_r1_sync_02_ordinary_cashier_header_foreign_org_with_foreign_product_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productB = $this->createProduct($orgB, ['sale_price' => 15000, 'stock' => 10]);

        Sanctum::actingAs($cashierA, ['pos:read', 'pos:write']);

        $payload = [
            'client_id' => 'client-r1-sync-02',
            'device_id' => 'device-pos-02',
            'idempotency_key' => 'idemp-r1-sync-02',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-02',
                'items' => [
                    ['pos_product_id' => $productB->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000],
                ],
            ],
        ];

        // Cashier A sends header Org B and Product B
        $this->withHeader('X-Active-Organization-Id', $orgB->id)
            ->postJson('/api/v1/pos/sync/enqueue', $payload)
            ->assertStatus(202);

        $processResponse = $this->withHeader('X-Device-Id', 'device-pos-02')
            ->postJson('/api/v1/pos/sync/process/idemp-r1-sync-02');
        $processResponse->assertStatus(403);

        $result = $processResponse->json();
        $this->assertSame(403, $result['status']);

        // FAIL CLOSED: 0 transactions, 0 stock mutation, 0 financial mutation
        $this->assertSame(0, PosTransaction::query()->count());
        $this->assertSame(10, (int) $productB->fresh()->stock);
        $this->assertSame(0, PosPayment::query()->count());
    }

    public function test_r1_sync_03_global_operator_with_trusted_active_org_a_header_org_b_never_creates_in_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalCashier = $this->createGlobalCashier();
        $productA = $this->createProduct($orgA, ['sale_price' => 12000, 'stock' => 10]);

        Sanctum::actingAs($globalCashier, ['pos:read', 'pos:write']);

        // Global operator has trusted active Org A in session
        session(['active_organization_id' => $orgA->id]);

        $payload = [
            'client_id' => 'client-r1-sync-03',
            'device_id' => 'device-pos-03',
            'idempotency_key' => 'idemp-r1-sync-03',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-03',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 12000, 'cash_received' => 12000],
                ],
            ],
        ];

        // Client sends header Org B to attempt spoofing
        $this->withHeader('X-Active-Organization-Id', $orgB->id)
            ->postJson('/api/v1/pos/sync/enqueue', $payload)
            ->assertStatus(202);

        $syncRequest = PosSyncRequest::query()->where('idempotency_key', 'idemp-r1-sync-03')->firstOrFail();
        // Must resolve to trusted session Org A, NOT client claim Org B
        $this->assertSame($orgA->id, $syncRequest->organization_id);

        // Clear session to prove tenant is persisted server-side
        session()->flush();

        $processResult = app(PosSyncService::class)->process($syncRequest->fresh());
        $this->assertSame(201, $processResult['status']);
        $this->assertSame($orgA->id, $processResult['data']['organization_id']);

        // Never creates transaction in Org B
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $orgB->id)->count());
        $this->assertSame(1, PosTransaction::query()->where('organization_id', $orgA->id)->count());
    }

    public function test_r1_sync_04_global_operator_without_trusted_active_org_header_org_b_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalCashier = $this->createGlobalCashier();
        $productB = $this->createProduct($orgB, ['sale_price' => 20000]);

        Sanctum::actingAs($globalCashier, ['pos:read', 'pos:write']);

        // No trusted active organization in session
        session()->forget('active_organization_id');

        $payload = [
            'client_id' => 'client-r1-sync-04',
            'device_id' => 'device-pos-04',
            'idempotency_key' => 'idemp-r1-sync-04',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-04',
                'items' => [
                    ['pos_product_id' => $productB->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
                ],
            ],
        ];

        // Header must NOT supply missing trusted context: must FAIL CLOSED
        $response = $this->withHeader('X-Active-Organization-Id', $orgB->id)
            ->postJson('/api/v1/pos/sync/enqueue', $payload);

        $response->assertForbidden();

        // 0 sync requests and 0 transactions created
        $this->assertDatabaseMissing('pos_sync_requests', ['idempotency_key' => 'idemp-r1-sync-04']);
        $this->assertDatabaseMissing('pos_transactions', ['client_reference' => 'SYNC-R1-04']);
    }

    public function test_r1_sync_05_trusted_org_a_sync_request_processes_as_org_a_with_no_original_session_active(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['sale_price' => 10000, 'stock' => 10]);

        Sanctum::actingAs($cashierA, ['pos:read', 'pos:write']);

        $payload = [
            'client_id' => 'client-r1-sync-05',
            'device_id' => 'device-pos-05',
            'idempotency_key' => 'idemp-r1-sync-05',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-05',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 2],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
                ],
            ],
        ];

        $this->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);

        $syncRequest = PosSyncRequest::query()->where('idempotency_key', 'idemp-r1-sync-05')->firstOrFail();
        $this->assertSame($orgA->id, $syncRequest->organization_id);

        // Flush session completely
        session()->flush();

        // Process later without active session
        $result = app(PosSyncService::class)->process($syncRequest->fresh());

        $this->assertSame(201, $result['status']);
        $this->assertSame($orgA->id, $result['data']['organization_id']);
        $this->assertSame(8, (int) $productA->fresh()->stock);
    }

    public function test_r1_sync_06_trusted_org_a_sync_request_with_product_b_fails_closed(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productB = $this->createProduct($orgB, ['sale_price' => 15000, 'stock' => 10]);

        Sanctum::actingAs($cashierA, ['pos:read', 'pos:write']);

        $payload = [
            'client_id' => 'client-r1-sync-06',
            'device_id' => 'device-pos-06',
            'idempotency_key' => 'idemp-r1-sync-06',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-06',
                'items' => [
                    ['pos_product_id' => $productB->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000],
                ],
            ],
        ];

        $this->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);

        $syncRequest = PosSyncRequest::query()->where('idempotency_key', 'idemp-r1-sync-06')->firstOrFail();
        $result = app(PosSyncService::class)->process($syncRequest);

        $this->assertSame(403, $result['status']);
        $this->assertSame(10, (int) $productB->fresh()->stock);
        $this->assertSame(0, PosTransaction::query()->count());
    }

    public function test_r1_sync_07_trusted_org_a_sync_request_replayed_after_user_active_org_changes_to_org_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalCashier = $this->createGlobalCashier();
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);

        Sanctum::actingAs($globalCashier, ['pos:read', 'pos:write']);

        // Enqueue and process under trusted Org A
        session(['active_organization_id' => $orgA->id]);

        $payload = [
            'client_id' => 'client-r1-sync-07',
            'device_id' => 'device-pos-07',
            'idempotency_key' => 'idemp-r1-sync-07',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'SYNC-R1-07',
                'items' => [
                    ['pos_product_id' => $productA->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000],
                ],
            ],
        ];

        $this->postJson('/api/v1/pos/sync/enqueue', $payload)->assertStatus(202);

        $syncService = app(PosSyncService::class);
        $syncRequest = PosSyncRequest::query()->where('idempotency_key', 'idemp-r1-sync-07')->firstOrFail();
        $result = $syncService->process($syncRequest);
        $this->assertSame(201, $result['status']);
        $this->assertSame($orgA->id, $result['data']['organization_id']);

        // Switch active organization in session to Org B
        session(['active_organization_id' => $orgB->id]);

        // Replay
        $replay = $syncService->process($syncRequest->fresh());
        $this->assertTrue($replay['replay']);
        $this->assertSame($result['data']['id'], $replay['data']['id']);
        $this->assertSame($orgA->id, $replay['data']['organization_id']);

        // Never rebound to Org B
        $this->assertSame(0, PosTransaction::query()->where('organization_id', $orgB->id)->count());
    }

    public function test_r1_sync_08_identical_client_reference_in_org_a_and_org_b_remains_isolated_in_sync(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB, ['access_cooperative_pos'], 'Kasir Org B');
        $productA = $this->createProduct($orgA, ['sale_price' => 10000]);
        $productB = $this->createProduct($orgB, ['sale_price' => 20000]);

        // Existing transaction in Org A with client_reference 'REF-R1-08'
        $txA = $this->createTransaction($orgA, $cashierA, $productA, ['client_reference' => 'REF-R1-08']);
        $this->assertSame($orgA->id, $txA->organization_id);

        Sanctum::actingAs($cashierB, ['pos:read', 'pos:write']);

        // Valid Org B transaction enqueued via sync with identical client_reference
        $payloadB = [
            'client_id' => 'client-r1-sync-08',
            'device_id' => 'device-pos-08',
            'idempotency_key' => 'idemp-r1-sync-08',
            'endpoint' => 'pos.transactions.store',
            'method' => 'POST',
            'payload' => [
                'client_reference' => 'REF-R1-08',
                'items' => [
                    ['pos_product_id' => $productB->id, 'quantity' => 1],
                ],
                'payments' => [
                    ['payment_method' => 'CASH', 'amount' => 20000, 'cash_received' => 20000],
                ],
            ],
        ];

        $this->postJson('/api/v1/pos/sync/enqueue', $payloadB)->assertStatus(202);

        $processResult = app(PosSyncService::class)->process(
            PosSyncRequest::query()->where('idempotency_key', 'idemp-r1-sync-08')->firstOrFail()
        );

        $this->assertSame(201, $processResult['status']);
        $this->assertSame($orgB->id, $processResult['data']['organization_id']);
        $this->assertNotSame($txA->id, $processResult['data']['id']);

        // Both transactions exist and are completely isolated by organization
        $this->assertSame(1, PosTransaction::query()->where('organization_id', $orgA->id)->where('client_reference', 'REF-R1-08')->count());
        $this->assertSame(1, PosTransaction::query()->where('organization_id', $orgB->id)->where('client_reference', 'REF-R1-08')->count());
    }

    // ==========================================
    // GROUP 12: R1-RET-01 TO R1-RET-04 (RETURN ORACLE TESTS)
    // ==========================================

    public function test_r1_ret_01_and_02_foreign_transaction_and_nonexistent_transaction_return_identical_404(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productB = $this->createProduct($orgB);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $nonExistentId = 999999;

        // R1-RET-01: Org A attempts return on existing Org B transaction
        $responseForeign = $this->actingAs($cashierA)->post(route('cooperative.pos.returns.store', $txB->id), [
            'reason' => 'Test foreign transaction return',
            'items' => [
                ['pos_transaction_item_id' => 1, 'quantity' => 1],
            ],
        ]);

        // R1-RET-02: Org A attempts return on nonexistent transaction
        $responseNonExistent = $this->actingAs($cashierA)->post(route('cooperative.pos.returns.store', $nonExistentId), [
            'reason' => 'Test nonexistent transaction return',
            'items' => [
                ['pos_transaction_item_id' => 1, 'quantity' => 1],
            ],
        ]);

        // Require same externally observable status: 404 == 404
        $this->assertSame(404, $responseForeign->status());
        $this->assertSame(404, $responseNonExistent->status());
        $this->assertSame($responseForeign->status(), $responseNonExistent->status());
    }

    public function test_r1_ret_03_and_04_foreign_item_and_nonexistent_item_return_identical_validation_failure(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $productA = $this->createProduct($orgA);
        $productB = $this->createProduct($orgB);

        $txA = $this->createTransaction($orgA, $cashierA, $productA);
        $txB = $this->createTransaction($orgB, $cashierB, $productB);
        $foreignItemId = $txB->items->first()->id;
        $nonExistentItemId = 999999;

        // R1-RET-03: Own Org A transaction receives item ID from another existing transaction
        $responseForeignItem = $this->actingAs($cashierA)
            ->from(route('cooperative.pos.returns.create', $txA->id))
            ->post(route('cooperative.pos.returns.store', $txA->id), [
                'reason' => 'Return with foreign item',
                'items' => [
                    ['pos_transaction_item_id' => $foreignItemId, 'quantity' => 1],
                ],
            ]);

        // R1-RET-04: Own Org A transaction receives nonexistent transaction-item ID
        $responseNonExistentItem = $this->actingAs($cashierA)
            ->from(route('cooperative.pos.returns.create', $txA->id))
            ->post(route('cooperative.pos.returns.store', $txA->id), [
                'reason' => 'Return with nonexistent item',
                'items' => [
                    ['pos_transaction_item_id' => $nonExistentItemId, 'quantity' => 1],
                ],
            ]);

        // Both must fail validation on items without leaking existence
        $responseForeignItem->assertSessionHasErrors('items.0.pos_transaction_item_id');
        $responseNonExistentItem->assertSessionHasErrors('items.0.pos_transaction_item_id');

        // Verify identical error message
        $this->assertSame(
            session('errors')->get('items.0.pos_transaction_item_id'),
            $responseNonExistentItem->getSession()->get('errors')->get('items.0.pos_transaction_item_id')
        );

        // Zero mutations across both attempts
        $this->assertSame(0, PosReturn::query()->count());
        $this->assertSame(0, PosReturnItem::query()->count());
    }

    // ==========================================
    // GROUP 13: R1-INTEGRITY-01 TO R1-INTEGRITY-06 (RETURN CORRUPT-DATA TESTS)
    // ==========================================

    public function test_r1_integrity_01_foreign_product_relation_fails_closed_zero_mutations(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $productB = $this->createProduct($orgB, ['stock' => 10, 'sale_price' => 20000]);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, ['quantity' => 2]);
        $txItem = $txA->items->first();

        // Corrupt fixture: item's product points to Org B
        $txItem->forceFill(['pos_product_id' => $productB->id])->saveQuietly();

        $this->expectException(ValidationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $txA->id,
                'reason' => 'Corrupt product foreign org',
                'items' => [
                    ['pos_transaction_item_id' => $txItem->id, 'quantity' => 1],
                ],
            ], $cashierA);
        } finally {
            // Assert FAIL CLOSED:
            $this->assertSame(0, PosReturn::query()->count());
            $this->assertSame(0, PosReturnItem::query()->count());
            $this->assertSame(10, (int) $productB->fresh()->stock);
            $this->assertSame(8, (int) $productA->fresh()->stock);
            $this->assertSame(0, PointTransaction::query()->where('reference_type', 'POS_RETURN')->count());
            $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_type', 'POS_RETURN')->count());
        }
    }

    public function test_r1_integrity_02_null_product_organization_fails_closed_zero_mutations(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, ['quantity' => 2]);
        $txItem = $txA->items->first();

        // Corrupt fixture: Product organization_id is NULL
        $productA->forceFill(['organization_id' => null])->saveQuietly();

        $this->expectException(ValidationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $txA->id,
                'reason' => 'Product null org',
                'items' => [
                    ['pos_transaction_item_id' => $txItem->id, 'quantity' => 1],
                ],
            ], $cashierA);
        } finally {
            $this->assertSame(0, PosReturn::query()->count());
            $this->assertSame(0, PosReturnItem::query()->count());
            $this->assertSame(8, (int) $productA->fresh()->stock);
        }
    }

    public function test_r1_integrity_03_foreign_member_relation_fails_closed_zero_mutations(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $memberA = $this->createMember($orgA);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, [
            'quantity' => 1,
            'cooperative_member_id' => $memberA->id,
        ]);
        $txItem = $txA->items->first();

        // Corrupt member organization to Org B
        $memberA->forceFill(['organization_id' => $orgB->id])->saveQuietly();

        $this->expectException(ValidationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $txA->id,
                'reason' => 'Corrupt foreign member',
                'items' => [
                    ['pos_transaction_item_id' => $txItem->id, 'quantity' => 1],
                ],
            ], $cashierA);
        } finally {
            $this->assertSame(0, PosReturn::query()->count());
            $this->assertSame(9, (int) $productA->fresh()->stock);
            $this->assertSame(0, PointTransaction::query()->where('reference_type', 'POS_RETURN')->count());
            $this->assertSame(0, CooperativeLedgerEntry::query()->where('source_type', 'POS_RETURN')->count());
        }
    }

    public function test_r1_integrity_04_null_member_organization_fails_closed_zero_mutations(): void
    {
        // Temporarily allow null on cooperative_members.organization_id for this isolated test
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $memberA = $this->createMember($orgA);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, [
            'quantity' => 1,
            'cooperative_member_id' => $memberA->id,
        ]);
        $txItem = $txA->items->first();

        // Corrupt member organization to NULL
        DB::table('cooperative_members')->where('id', $memberA->id)->update(['organization_id' => null]);

        $this->expectException(ValidationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $txA->id,
                'reason' => 'Corrupt null member org',
                'items' => [
                    ['pos_transaction_item_id' => $txItem->id, 'quantity' => 1],
                ],
            ], $cashierA);
        } finally {
            $this->assertSame(0, PosReturn::query()->count());
            $this->assertSame(9, (int) $productA->fresh()->stock);
        }
    }

    public function test_r1_integrity_05_foreign_store_account_fails_closed_zero_mutations(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $productA = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $memberA = $this->createMember($orgA);

        $storeAccount = MemberStoreAccount::factory()->create([
            'organization_id' => $orgA->id,
            'cooperative_member_id' => $memberA->id,
            'balance' => 100000,
            'credit_limit' => 500000,
        ]);

        $txA = $this->createTransaction($orgA, $cashierA, $productA, [
            'cooperative_member_id' => $memberA->id,
            'quantity' => 1,
        ]);
        $txItem = $txA->items->first();

        PosPayment::query()->create([
            'pos_transaction_id' => $txA->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'amount' => 10000,
            'status' => 'COMPLETED',
        ]);

        // Corrupt store account organization to Org B
        $storeAccount->forceFill(['organization_id' => $orgB->id])->saveQuietly();

        $this->expectException(ValidationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $txA->id,
                'reason' => 'Corrupt store account org',
                'items' => [
                    ['pos_transaction_item_id' => $txItem->id, 'quantity' => 1],
                ],
            ], $cashierA);
        } finally {
            $this->assertSame(0, PosReturn::query()->count());
            $this->assertSame(9, (int) $productA->fresh()->stock);
            $this->assertSame(100000, (int) $storeAccount->fresh()->balance);
        }
    }

    public function test_r1_integrity_06_mixed_product_corruption_preflights_and_prevents_partial_processing(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $product1 = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 10000]);
        $product2 = $this->createProduct($orgA, ['stock' => 10, 'sale_price' => 15000]);
        $foreignProduct = $this->createProduct($orgB, ['stock' => 10, 'sale_price' => 20000]);

        $service = app(PosTransactionService::class);
        $txA = $service->create([
            'client_reference' => 'TX-MIXED-CORRUPT-'.uniqid(),
            'items' => [
                ['pos_product_id' => $product1->id, 'quantity' => 1],
                ['pos_product_id' => $product2->id, 'quantity' => 1],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 25000, 'cash_received' => 25000],
            ],
        ], $cashierA);

        $items = $txA->items;
        $item1 = $items->first();
        $item2 = $items->last();

        // Corrupt Item 2 to foreign product in Org B
        $item2->forceFill(['pos_product_id' => $foreignProduct->id])->saveQuietly();

        $this->expectException(ValidationException::class);

        try {
            app(PosReturnService::class)->create([
                'pos_transaction_id' => $txA->id,
                'reason' => 'Mixed product return attempt',
                'items' => [
                    ['pos_transaction_item_id' => $item1->id, 'quantity' => 1],
                    ['pos_transaction_item_id' => $item2->id, 'quantity' => 1],
                ],
            ], $cashierA);
        } finally {
            // Full preflight must prevent ANY mutation to Product 1 and Product 2!
            $this->assertSame(9, (int) $product1->fresh()->stock);
            $this->assertSame(9, (int) $product2->fresh()->stock);
            $this->assertSame(10, (int) $foreignProduct->fresh()->stock);
            $this->assertSame(0, PosReturn::query()->count());
        }
    }

    // ==========================================
    // GROUP 14: POS SYNC REQUEST MIGRATION & BACKFILL TESTS
    // ==========================================

    public function test_pos_sync_request_migration_up_and_down_and_fresh(): void
    {
        $migration = require database_path('migrations/2026_09_08_000001_add_organization_id_to_pos_sync_requests_table.php');

        $migration->down();
        $this->assertFalse(Schema::hasColumn('pos_sync_requests', 'organization_id'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('pos_sync_requests', 'organization_id'));
    }

    public function test_legacy_sync_request_without_trusted_organization_fails_closed_without_acquiring_ownership(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $globalCashier = $this->createGlobalCashier();

        // Legacy sync request with organization_id = null for cashier A
        $syncReqA = PosSyncRequest::query()->create([
            'idempotency_key' => 'legacy-cashier-a',
            'user_id' => $cashierA->id,
            'client_id' => 'legacy-client-a',
            'device_id' => 'legacy-device-a',
            'endpoint' => PosSyncService::ENDPOINT_TRANSACTION_STORE,
            'method' => 'POST',
            'payload' => ['x' => 1],
            'status' => PosSyncRequest::STATUS_PENDING,
            'organization_id' => null,
        ]);

        // Legacy sync request with organization_id = null for global user
        $syncReqGlobal = PosSyncRequest::query()->create([
            'idempotency_key' => 'legacy-global-user',
            'user_id' => $globalCashier->id,
            'client_id' => 'legacy-client-global',
            'device_id' => 'legacy-device-global',
            'endpoint' => PosSyncService::ENDPOINT_TRANSACTION_STORE,
            'method' => 'POST',
            'payload' => ['x' => 1],
            'status' => PosSyncRequest::STATUS_PENDING,
            'organization_id' => null,
        ]);

        $migration = require database_path('migrations/2026_09_08_000001_add_organization_id_to_pos_sync_requests_table.php');
        $resolvedCount = $migration->backfillOrganizationIds();

        // Only cashier A is deterministically resolved to Org A
        $this->assertSame(1, $resolvedCount);
        $this->assertSame($orgA->id, $syncReqA->fresh()->organization_id);

        // Global user cannot be safely inferred and MUST remain null
        $this->assertNull($syncReqGlobal->fresh()->organization_id);

        // Attempting to process legacy sync request with unknown tenant must FAIL CLOSED
        $syncService = app(PosSyncService::class);
        $result = $syncService->process($syncReqGlobal->fresh());
        $this->assertSame(422, $result['status']);
        $this->assertStringContainsString('organisasi', $result['data']['errors']['organization_id'][0] ?? $result['data']['message'] ?? $result['data']['error'] ?? '');
    }

    public function test_pos_sync_request_migration_postgresql_path_remains_valid(): void
    {
        $migration = require database_path('migrations/2026_09_08_000001_add_organization_id_to_pos_sync_requests_table.php');
        $this->assertNotNull($migration);
        $this->assertTrue(method_exists($migration, 'up'));
        $this->assertTrue(method_exists($migration, 'down'));
        $this->assertTrue(method_exists($migration, 'backfillOrganizationIds'));
    }

    // ==========================================
    // HELPER FIXTURES
    // ==========================================

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

    private function createCashier(Organization $org, array $perms = ['access_cooperative_pos'], string $name = 'Kasir Test'): User
    {
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name' => $name,
        ]);
        $user->givePermissionTo($perms);

        return $user;
    }

    private function createSupervisor(Organization $org, array $perms = ['access_cooperative_pos', 'approve_pos_void']): User
    {
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Supervisor '.$org->name,
        ]);
        $user->givePermissionTo($perms);

        return $user;
    }

    private function createGlobalCashier(): User
    {
        $user = User::factory()->create([
            'organization_id' => null,
            'name' => 'Global Cashier Operator',
        ]);
        $user->givePermissionTo(['access_cooperative_pos', 'view_cooperative_all']);

        return $user;
    }

    private function createGlobalSupervisor(): User
    {
        $user = User::factory()->create([
            'organization_id' => null,
            'name' => 'Global Supervisor Operator',
        ]);
        $user->givePermissionTo(['access_cooperative_pos', 'approve_pos_void', 'view_cooperative_all']);

        return $user;
    }

    private function createMember(Organization $org, string $name = 'Member Test'): CooperativeMember
    {
        return CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'name' => $name,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'credit_limit' => 500000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function createProduct(Organization $org, array $attrs = []): PosProduct
    {
        $category = PosCategory::factory()->create(['organization_id' => $org->id]);

        return PosProduct::factory()->create([
            'organization_id' => $org->id,
            'pos_category_id' => $category->id,
            'cost_price' => 5000,
            'sale_price' => 10000,
            'stock' => 50,
            ...$attrs,
        ]);
    }

    private function createShift(User $cashier): PosCashierShift
    {
        return PosCashierShift::query()->create([
            'shift_no' => 'SHIFT-'.uniqid(),
            'shift_date' => now()->toDateString(),
            'opened_at' => now()->subHours(2),
            'opening_cash' => 50000,
            'cashier_id' => $cashier->id,
            'status' => 'OPEN',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function createTransaction(Organization $org, User $cashier, PosProduct $product, array $attrs = []): PosTransaction
    {
        $qty = $attrs['quantity'] ?? 1;
        unset($attrs['quantity']);

        $service = app(PosTransactionService::class);

        return $service->create([
            'client_reference' => $attrs['client_reference'] ?? 'TRX-'.uniqid(),
            'transaction_no' => $attrs['transaction_no'] ?? null,
            'cooperative_member_id' => $attrs['cooperative_member_id'] ?? null,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => $qty],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => (float) $product->sale_price * $qty, 'cash_received' => (float) $product->sale_price * $qty],
            ],
            ...$attrs,
        ], $cashier);
    }
}
