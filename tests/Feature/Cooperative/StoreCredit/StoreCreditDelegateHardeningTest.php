<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreCreditDelegateHardeningTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        RateLimiter::clear('delegate-pin:*');
    }

    public function test_owner_checkout_without_delegate_still_succeeds(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-OWNER-001',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        $this->assertSame(450000, $account->refresh()->signedBalance());
    }

    public function test_delegate_without_pin_is_rejected(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-DELEGATE-NO-PIN',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => $delegate->code,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);

        $this->assertSame(0, PosTransaction::query()->where('client_reference', 'SC-DELEGATE-NO-PIN')->count());
    }

    public function test_pin_without_delegate_is_rejected(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-PIN-NO-DELEGATE',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_pin' => '1234',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_wrong_pin_is_rejected_with_distinct_message(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $response = $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-WRONG-PIN',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => $delegate->code,
            'store_delegate_pin' => '9999',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('tidak sesuai', (string) $response->getContent());
        $this->assertStringNotContainsString('Terlalu banyak', (string) $response->getContent());
    }

    public function test_correct_pin_succeeds(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-CORRECT-PIN',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => $delegate->code,
            'store_delegate_pin' => '1234',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertSuccessful();

        $this->assertSame(450000, $account->refresh()->signedBalance());
    }

    public function test_correct_pin_does_not_consume_attempt_counter(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);
        $service = $this->app->make(StoreCreditDelegateService::class);

        RateLimiter::clear('delegate-pin:'.$delegate->id);

        $service->verifyForCheckout($delegate, '1234');
        $service->verifyForCheckout($delegate, '1234');
        $service->verifyForCheckout($delegate, '1234');

        $remaining = RateLimiter::remaining('delegate-pin:'.$delegate->id, 5);
        $this->assertSame(5, $remaining, 'Successful PIN verification must not consume attempt counter.');
    }

    public function test_revoked_delegate_is_rejected(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $this->app->make(StoreCreditDelegateService::class)->revoke($delegate, $cashier);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-REVOKED-DELEGATE',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => $delegate->code,
            'store_delegate_pin' => '1234',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_delegate_code_from_another_account_is_rejected(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        [, , , $otherAccount] = $this->checkoutFixture(100000);
        $otherDelegate = $this->createDelegate($otherAccount);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-CROSS-ACCOUNT-DELEGATE',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => $otherDelegate->code,
            'store_delegate_pin' => '1234',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_delegate_code_from_another_organization_is_rejected(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);

        $otherOrg = Organization::factory()->create();
        $otherMember = CooperativeMember::factory()->create([
            'organization_id' => $otherOrg->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $otherAccount = $this->openAccount($otherMember, 100000);
        $otherDelegate = $this->createDelegate($otherAccount);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-CROSS-ORG-DELEGATE',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => $otherDelegate->code,
            'store_delegate_pin' => '1234',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    public function test_rate_limiting_works_and_does_not_leak_pin(): void
    {
        [, , , $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);
        $service = $this->app->make(StoreCreditDelegateService::class);

        $rateLimited = false;
        $wrongPinSeen = false;

        for ($i = 0; $i < 6; $i++) {
            try {
                $service->verifyForCheckout($delegate, '0000');
            } catch (ValidationException $e) {
                $message = implode(' ', $e->validator->errors()->all());
                if (str_contains($message, 'Terlalu banyak')) {
                    $rateLimited = true;
                }
                if (str_contains($message, 'tidak sesuai')) {
                    $wrongPinSeen = true;
                }
            }
        }

        $this->assertTrue($wrongPinSeen, 'Wrong PIN must produce a distinct error.');
        $this->assertTrue($rateLimited, 'Excess attempts must be rate-limited.');
    }

    public function test_numeric_delegate_id_is_not_accepted(): void
    {
        [$cashier, $member, $product, $account] = $this->checkoutFixture(500000);
        $delegate = $this->createDelegate($account);

        $this->actingAs($cashier)->postJson(route('cooperative.pos.transactions.store'), [
            'client_reference' => 'SC-NUMERIC-ID',
            'cooperative_member_id' => $member->id,
            'payment_method' => 'MEMBER_STORE_ACCOUNT',
            'store_delegate_code' => (string) $delegate->id,
            'store_delegate_pin' => '1234',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422);
    }

    private function checkoutFixture(int $openingBalance): array
    {
        $organization = Organization::factory()->create();
        $cashier = User::factory()->create(['organization_id' => $organization->id]);
        $cashier->givePermissionTo(['access_cooperative_pos', 'cashier_store_credit', 'view_store_credit', 'manage_store_credit']);

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
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $opener = User::factory()->create(['organization_id' => $member->organization_id]);

        return $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            openingBalance: $openingBalance,
            openedBy: $opener,
        ));
    }

    private function createDelegate($account)
    {
        $service = $this->app->make(StoreCreditDelegateService::class);
        $creator = User::factory()->create(['organization_id' => $account->organization_id]);
        $creator->givePermissionTo('manage_store_credit');

        return $service->create($account, ['display_name' => 'Staff Toko', 'pin' => '1234'], $creator);
    }
}
