<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreCreditApiContractTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_summary_contract_exposes_required_public_fields(): void
    {
        [$member, $memberUser, $token, $account] = $this->memberWithAccount(openingBalance: 250000, creditLimit: 100000);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertSuccessful();

        $data = $response->json('data');

        // Required summary fields from the public contract.
        $this->assertArrayHasKey('balance', $data);
        $this->assertArrayHasKey('credit_limit', $data);
        $this->assertArrayHasKey('available_spending', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('status_label', $data);
        $this->assertArrayHasKey('balance_label', $data);

        $this->assertSame(250000, $data['balance']);
        $this->assertSame(100000, $data['credit_limit']);
        // available_spending = balance + credit_limit for a positive balance.
        $this->assertSame(350000, $data['available_spending']);
        $this->assertSame('active', $data['status']);
        $this->assertSame('Saldo tersimpan', $data['balance_label']);

        unset($memberUser);
    }

    public function test_summary_contract_exposes_organization_id_as_uuid_string(): void
    {
        [$member, $memberUser, $token, $account] = $this->memberWithAccount(openingBalance: 250000, creditLimit: 100000);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertSuccessful();

        // The organization is persisted with a UUID primary key, so the public
        // contract must expose organization_id as a UUID string, never an integer.
        $response->assertJsonPath('data.organization_id', (string) $member->organization_id);

        $this->assertIsString($response->json('data.organization_id'));
        $this->assertTrue(Str::isUuid($response->json('data.organization_id')));

        unset($memberUser);
    }

    public function test_negative_balance_reports_debt_label_and_available_spending(): void
    {
        [$member, $memberUser, $token, $account] = $this->memberWithAccount(openingBalance: 0, creditLimit: 100000);

        $cashier = User::factory()->create(['organization_id' => $member->organization_id]);
        $cashier->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit']);
        $product = \App\Models\PosProduct::factory()->create(['cost_price' => 1000, 'sale_price' => 50000, 'stock' => 10, 'is_active' => true]);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-CONTRACT-NEG',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Pembeli Utang',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        Auth::logout();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertSuccessful();

        $data = $response->json('data');
        $this->assertSame(-50000, $data['balance'], 'Balance must be negative after a credit purchase.');
        $this->assertSame('Pemakaian/utang toko', $data['balance_label']);
        // With negative balance, available spending is the remaining credit headroom.
        $this->assertSame(50000, $data['available_spending']);

        unset($memberUser);
    }

    public function test_ledger_contract_exposes_stable_fields_and_string_reference_id(): void
    {
        [$member, $memberUser, $token, $account] = $this->memberWithAccount(openingBalance: 250000);

        $entry = MemberStoreLedgerEntry::query()->create([
            'account_id' => $account->id,
            'organization_id' => $member->organization_id,
            'entry_type' => 'cash_funding',
            'amount' => 250000,
            'effect' => 'credit',
            'balance_before' => 0,
            'balance_after' => 250000,
            'reference_type' => 'App\\Models\\MemberStoreFundingRequest',
            'reference_id' => 42,
            'idempotency_key' => 'SC-CONTRACT-LEDGER-1',
            'actor_user_id' => $memberUser->id,
            'occurred_at' => now(),
        ]);

        $item = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/ledger')
            ->assertSuccessful()
            ->json('data.0');

        foreach (['amount', 'balance_after', 'purchaser_name', 'cashier_name', 'purchase_note', 'transaction_no', 'occurred_at', 'status', 'reference_type', 'reference_id'] as $field) {
            $this->assertArrayHasKey($field, $item, "Ledger item must expose the {$field} contract field.");
        }

        // reference_type is a stable public value, never the PHP class name.
        $this->assertSame('funding_request', $item['reference_type']);
        $this->assertStringNotContainsString('App\\Models\\', json_encode($item));

        // reference_id is always a string when present.
        $this->assertIsString($item['reference_id']);
        $this->assertSame('42', $item['reference_id']);

        // occurred_at is timezone-aware ISO-8601.
        $this->assertIsString($item['occurred_at']);
        $this->assertStringContainsString('T', $item['occurred_at']);

        unset($memberUser);
    }

    public function test_member_cannot_access_another_members_account(): void
    {
        [$memberA, $memberAUser, $tokenA, $accountA] = $this->memberWithAccount(openingBalance: 100000);

        // Member B in a different organization has their own account; member A's
        // token must never resolve to it — the member portal is owner-scoped.
        $organizationB = Organization::factory()->create();
        $memberBUser = User::factory()->create(['organization_id' => $organizationB->id]);
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $organizationB->id,
            'user_id' => $memberBUser->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $this->app->make(StoreCreditLedgerService::class)->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $memberB->organization_id,
            cooperativeMemberId: (int) $memberB->id,
            openingBalance: 999999,
            openedBy: $memberBUser,
        ));

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertSuccessful()
            ->assertJsonPath('data.balance', 100000);

        unset($memberAUser, $memberBUser);
    }

    private function memberWithAccount(int $openingBalance = 0, int $creditLimit = 0): array
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $account = $this->app->make(StoreCreditLedgerService::class)->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            creditLimit: $creditLimit,
            openingBalance: $openingBalance,
            openedBy: $memberUser,
        ));

        $token = $memberUser->createToken('test', ['member:read', 'member:write'])->plainTextToken;

        return [$member, $memberUser, $token, $account];
    }
}
