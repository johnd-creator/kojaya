<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosMemberCreditPayment;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosPhase2MemberCreditTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_with_no_credit_limit_cannot_pay_with_credit(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'credit_limit' => 0,
        ]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE2-NO-LIMIT',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_CREDIT',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cooperative_member_id']);
    }

    public function test_member_credit_purchase_increases_outstanding_balance(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'credit_limit' => 100000,
        ]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE2-CREDIT-1',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_CREDIT',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(15000.0, (float) $member->outstanding_balance);
        $this->assertTrue(CooperativeLedgerEntry::query()
            ->where('cooperative_member_id', $member->id)
            ->where('entry_type', 'POS_MEMBER_CREDIT')
            ->exists());
    }

    public function test_member_credit_purchase_exceeding_limit_is_rejected(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'credit_limit' => 10000,
            'outstanding_balance' => 8000,
        ]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE2-OVER-LIMIT',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_CREDIT',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cooperative_member_id']);
    }

    public function test_credit_payment_reduces_outstanding_balance(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'credit_limit' => 100000,
            'outstanding_balance' => 20000,
        ]);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.credit.store', $member->id), [
            'amount' => 5000,
            'reference_no' => 'PAY-001',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(15000.0, (float) $member->outstanding_balance);
        $this->assertSame(1, PosMemberCreditPayment::query()->where('cooperative_member_id', $member->id)->count());
    }

    public function test_credit_payment_exceeding_outstanding_is_rejected(): void
    {
        $cashier = $this->cashier();
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'credit_limit' => 100000,
            'outstanding_balance' => 10000,
        ]);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.credit.store', $member->id), [
            'amount' => 50000,
        ])->assertStatus(422);
    }

    public function test_void_credit_transaction_reduces_outstanding(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'credit_limit' => 100000,
        ]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        $this->actingAs($cashier)->post(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'PHASE2-CREDIT-VOID',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_CREDIT',
            'items' => [
                ['pos_product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(10000.0, (float) $member->outstanding_balance);

        $transaction = PosTransaction::query()->where('client_reference', 'PHASE2-CREDIT-VOID')->firstOrFail();
        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $transaction->id), [
            'reason' => 'Salah input',
        ])->assertRedirect();
        $voidRequest = \App\Models\PosVoidRequest::query()
            ->where('pos_transaction_id', $transaction->id)
            ->firstOrFail();
        $this->actingAs($supervisor)->post(route('cooperative.pos.void-requests.process', $voidRequest->id), [
            'decision' => 'APPROVE',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(0.0, (float) $member->outstanding_balance);
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
