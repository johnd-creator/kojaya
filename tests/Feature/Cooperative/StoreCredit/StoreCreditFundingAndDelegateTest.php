<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditFundingService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreCreditFundingAndDelegateTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_cash_funding_posts_immediately_and_credits_balance(): void
    {
        [$account, $cashier] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);

        $funding = $service->submitCashFunding($account, 500000, $cashier, 'CASH-001');

        $this->assertSame('approved', $funding->refresh()->status->value);
        $this->assertSame(500000, $account->refresh()->signedBalance());
        $this->assertNotNull($funding->posted_ledger_entry_id);
    }

    public function test_transfer_funding_stays_pending_and_does_not_change_balance(): void
    {
        [$account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);

        $funding = $service->submitTransferFunding($account, 300000, $submitter, 'BANK-REF');

        $this->assertSame('pending', $funding->refresh()->status->value);
        $this->assertSame(0, $account->refresh()->signedBalance());
        $this->assertNull($funding->posted_ledger_entry_id);
    }

    public function test_approved_transfer_credits_balance(): void
    {
        [$account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $funding = $service->submitTransferFunding($account, 300000, $submitter, 'BANK-REF');

        $reviewer = User::factory()->create(['organization_id' => $account->organization_id]);
        $service->approveTransfer($funding, $reviewer);

        $this->assertSame('approved', $funding->refresh()->status->value);
        $this->assertSame(300000, $account->refresh()->signedBalance());
    }

    public function test_rejected_transfer_does_not_change_balance(): void
    {
        [$account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $funding = $service->submitTransferFunding($account, 300000, $submitter, 'BANK-REF');

        $reviewer = User::factory()->create(['organization_id' => $account->organization_id]);
        $service->rejectTransfer($funding, $reviewer, 'Bukti tidak valid');

        $this->assertSame('rejected', $funding->refresh()->status->value);
        $this->assertSame(0, $account->refresh()->signedBalance());
    }

    public function test_submitter_cannot_approve_own_transfer(): void
    {
        [$account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $funding = $service->submitTransferFunding($account, 300000, $submitter, 'BANK-REF');

        $this->expectException(ValidationException::class);
        $service->approveTransfer($funding, $submitter);
    }

    public function test_approved_transfer_cannot_be_processed_again(): void
    {
        [$account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $funding = $service->submitTransferFunding($account, 300000, $submitter, 'BANK-REF');

        $reviewer = User::factory()->create(['organization_id' => $account->organization_id]);
        $service->approveTransfer($funding, $reviewer);

        $this->expectException(ValidationException::class);
        $service->approveTransfer($funding, $reviewer);
    }

    public function test_transfer_proof_is_stored_privately_and_path_not_exposed(): void
    {
        [$account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $proof = File::create('bukti.pdf', 100, 'application/pdf');

        $funding = $service->submitTransferFunding($account, 250000, $submitter, 'BANK', $proof);

        $this->assertNotEmpty($funding->getRawOriginal('proof_path'));
        Storage::disk('local')->assertExists($funding->getRawOriginal('proof_path'));
        $serialized = $funding->toArray();
        $this->assertArrayNotHasKey('proof_path', $serialized);
    }

    public function test_delegate_has_no_checkout_credential(): void
    {
        [$account, $creator] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditDelegateService::class);

        $delegate = $service->create($account, [
            'display_name' => 'Staff Toko',
        ], $creator);

        $this->assertArrayNotHasKey('pin_hash', $delegate->getAttributes());
    }

    public function test_revoked_delegate_cannot_pass_usability_check(): void
    {
        [$account, $creator] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditDelegateService::class);
        $delegate = $service->create($account, ['display_name' => 'Staff'], $creator);

        $service->revoke($delegate, $creator);

        $this->expectException(ValidationException::class);
        $service->assertUsableForPurchase($delegate->refresh(), 10000);
    }

    public function test_delegate_per_transaction_limit_enforced(): void
    {
        [$account, $creator] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditDelegateService::class);
        $delegate = $service->create($account, [
            'display_name' => 'Staff',
            'per_transaction_limit' => 50000,
        ], $creator);

        $this->expectException(ValidationException::class);
        $service->assertUsableForPurchase($delegate, 60000);
    }

    private function accountAndUser(): array
    {
        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $organization = Organization::factory()->create();
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $account = $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: $organization->id,
            cooperativeMemberId: $member->id,
            openedBy: $user,
        ));

        return [$account, $user];
    }
}
