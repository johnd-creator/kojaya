<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\MemberStoreFundingRequest;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditPaginationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_ledger_per_page_is_clamped_between_1_and_100(): void
    {
        [$member, $memberUser, $token, $account] = $this->memberWithAccount(5);

        // per_page below the floor is clamped up to 1.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/ledger?per_page=0')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/ledger?per_page=-1')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');

        // per_page above the ceiling is clamped down to 100.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/ledger?per_page=101')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(5, 'data');

        // Default when the parameter is absent.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/ledger')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(5, 'data');

        unset($memberUser);
    }

    public function test_funding_requests_per_page_is_clamped_between_1_and_100(): void
    {
        [$member, $memberUser, $token, $account] = $this->memberWithAccount(0, fundingRequests: 5);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/funding-requests?per_page=0')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/funding-requests?per_page=101')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(5, 'data');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/funding-requests')
            ->assertSuccessful()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonCount(5, 'data');

        unset($memberUser);
    }

    private function memberWithAccount(int $ledgerEntries = 0, int $fundingRequests = 0): array
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
            openedBy: $memberUser,
        ));

        for ($i = 0; $i < $ledgerEntries; $i++) {
            MemberStoreLedgerEntry::query()->create([
                'account_id' => $account->id,
                'organization_id' => $member->organization_id,
                'entry_type' => 'adjustment_credit',
                'amount' => 10000,
                'effect' => 'credit',
                'balance_before' => 1000000 + $i * 10000,
                'balance_after' => 1010000 + $i * 10000,
                'reference_type' => null,
                'reference_id' => null,
                'idempotency_key' => "PAGINATE-LEDGER-{$account->id}-{$i}",
                'actor_user_id' => $memberUser->id,
                'occurred_at' => now(),
            ]);
        }

        for ($i = 0; $i < $fundingRequests; $i++) {
            MemberStoreFundingRequest::query()->create([
                'account_id' => $account->id,
                'organization_id' => $member->organization_id,
                'method' => 'transfer',
                'amount' => 50000,
                'status' => 'pending',
                'idempotency_key' => "PAGINATE-FUND-{$account->id}-{$i}",
                'submitted_by' => $memberUser->id,
            ]);
        }

        $token = $memberUser->createToken('test', ['member:read', 'member:write'])->plainTextToken;

        return [$member, $memberUser, $token, $account];
    }
}
