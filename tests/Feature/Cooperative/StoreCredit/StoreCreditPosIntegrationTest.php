<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StoreCreditPosIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_account_purchase_decreases_balance_and_posts_ledger(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->accountFor($member, openingBalance: 500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-POS-001',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Anggota Sendiri',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
        ])->assertSuccessful();

        $this->assertSame(400000, $account->refresh()->signedBalance());
        $this->assertSame(1, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->count());
    }

    public function test_over_limit_store_account_purchase_fails_atomically(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->accountFor($member, openingBalance: 0, creditLimit: 50000);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-POS-OVER-LIMIT',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Anggota Sendiri',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'SC-POS-OVER-LIMIT')->count());
        $this->assertSame(0, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
        $this->assertSame(10, (int) $product->refresh()->stock, 'Stock must not be reduced on failed purchase.');
    }

    public function test_duplicate_store_account_purchase_does_not_double_debit(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->accountFor($member, openingBalance: 500000);

        $payload = [
            'client_reference' => 'SC-POS-DUP',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Anggota Sendiri',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ];

        $first = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), $payload);
        $second = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), $payload);

        $first->assertSuccessful();
        $second->assertSuccessful();
        $this->assertSame(450000, $account->refresh()->signedBalance());
        $this->assertSame(1, PosTransaction::query()->where('client_reference', 'SC-POS-DUP')->count());
    }

    public function test_existing_cash_payment_still_works(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-POS-CASH',
            'payment_method' => 'CASH',
            'cash_received' => 100000,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
        ])->assertSuccessful();

        $this->assertSame(1, PosTransaction::query()->where('client_reference', 'SC-POS-CASH')->count());
    }

    public function test_store_account_purchase_requires_active_member(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $member->update(['status' => 'INACTIVE']);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-POS-INACTIVE',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Anggota Sendiri',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_member_api_can_view_own_account_summary(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->accountFor($member, openingBalance: 250000);

        $token = $member->user?->createToken('test', ['member:read'])->plainTextToken
            ?? tap(User::factory()->create(['organization_id' => $member->organization_id]), function (User $user) use ($member): void {
                $member->update(['user_id' => $user->id]);
            })->createToken('test', ['member:read'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary');

        $response->assertSuccessful();
        $response->assertJsonPath('data.balance', 250000);
    }

    public function test_member_api_ledger_exposes_stable_store_attribution_contract(): void
    {
        [$cashier, $member, $product] = $this->checkoutFixture();
        $account = $this->accountFor($member, openingBalance: 250000);
        $memberUser = User::factory()->create(['organization_id' => $member->organization_id]);
        $member->update(['user_id' => $memberUser->id]);
        $this->assertSame($member->id, $memberUser->fresh()->cooperativeMember?->id);
        $this->assertSame($member->id, $memberUser->fresh()->cooperativeMember()->active()->first()?->id);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-POS-CONTRACT',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Budi sebagai pembeli',
            'purchase_note' => 'Diambil oleh staff kantor',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        Auth::logout();
        $token = $memberUser->createToken('test', ['member:read'])->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/ledger');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.purchaser_name', 'Budi sebagai pembeli')
            ->assertJsonPath('data.0.purchase_note', 'Diambil oleh staff kantor')
            ->assertJsonPath('data.0.cashier_name', $cashier->name)
            ->assertJsonPath('data.0.reference_type', 'pos_transaction')
            ->assertJsonPath('data.0.status', 'purchase')
            ->assertJsonPath('data.0.balance_after', 200000);

        $entry = $response->json('data.0');
        $this->assertIsString($entry['transaction_no']);
        $this->assertNotSame('', $entry['transaction_no']);
        $this->assertIsString($entry['occurred_at']);
        $this->assertStringContainsString('T', $entry['occurred_at']);

        $this->assertStringNotContainsString('App\\Models\\', $response->getContent());
        $this->assertSame(200000, $account->refresh()->signedBalance());
    }

    private function checkoutFixture(): array
    {
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit']);

        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 50000,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$cashier, $member, $product];
    }

    private function accountFor(CooperativeMember $member, int $openingBalance = 0, int $creditLimit = 0)
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $opener = User::factory()->create(['organization_id' => $member->organization_id]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            creditLimit: $creditLimit,
            openingBalance: $openingBalance,
            openedBy: $opener,
        ));
    }
}
