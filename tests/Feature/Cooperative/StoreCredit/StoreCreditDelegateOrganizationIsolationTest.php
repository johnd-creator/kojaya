<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\MemberStoreDelegate;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditDelegateService;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditDelegateOrganizationIsolationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_cannot_create_delegate_with_user_from_another_organization(): void
    {
        [$memberA, $memberAUser, $tokenA] = $this->activeMemberWithAccount(organizationLabel: 'A');
        $organizationB = Organization::factory()->create();
        $crossOrgUser = User::factory()->create(['organization_id' => $organizationB->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/v1/member/store-account/delegates', [
                'display_name' => 'Staff Lintas Organisasi',
                'user_id' => $crossOrgUser->id,
            ]);

        // 2 + 3: 422 with the validation error on user_id.
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
        $this->assertStringContainsString(
            'Pengguna delegate harus berada pada organisasi yang sama.',
            (string) $response->getContent(),
        );

        // 4: no delegate row is created for the caller's account.
        $this->assertSame(0, MemberStoreDelegate::query()
            ->where('user_id', $crossOrgUser->id)
            ->count());
        $this->assertSame(0, MemberStoreDelegate::query()
            ->where('display_name', 'Staff Lintas Organisasi')
            ->count());

        // No delegate.created audit event is recorded for the rejected attempt.
        $this->assertSame(0, AuditLog::query()
            ->where('action', 'member_store_credit.delegate.created')
            ->count());

        // 5: the same member can create a delegate for a user in the same organization.
        $sameOrgUser = User::factory()->create(['organization_id' => $memberA->organization_id]);

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/v1/member/store-account/delegates', [
                'display_name' => 'Staff Organisasi A',
                'user_id' => $sameOrgUser->id,
            ])->assertStatus(201);

        $this->assertSame(1, MemberStoreDelegate::query()
            ->where('user_id', $sameOrgUser->id)
            ->where('organization_id', $memberA->organization_id)
            ->count());

        unset($memberAUser);
    }

    public function test_member_can_create_delegate_without_user_id(): void
    {
        [$memberA, $memberAUser, $tokenA] = $this->activeMemberWithAccount(organizationLabel: 'B');

        // 6: a delegate without a user_id is still supported by the contract.
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/v1/member/store-account/delegates', [
                'display_name' => 'Delegate Tanpa User',
            ])->assertStatus(201);

        $delegate = MemberStoreDelegate::query()
            ->where('display_name', 'Delegate Tanpa User')
            ->firstOrFail();

        $this->assertNull($delegate->user_id);
        $this->assertSame((string) $memberA->organization_id, (string) $delegate->organization_id);

        unset($memberAUser);
    }

    public function test_delegate_update_and_revoke_are_owner_scoped(): void
    {
        [$memberA, $memberAUser, $tokenA] = $this->activeMemberWithAccount(organizationLabel: 'C');

        // A delegate owned by a different member/account. Created via the service
        // (under member B's account) so the caller's token is never member B's.
        $organizationB = Organization::factory()->create();
        $memberBUser = User::factory()->create(['organization_id' => $organizationB->id]);
        $memberB = CooperativeMember::factory()->create([
            'organization_id' => $organizationB->id,
            'user_id' => $memberBUser->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
        $accountB = $this->app->make(StoreCreditLedgerService::class)->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $memberB->organization_id,
            cooperativeMemberId: (int) $memberB->id,
            openedBy: $memberBUser,
        ));
        $managerB = User::factory()->create(['organization_id' => $organizationB->id]);
        $managerB->givePermissionTo('manage_store_credit');
        $delegateB = $this->app->make(StoreCreditDelegateService::class)
            ->create($accountB, ['display_name' => 'Delegate Milik B'], $managerB);

        // Member A cannot touch member B's delegate — owner-scoped 404.
        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->putJson('/api/v1/member/store-account/delegates/'.$delegateB->id, [
                'display_name' => 'Hijack',
            ])->assertNotFound();

        $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson('/api/v1/member/store-account/delegates/'.$delegateB->id.'/revoke')
            ->assertNotFound();

        $this->assertSame(
            'active',
            MemberStoreDelegate::query()->whereKey($delegateB->id)->firstOrFail()->status->value,
            'A foreign delegate must not be revoked or renamed by a non-owner.',
        );

        unset($memberAUser);
    }

    private function activeMemberWithAccount(string $organizationLabel): array
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $member = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);

        $this->app->make(StoreCreditLedgerService::class)->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            openedBy: $memberUser,
        ));

        $token = $memberUser->createToken('test', ['member:read', 'member:write'])->plainTextToken;

        return [$member, $memberUser, $token];
    }
}
