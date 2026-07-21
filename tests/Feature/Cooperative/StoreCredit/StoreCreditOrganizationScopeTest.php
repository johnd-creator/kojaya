<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreDelegate;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditOrganizationScopeTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_cannot_open_account_for_member_of_another_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $orgA->id]);
        $admin->givePermissionTo(['manage_store_credit', 'view_store_credit']);
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $this->actingAs($admin)->postJson(route('cooperative.store-credit.store'), [
            'cooperative_member_id' => $memberB->id,
            'credit_limit' => 0,
            'opening_balance' => 0,
        ])->assertStatus(404);

        $this->assertDatabaseMissing('member_store_accounts', ['cooperative_member_id' => $memberB->id]);
    }

    public function test_cashier_cannot_debit_member_of_another_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $cashierA = User::factory()->create(['organization_id' => $orgA->id]);
        $cashierA->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit']);

        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $accountB = $this->openAccount($memberB, 500000);

        $product = PosProduct::factory()->create([
            'cost_price' => 1000, 'sale_price' => 50000, 'stock' => 10, 'is_active' => true,
        ]);

        $this->actingAs($cashierA)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-CROSS-ORG-DEBIT',
            'cooperative_member_id' => $memberB->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertSame(500000, $accountB->refresh()->signedBalance());
        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'SC-CROSS-ORG-DEBIT')->count());
        $this->assertSame(10, (int) $product->refresh()->stock);
    }

    public function test_nested_delegate_update_url_cannot_target_another_account(): void
    {
        [$orgA, $adminA, $memberA, $accountA] = $this->orgWithAccount(500000);
        [, , , $accountB] = $this->orgWithAccount(500000);

        $delegateService = $this->app->make(StoreCreditDelegateService::class);
        $creatorB = User::factory()->create(['organization_id' => $accountB->organization_id]);
        $delegateB = $delegateService->create($accountB, ['display_name' => 'Staff B'], $creatorB);

        $this->actingAs($adminA)
            ->putJson(route('cooperative.store-credit.delegates.update', [$accountA->id, $delegateB->id]), [
                'display_name' => 'Hijacked',
            ])
            ->assertStatus(404);

        $this->assertSame('Staff B', $delegateB->refresh()->display_name);
    }

    public function test_nested_delegate_revoke_url_cannot_target_another_account(): void
    {
        [$orgA, $adminA, $memberA, $accountA] = $this->orgWithAccount(500000);
        [, , , $accountB] = $this->orgWithAccount(500000);

        $delegateService = $this->app->make(StoreCreditDelegateService::class);
        $creatorB = User::factory()->create(['organization_id' => $accountB->organization_id]);
        $delegateB = $delegateService->create($accountB, ['display_name' => 'Staff B'], $creatorB);

        $this->actingAs($adminA)
            ->postJson(route('cooperative.store-credit.delegates.revoke', [$accountA->id, $delegateB->id]))
            ->assertStatus(404);

        $this->assertNull(MemberStoreDelegate::query()->where('id', $delegateB->id)->value('revoked_at'));
    }

    public function test_cross_organization_delegate_linkage_for_member_is_scoped(): void
    {
        [$orgA, $adminA, $memberA, $accountA] = $this->orgWithAccount(0);
        $orgB = Organization::factory()->create();
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        // Attempting to link a delegate to a user from another organization is denied at creation.
        $this->actingAs($adminA)
            ->postJson(route('cooperative.store-credit.delegates.store', $accountA->id), [
                'display_name' => 'Cross Org',
                'user_id' => $userB->id,
            ])
            ->assertStatus(422);
    }

    public function test_rejected_request_creates_no_ledger_or_pos_rows(): void
    {
        $orgA = Organization::factory()->create();
        $cashierA = User::factory()->create(['organization_id' => $orgA->id]);
        $cashierA->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit']);

        $orgB = Organization::factory()->create();
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $orgB->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $accountB = $this->openAccount($memberB, 500000);

        $product = PosProduct::factory()->create([
            'cost_price' => 1000, 'sale_price' => 50000, 'stock' => 10, 'is_active' => true,
        ]);

        $this->actingAs($cashierA)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-NO-ROWS',
            'cooperative_member_id' => $memberB->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertSame(0, \App\Models\MemberStoreLedgerEntry::query()->where('account_id', $accountB->id)->where('entry_type', 'pos_purchase')->count());
        $this->assertSame(500000, $accountB->refresh()->signedBalance());
    }

    private function orgWithAccount(int $openingBalance): array
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['organization_id' => $organization->id]);
        $admin->givePermissionTo(['manage_store_credit', 'view_store_credit', 'cashier_store_credit', 'access_cooperative_pos']);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $account = $this->openAccount($member, $openingBalance);

        return [$organization, $admin, $member, $account];
    }

    private function openAccount(CooperativeMember $member, int $openingBalance)
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $opener = User::factory()->create(['organization_id' => $member->organization_id]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            openingBalance: $openingBalance,
            openedBy: $opener,
        ));
    }
}
