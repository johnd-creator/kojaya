<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditFundingService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class StoreCreditProofFundingHardeningTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_authorized_reviewer_can_download_proof(): void
    {
        [$org, $reviewer, $funding] = $this->transferWithProof();
        $reviewer->givePermissionTo('approve_store_credit_transfer');

        $response = $this->actingAs($reviewer)
            ->getJson(route('cooperative.store-credit.transfers.proof', $funding->id));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_unauthorized_user_cannot_download_proof(): void
    {
        [, , $funding] = $this->transferWithProof();
        $user = User::factory()->create();
        $user->givePermissionTo('view_store_credit');

        $this->actingAs($user)
            ->getJson(route('cooperative.store-credit.transfers.proof', $funding->id))
            ->assertForbidden();
    }

    public function test_cross_organization_user_cannot_download_proof(): void
    {
        [$org, , $funding] = $this->transferWithProof();
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
        $otherUser->givePermissionTo('approve_store_credit_transfer');

        $this->actingAs($otherUser)
            ->getJson(route('cooperative.store-credit.transfers.proof', $funding->id))
            ->assertForbidden();
    }

    public function test_missing_proof_returns_safe_404(): void
    {
        [$org, $reviewer, $funding] = $this->transferWithoutProof();
        $reviewer->givePermissionTo('approve_store_credit_transfer');

        $this->actingAs($reviewer)
            ->getJson(route('cooperative.store-credit.transfers.proof', $funding->id))
            ->assertNotFound();
    }

    public function test_proof_download_does_not_leak_storage_path(): void
    {
        [$org, $reviewer, $funding] = $this->transferWithProof();
        $reviewer->givePermissionTo('approve_store_credit_transfer');

        $content = $this->actingAs($reviewer)
            ->get(route('cooperative.store-credit.transfers.proof', $funding->id))
            ->getContent();

        $this->assertStringNotContainsString('store-credit-proofs', (string) $content);
        $this->assertStringNotContainsString(storage_path(), (string) $content);
    }

    public function test_duplicate_cash_funding_with_same_key_posts_single_credit(): void
    {
        [$org, $account, $cashier] = $this->accountAndUser();
        $cashier->givePermissionTo('cashier_store_credit');
        $service = $this->app->make(StoreCreditFundingService::class);

        $first = $service->submitCashFunding($account, 500000, $cashier, 'REF-1', null, 'stable-key-1');
        $second = $service->submitCashFunding($account, 500000, $cashier, 'REF-1', null, 'stable-key-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, MemberStoreFundingRequest::query()->where('idempotency_key', 'funding:'.$account->id.':stable-key-1')->count());
        $this->assertSame(1, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->where('entry_type', 'cash_funding')->count());
        $this->assertSame(600000, $account->refresh()->signedBalance());
    }

    public function test_duplicate_transfer_funding_with_same_key_creates_single_request(): void
    {
        [$org, $account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);

        $first = $service->submitTransferFunding($account, 300000, $submitter, 'BANK', null, 'transfer-key-1');
        $second = $service->submitTransferFunding($account, 300000, $submitter, 'BANK', null, 'transfer-key-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, MemberStoreFundingRequest::query()->where('idempotency_key', 'funding:'.$account->id.':transfer-key-1')->count());
    }

    public function test_different_keys_create_separate_requests(): void
    {
        [$org, $account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);

        $first = $service->submitTransferFunding($account, 100000, $submitter, 'BANK', null, 'key-a');
        $second = $service->submitTransferFunding($account, 200000, $submitter, 'BANK', null, 'key-b');

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, MemberStoreFundingRequest::query()->where('account_id', $account->id)->count());
    }

    public function test_account_balance_is_not_mass_assignable(): void
    {
        $account = $this->accountFor();
        $originalBalance = $account->signedBalance();

        $account->fill(['balance' => 999999999]);
        $account->save();

        $this->assertSame($originalBalance, $account->refresh()->signedBalance(), 'Balance must never be mass-assignable from external input.');
    }

    public function test_posted_ledger_entry_cannot_be_updated(): void
    {
        $account = $this->accountFor();
        $entry = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->first();

        $entry->amount = 1;

        $this->expectException(RuntimeException::class);
        $entry->save();
    }

    public function test_posted_ledger_entry_cannot_be_deleted(): void
    {
        $account = $this->accountFor();
        $entry = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->first();

        $this->expectException(RuntimeException::class);
        $entry->delete();
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
            openingBalance: 100000,
            openedBy: $user,
        ));

        return [$organization, $account, $user];
    }

    private function accountFor()
    {
        return $this->accountAndUser()[1];
    }

    private function transferWithProof(): array
    {
        [$org, $account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $proof = File::create('bukti.pdf', 100, 'application/pdf');
        $funding = $service->submitTransferFunding($account, 250000, $submitter, 'BANK-REF', $proof);

        return [$org, $submitter, $funding->refresh()];
    }

    private function transferWithoutProof(): array
    {
        [$org, $account, $submitter] = $this->accountAndUser();
        $service = $this->app->make(StoreCreditFundingService::class);
        $funding = $service->submitTransferFunding($account, 250000, $submitter, 'BANK-REF');

        return [$org, $submitter, $funding->refresh()];
    }
}
