<?php

namespace Tests\Feature\Cooperative\StoreCredit;

use App\Models\AuditLog;
use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\MemberStoreLedgerEntry;
use App\Models\Organization;
use App\Models\User;
use App\Services\Cooperative\StoreCreditLedgerService;
use App\Support\MemberStoreAccountContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCreditOpenAccountTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_local_eligible_members_are_active_unaccounted_and_local(): void
    {
        $organization = $this->organization('LOCAL-01', 'Local Unit');
        $otherOrganization = $this->organization('OTHER-01', 'Other Unit');
        $admin = $this->admin($organization, ['manage_store_credit', 'view_store_credit']);
        $eligible = $this->member($organization, 'Local Eligible', '00123');
        $alreadyHasAccount = $this->member($organization, 'Already Open', '00124');
        $this->member($otherOrganization, 'Other Organization', '00999');
        $this->openAccount($alreadyHasAccount);

        $this->actingAs($admin)
            ->get(route('cooperative.store-credit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cooperative/StoreCredit/Index')
                ->where('canManage', true)
                ->has('eligibleMembers', 1)
                ->where('eligibleMembers.0.id', $eligible->id)
                ->where('eligibleMembers.0.organization_code', 'LOCAL-01')
                ->where('eligibleMembers.0.organization_name', 'Local Unit')
                ->where('eligibleMembers.0.member_no', '00123')
            );
    }

    public function test_user_without_manage_permission_receives_empty_eligible_members(): void
    {
        $organization = $this->organization('LOCAL-02', 'Local Unit 2');
        $user = $this->admin($organization, ['view_store_credit']);
        $this->member($organization, 'Visible Member', '00223');

        $this->actingAs($user)
            ->get(route('cooperative.store-credit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canManage', false)
                ->has('eligibleMembers', 0)
            );
    }

    public function test_global_store_credit_visibility_lists_members_from_multiple_organizations(): void
    {
        $organizationA = $this->organization('GLOBAL-01', 'Global Alpha');
        $organizationB = $this->organization('GLOBAL-02', 'Global Beta');
        $admin = $this->admin($organizationA, [
            'manage_store_credit',
            'view_store_credit',
            'view_store_credit_all',
        ]);
        $memberA = $this->member($organizationA, 'Alpha Member', '01001');
        $memberB = $this->member($organizationB, 'Beta Member', '02001');
        $accountedMember = $this->member($organizationB, 'Zeta Accounted', '02002');
        $this->openAccount($accountedMember);

        $this->actingAs($admin)
            ->get(route('cooperative.store-credit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('canManage', true)
                ->has('eligibleMembers', 2)
                ->where('eligibleMembers.0.id', $memberA->id)
                ->where('eligibleMembers.0.organization_id', $organizationA->id)
                ->where('eligibleMembers.0.organization_code', 'GLOBAL-01')
                ->where('eligibleMembers.1.id', $memberB->id)
                ->where('eligibleMembers.1.organization_id', $organizationB->id)
                ->where('eligibleMembers.1.organization_name', 'Global Beta')
            );
    }

    public function test_cooperative_global_permission_does_not_expand_store_credit_scope(): void
    {
        $organizationA = $this->organization('SCOPED-01', 'Scoped Alpha');
        $organizationB = $this->organization('SCOPED-02', 'Scoped Beta');
        $admin = $this->admin($organizationA, [
            'manage_store_credit',
            'view_store_credit',
            'view_cooperative_all',
        ]);
        $localMember = $this->member($organizationA, 'Local Member', '03001');
        $crossOrganizationMember = $this->member($organizationB, 'Cross Organization', '04001');

        $this->actingAs($admin)
            ->get(route('cooperative.store-credit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('eligibleMembers', 1)
                ->where('eligibleMembers.0.id', $localMember->id)
            );

        $this->actingAs($admin)
            ->postJson(route('cooperative.store-credit.store'), [
                'cooperative_member_id' => $crossOrganizationMember->id,
                'credit_limit' => 500000,
                'opening_balance' => 100000,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('member_store_accounts', [
            'cooperative_member_id' => $crossOrganizationMember->id,
        ]);
    }

    public function test_global_admin_can_open_account_with_limit_and_opening_balance_for_member_organization(): void
    {
        $organizationA = $this->organization('OPEN-01', 'Opening Alpha');
        $organizationB = $this->organization('OPEN-02', 'Opening Beta');
        $admin = $this->admin($organizationA, [
            'manage_store_credit',
            'view_store_credit',
            'view_store_credit_all',
        ]);
        $member = $this->member($organizationB, 'Opening Member', '05001');

        $response = $this->actingAs($admin)->post(route('cooperative.store-credit.store'), [
            'cooperative_member_id' => $member->id,
            'credit_limit' => 750000,
            'opening_balance' => 125000,
            'reason' => 'Saldo awal anggota',
        ]);

        $account = MemberStoreAccount::query()->where('cooperative_member_id', $member->id)->sole();

        $response
            ->assertRedirectToRoute('cooperative.store-credit.show', $account)
            ->assertSessionHas('success', 'Akun saldo toko anggota dibuka.');
        $this->assertSame($organizationB->id, $account->organization_id);
        $this->assertSame(750000, $account->credit_limit);
        $this->assertSame(125000, $account->balance);
        $this->assertDatabaseHas('member_store_ledger_entries', [
            'account_id' => $account->id,
            'entry_type' => 'opening_balance',
            'effect' => 'credit',
            'amount' => 125000,
            'balance_after' => 125000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member_store_credit.account.opened',
            'subject_id' => $account->id,
            'organization_id' => $organizationB->id,
        ]);
    }

    public function test_existing_account_returns_validation_error_without_mutations(): void
    {
        $organization = $this->organization('DUP-01', 'Duplicate Unit');
        $admin = $this->admin($organization, ['manage_store_credit', 'view_store_credit']);
        $member = $this->member($organization, 'Duplicate Member', '06001');
        $account = $this->openAccount($member, 100000, 250000);
        $ledgerCount = MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count();
        $auditCount = AuditLog::query()
            ->where('action', 'member_store_credit.account.opened')
            ->where('subject_id', $account->id)
            ->count();

        $this->actingAs($admin)
            ->postJson(route('cooperative.store-credit.store'), [
                'cooperative_member_id' => $member->id,
                'credit_limit' => 999999,
                'opening_balance' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'cooperative_member_id' => 'Anggota tersebut sudah memiliki akun Saldo Toko.',
            ]);

        $this->assertSame(250000, $account->refresh()->credit_limit);
        $this->assertSame(100000, $account->refresh()->balance);
        $this->assertSame($ledgerCount, MemberStoreLedgerEntry::query()->where('account_id', $account->id)->count());
        $this->assertSame($auditCount, AuditLog::query()
            ->where('action', 'member_store_credit.account.opened')
            ->where('subject_id', $account->id)
            ->count());
    }

    private function organization(string $code, string $name): Organization
    {
        return Organization::factory()->create([
            'code' => $code,
            'name' => $name,
        ]);
    }

    private function admin(Organization $organization, array $permissions): User
    {
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function member(Organization $organization, string $name, string $memberNo): CooperativeMember
    {
        return CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'name' => $name,
            'no_anggota' => $memberNo,
            'member_no' => $memberNo,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
        ]);
    }

    private function openAccount(CooperativeMember $member, int $openingBalance = 0, int $creditLimit = 0): MemberStoreAccount
    {
        return $this->app->make(StoreCreditLedgerService::class)->openAccount(new MemberStoreAccountContext(
            organizationId: (string) $member->organization_id,
            cooperativeMemberId: (int) $member->id,
            creditLimit: $creditLimit,
            openingBalance: $openingBalance,
            openedBy: User::factory()->create(['organization_id' => $member->organization_id]),
        ));
    }
}
