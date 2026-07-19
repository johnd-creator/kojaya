<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\PosVoidRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosPhase1FeatureTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_split_payment_with_multiple_methods_is_recorded(): void
    {
        $cashier = $this->cashier();
        $organization = Organization::factory()->create();
        $cashier->update(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE', 'organization_id' => $organization->id]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-SPLIT-1',
            'cooperative_member_id' => $member->id,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 4],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 10000],
                ['payment_method' => 'QRIS', 'amount' => 10000],
            ],
        ]);

        $response->assertRedirect();
        $transaction = PosTransaction::query()->where('client_reference', 'PHASE1-SPLIT-1')->with('payments')->firstOrFail();

        $this->assertCount(2, $transaction->payments);
        $this->assertSame(10000.0, (float) $transaction->cash_received);
        $this->assertSame(0.0, (float) $transaction->cash_change);
    }

    public function test_split_payment_with_mismatched_total_is_rejected(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-SPLIT-INVALID',
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
            'payments' => [
                ['payment_method' => 'CASH', 'amount' => 5000],
                ['payment_method' => 'QRIS', 'amount' => 1000],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payments']);
    }

    public function test_member_credit_payment_requires_active_member(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-NO-MEMBER',
            'payment_method' => 'MEMBER_CREDIT',
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cooperative_member_id']);
    }

    public function test_void_request_approval_restores_stock_and_marks_voided(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-VOID-1',
            'payment_method' => 'CASH',
            'cash_received' => 15000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 3],
            ],
        ]);
        $response->assertRedirect();

        $transaction = PosTransaction::query()->where('client_reference', 'PHASE1-VOID-1')->firstOrFail();
        $product->refresh();
        $this->assertSame(7, (int) $product->stock);

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Input salah, harusnya qty 1',
        ])->assertRedirect();

        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(PosVoidRequest::STATUS_PENDING, $voidRequest->status);
        $transaction->refresh();
        $this->assertSame('VOID_PENDING', $transaction->status);

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        $transaction->refresh();
        $this->assertSame('VOIDED', $transaction->status);
        $this->assertNotNull($transaction->voided_at);
        $this->assertSame($supervisor->id, $transaction->voided_by);
        $this->assertSame(0.0, (float) $transaction->gross_profit);
        $product->refresh();
        $this->assertSame(10, (int) $product->stock);

        $this->assertSame(3, (int) PosStockMovement::query()
            ->where('source_type', PosTransaction::class)
            ->where('source_id', $transaction->id)
            ->where('movement_type', 'VOID')
            ->sum('quantity'));
    }

    public function test_void_request_rejection_restores_transaction_status(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-VOID-REJ',
            'payment_method' => 'CASH',
            'cash_received' => 5000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();
        $transaction = PosTransaction::query()->where('client_reference', 'PHASE1-VOID-REJ')->firstOrFail();
        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Tidak jadi',
        ])->assertRedirect();
        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $transaction->id)->firstOrFail();

        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'REJECT',
            'rejection_reason' => 'Tidak memenuhi syarat',
        ])->assertRedirect();

        $transaction->refresh();
        $this->assertSame('COMPLETED', $transaction->status);
        $voidRequest->refresh();
        $this->assertSame(PosVoidRequest::STATUS_REJECTED, $voidRequest->status);
    }

    public function test_duplicate_void_request_is_rejected(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-DUP-VOID',
            'payment_method' => 'CASH',
            'cash_received' => 5000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $transaction = PosTransaction::query()->where('client_reference', 'PHASE1-DUP-VOID')->firstOrFail();
        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Alasan pertama',
        ])->assertRedirect();

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Alasan kedua',
        ])->assertSessionHasErrors();
    }

    public function test_receipt_endpoint_renders_transaction(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-RECEIPT',
            'payment_method' => 'CASH',
            'cash_received' => 5000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $transaction = PosTransaction::query()->where('client_reference', 'PHASE1-RECEIPT')->firstOrFail();

        $response = $this->actingAs($cashier)->get(route('cooperative.pos.transactions.receipt', $transaction->id));
        $response->assertOk();
        $response->assertSeeText($transaction->transaction_no);
        $response->assertSeeText('KOPERASI KOJAYA');
    }

    public function test_return_creation_via_web_controller(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-RETURN-1',
            'payment_method' => 'CASH',
            'cash_received' => 10000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $transaction = PosTransaction::query()->with('items')->where('client_reference', 'PHASE1-RETURN-1')->firstOrFail();

        $this->actingAs($cashier)->post(route('cooperative.pos.returns.store', $transaction->id), [
            'pos_transaction_id' => $transaction->id,
            'reason' => 'Barang rusak di tempat',
            'items' => [
                ['pos_transaction_item_id' => $transaction->items->first()->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame(4, (int) $product->stock);
    }

    public function test_return_form_without_pos_transaction_id_uses_route_binding(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-RETURN-ROUTE',
            'payment_method' => 'CASH',
            'cash_received' => 10000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $transaction = PosTransaction::query()->with('items')->where('client_reference', 'PHASE1-RETURN-ROUTE')->firstOrFail();

        $this->actingAs($cashier)
            ->post(route('cooperative.pos.returns.store', $transaction->id), [
                'reason' => 'Pelanggan berubah pikiran',
                'items' => [
                    ['pos_transaction_item_id' => $transaction->items->first()->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertSame(4, (int) $product->stock);

        $this->assertDatabaseHas('pos_returns', [
            'pos_transaction_id' => $transaction->id,
        ]);
    }

    public function test_return_cannot_reference_items_from_another_transaction(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-RETURN-OWNER-A',
            'payment_method' => 'CASH',
            'cash_received' => 5000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();
        $transactionA = PosTransaction::query()->with('items')->where('client_reference', 'PHASE1-RETURN-OWNER-A')->firstOrFail();

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE1-RETURN-OWNER-B',
            'payment_method' => 'CASH',
            'cash_received' => 5000,
            'discount_amount' => 0,
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertRedirect();
        $transactionB = PosTransaction::query()->with('items')->where('client_reference', 'PHASE1-RETURN-OWNER-B')->firstOrFail();

        $itemFromB = $transactionB->items->first();

        $this->actingAs($cashier)
            ->post(route('cooperative.pos.returns.store', $transactionA->id), [
                'reason' => 'Coba retur punya orang lain',
                'items' => [
                    ['pos_transaction_item_id' => $itemFromB->id, 'quantity' => 1],
                ],
            ])
            ->assertSessionHasErrors('items.0.pos_transaction_item_id');
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('access_cooperative_pos');

        return $user;
    }

    private function supervisor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        return $user;
    }
}
