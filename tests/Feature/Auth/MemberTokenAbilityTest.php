<?php

namespace Tests\Feature\Auth;

use App\Models\CooperativeMember;
use App\Models\User;
use App\Services\Auth\TokenAbilityResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTokenAbilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_system_admin_member_app_does_not_get_wildcard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $abilities = app(TokenAbilityResolver::class)->for($admin, 'member');

        $this->assertNotContains('*', $abilities);
    }

    public function test_system_admin_admin_app_does_not_get_wildcard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $abilities = app(TokenAbilityResolver::class)->for($admin, 'admin');

        $this->assertNotContains('*', $abilities);
        $this->assertContains('profile:read', $abilities);
    }

    public function test_system_admin_null_app_does_not_get_wildcard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('System Admin');

        $abilities = app(TokenAbilityResolver::class)->for($admin, null);

        $this->assertNotContains('*', $abilities);
    }

    public function test_abilities_are_deduplicated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $abilities = app(TokenAbilityResolver::class)->for($user, null);

        $this->assertSame(count($abilities), count(array_unique($abilities)));
    }

    public function test_anggota_member_app_gets_member_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        $abilities = app(TokenAbilityResolver::class)->for($user, 'member');

        $this->assertContains('profile:read', $abilities);
        $this->assertContains('member:read', $abilities);
        $this->assertContains('member:write', $abilities);
    }

    public function test_anggota_member_app_does_not_get_cooperative_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        $abilities = app(TokenAbilityResolver::class)->for($user, 'member');

        $this->assertNotContains('cooperative:read', $abilities);
        $this->assertNotContains('cooperative:write', $abilities);
    }

    public function test_user_without_cooperative_member_does_not_get_member_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Anggota');

        $abilities = app(TokenAbilityResolver::class)->for($user, 'member');

        $this->assertNotContains('member:read', $abilities);
        $this->assertNotContains('member:write', $abilities);
    }

    public function test_kasir_koperasi_admin_app_gets_cooperative_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, 'admin');

        $this->assertContains('cooperative:read', $abilities);
        $this->assertContains('cooperative:write', $abilities);
        $this->assertContains('pos:read', $abilities);
        $this->assertContains('pos:write', $abilities);
    }

    public function test_kasir_koperasi_member_app_does_not_get_cooperative_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, 'member');

        $this->assertNotContains('cooperative:read', $abilities);
        $this->assertNotContains('cooperative:write', $abilities);
    }

    public function test_pengurus_gets_granular_member_abilities(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Pengurus Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, null);

        $this->assertContains('cooperative.member.read', $abilities);
        $this->assertContains('cooperative.member.write', $abilities);
        $this->assertContains('cooperative.member.verify', $abilities);
        $this->assertContains('cooperative.member.approve', $abilities);
        $this->assertContains('cooperative.member.export', $abilities);
        $this->assertContains('cooperative.resignation.review', $abilities);
    }

    public function test_manajer_gets_loan_review_ability(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Manajer Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, null);

        $this->assertContains('cooperative.loan.read', $abilities);
        $this->assertContains('cooperative.loan.write', $abilities);
        $this->assertContains('cooperative.loan.review', $abilities);
        $this->assertNotContains('cooperative.loan.approve', $abilities);
    }

    public function test_kasir_gets_payment_abilities_not_loan_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, null);

        $this->assertContains('cooperative.payment.read', $abilities);
        $this->assertContains('cooperative.payment.record', $abilities);
        $this->assertNotContains('cooperative.loan.write', $abilities);
        $this->assertNotContains('cooperative.loan.review', $abilities);
        $this->assertNotContains('cooperative.loan.approve', $abilities);
        $this->assertNotContains('cooperative.member.write', $abilities);
        $this->assertNotContains('cooperative.member.export', $abilities);
    }

    public function test_admin_koperasi_does_not_get_loan_review_or_approve(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, null);

        $this->assertNotContains('cooperative.loan.review', $abilities);
        $this->assertNotContains('cooperative.loan.approve', $abilities);
    }

    public function test_granular_and_legacy_coexist(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $abilities = app(TokenAbilityResolver::class)->for($user, null);

        $this->assertContains('cooperative:read', $abilities);
        $this->assertContains('cooperative:write', $abilities);
        $this->assertContains('cooperative.member.read', $abilities);
        $this->assertContains('cooperative.member.write', $abilities);
    }

    public function test_hr_user_gets_employee_document_abilities(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view_employee_all', 'edit_employee']);

        $abilities = app(TokenAbilityResolver::class)->for($user, 'admin');

        $this->assertContains('employee-documents:read', $abilities);
        $this->assertContains('employee-documents:write', $abilities);
    }

    public function test_employee_document_abilities_are_scoped_by_permission(): void
    {
        $readOnlyUser = User::factory()->create();
        $readOnlyUser->givePermissionTo('view_employee_unit');

        $readAbilities = app(TokenAbilityResolver::class)->for($readOnlyUser, 'admin');
        $this->assertContains('employee-documents:read', $readAbilities);
        $this->assertNotContains('employee-documents:write', $readAbilities);

        $editUser = User::factory()->create();
        $editUser->givePermissionTo('edit_employee');

        $editAbilities = app(TokenAbilityResolver::class)->for($editUser, 'admin');
        $this->assertNotContains('employee-documents:read', $editAbilities);
        $this->assertContains('employee-documents:write', $editAbilities);
    }

    public function test_create_or_delete_employee_permissions_alone_do_not_grant_employee_documents_write(): void
    {
        $createUserOnly = User::factory()->create();
        $createUserOnly->givePermissionTo('create_employee');

        $createAbilities = app(TokenAbilityResolver::class)->for($createUserOnly, 'admin');
        $this->assertNotContains('employee-documents:write', $createAbilities);
        $this->assertNotContains('employee-documents:read', $createAbilities);

        $deleteUserOnly = User::factory()->create();
        $deleteUserOnly->givePermissionTo('delete_employee');

        $deleteAbilities = app(TokenAbilityResolver::class)->for($deleteUserOnly, 'admin');
        $this->assertNotContains('employee-documents:write', $deleteAbilities);
        $this->assertNotContains('employee-documents:read', $deleteAbilities);
    }

    public function test_non_admin_token_apps_do_not_receive_employee_document_abilities(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view_employee_all', 'edit_employee']);
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        $memberAbilities = app(TokenAbilityResolver::class)->for($user, 'member');
        $this->assertNotContains('employee-documents:read', $memberAbilities);
        $this->assertNotContains('employee-documents:write', $memberAbilities);

        $essAbilities = app(TokenAbilityResolver::class)->for($user, 'ess');
        $this->assertNotContains('employee-documents:read', $essAbilities);
        $this->assertNotContains('employee-documents:write', $essAbilities);

        $techAbilities = app(TokenAbilityResolver::class)->for($user, 'technician');
        $this->assertNotContains('employee-documents:read', $techAbilities);
        $this->assertNotContains('employee-documents:write', $techAbilities);
    }
}
