<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditAuthorizationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_can_view_own_account_summary(): void
    {
        [$user, $member, $account] = $this->memberWithAccount(300000);
        $token = $user->createToken('test', ['member:read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertSuccessful()
            ->assertJsonPath('data.balance', 300000);
    }

    public function test_member_without_account_receives_404(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $token = $user->createToken('test', ['member:read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertStatus(404);
    }

    public function test_inactive_member_cannot_access_store_account_api(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'INACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $token = $user->createToken('test', ['member:read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/member/store-account/summary')
            ->assertForbidden();
    }

    public function test_user_without_view_permission_is_denied_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('cooperative.store-credit.index'))->assertForbidden();
    }

    public function test_unauthorized_user_cannot_change_credit_limit(): void
    {
        [, , $account] = $this->memberWithAccount(0);
        $user = User::factory()->create(['organization_id' => $account->organization_id]);
        $user->givePermissionTo('view_store_credit');

        $this->actingAs($user)
            ->postJson(route('cooperative.store-credit.limit', $account->id), [
                'credit_limit' => 100000,
                'reason' => 'test',
            ])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_approve_transfer(): void
    {
        [$owner, $member, $account] = $this->memberWithAccount(0);
        $submitter = User::factory()->create(['organization_id' => $account->organization_id]);
        $funding = $this->app->make(\App\Services\Cooperative\StoreCreditFundingService::class)
            ->submitTransferFunding($account, 100000, $submitter, 'BANK');

        $user = User::factory()->create(['organization_id' => $account->organization_id]);
        $user->givePermissionTo('view_store_credit');

        $this->actingAs($user)
            ->postJson(route('cooperative.store-credit.transfers.process', $funding->id), ['decision' => 'approve'])
            ->assertForbidden();
    }

    public function test_cross_organization_admin_is_denied(): void
    {
        [, , $account] = $this->memberWithAccount(0);
        $otherOrg = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $otherOrg->id]);
        $user->givePermissionTo(['manage_store_credit', 'view_store_credit']);

        $this->actingAs($user)
            ->getJson(route('cooperative.store-credit.show', $account->id))
            ->assertForbidden();
    }

    public function test_admin_with_permission_can_view_index(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo('view_store_credit');

        $this->actingAs($user)->getJson(route('cooperative.store-credit.index'))->assertSuccessful();
    }

    private function memberWithAccount(int $openingBalance): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $ledger = $this->app->make(StoreCreditLedgerService::class);
        $account = $ledger->openAccount(new MemberStoreAccountContext(
            organizationId: $organization->id,
            cooperativeMemberId: $member->id,
            openingBalance: $openingBalance,
            openedBy: $user,
        ));

        return [$user, $member, $account];
    }
}
