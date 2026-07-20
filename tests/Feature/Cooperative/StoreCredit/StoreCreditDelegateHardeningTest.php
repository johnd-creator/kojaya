<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreLedgerEntry;
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

class StoreCreditDelegateHardeningTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_can_charge_without_pin_and_records_attribution(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-NO-PIN',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Budi Staff',
            'purchase_note' => 'Diambil untuk tim gudang',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        $entry = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->firstOrFail();

        $this->assertSame('Budi Staff', $entry->purchaser_name);
        $this->assertSame('Diambil untuk tim gudang', $entry->purchase_note);
        $this->assertSame($cashier->id, $entry->actor_user_id);
        $this->assertNotNull($entry->transaction_no);
        $this->assertNotNull($entry->occurred_at);
    }

    public function test_purchaser_name_is_required_for_store_account_checkout(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-MISSING-PURCHASER',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('purchaser_name');

        $this->assertSame(500000, $account->refresh()->signedBalance());
        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'SC-MISSING-PURCHASER')->count());
    }

    public function test_registered_delegate_is_optional_reference_without_pin(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-DELEGATE-NO-PIN',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Staff Terdaftar',
            'store_delegate_code' => $delegate->code,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        $this->assertSame($delegate->id, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->value('delegate_id'));
    }

    public function test_cashier_without_store_credit_permission_is_rejected(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $cashier->revokePermissionTo('cashier_store_credit');

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-UNAUTHORIZED',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Pembeli',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertSame(500000, $account->refresh()->signedBalance());
    }

    public function test_purchaser_snapshot_survives_delegate_change_and_revoke(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-SNAPSHOT',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'purchaser_name' => 'Nama Saat Belanja',
            'store_delegate_code' => $delegate->code,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        $delegate->update(['display_name' => 'Nama Baru']);
        $this->app->make(StoreCreditDelegateService::class)->revoke($delegate->refresh(), $cashier);

        $entry = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'pos_purchase')->firstOrFail();
        $this->assertSame('Nama Saat Belanja', $entry->purchaser_name);
    }

    private function checkoutFixture(int $openingBalance): array
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
        $account = $this->openAccount($member, $openingBalance);

        return [$cashier, $member, $product, $account];
    }

    private function openAccount(CooperativeMember $member, int $openingBalance)
    {
        return $this->app->make(StoreCreditLedgerService::class)->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            openingBalance: $openingBalance,
            openedBy: User::factory()->create(['organization_id' => $member->organization_id]),
        ));
    }

    private function createDelegate($account)
    {
        $creator = User::factory()->create(['organization_id' => $account->organization_id]);
        $creator->givePermissionTo('manage_store_credit');

        return $this->app->make(StoreCreditDelegateService::class)->create($account, ['display_name' => 'Staff Toko'], $creator);
    }
}
