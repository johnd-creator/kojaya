<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\MemberStoreAccount;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UiAuditSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_audit_seeder_is_repeatable_and_deterministic(): void
    {
        $this->seed(UiAuditSeeder::class);

        $firstCounts = [
            'users' => User::query()->where('email', 'like', 'ui.%@kojaya.test')->count(),
            'organizations' => Organization::query()->where('code', 'like', 'AUD-%')->count(),
            'members' => CooperativeMember::query()->where('member_no', 'like', 'AUD-%')->count(),
            'accounts' => MemberStoreAccount::query()->count(),
        ];

        $this->seed(UiAuditSeeder::class);

        $this->assertSame($firstCounts, [
            'users' => User::query()->where('email', 'like', 'ui.%@kojaya.test')->count(),
            'organizations' => Organization::query()->where('code', 'like', 'AUD-%')->count(),
            'members' => CooperativeMember::query()->where('member_no', 'like', 'AUD-%')->count(),
            'accounts' => MemberStoreAccount::query()->count(),
        ]);
        $this->assertDatabaseHas('users', ['email' => 'ui.pengurus@kojaya.test']);
        $this->assertDatabaseHas('member_store_accounts', ['balance' => -75000, 'status' => 'active']);
        $this->assertDatabaseHas('member_store_funding_requests', ['idempotency_key' => 'ui-audit-transfer-pending', 'status' => 'pending']);
    }

    public function test_ui_audit_roles_and_password_are_available(): void
    {
        $this->seed(UiAuditSeeder::class);

        $user = User::query()->where('email', 'ui.pengurus@kojaya.test')->firstOrFail();

        $this->assertTrue($user->hasRole('Pengurus Koperasi'));
        $this->assertTrue(Hash::check('UiAudit!2026', $user->password));
        $this->assertTrue(User::query()->where('email', 'ui.system@kojaya.test')->firstOrFail()->hasRole('System Admin'));
    }

    public function test_ui_audit_seeder_is_fail_closed_outside_test_environments(): void
    {
        foreach (['production', 'staging', 'local', 'development', 'qa'] as $environment) {
            config(['app.env' => $environment]);
            $thrown = false;

            try {
                $this->seed(UiAuditSeeder::class);
            } catch (\LogicException $exception) {
                $thrown = true;
                $this->assertStringContainsString('UiAuditSeeder is only available', $exception->getMessage());
            }

            $this->assertTrue($thrown, "UiAuditSeeder unexpectedly ran in {$environment}.");
            $this->assertDatabaseMissing('users', ['email' => 'ui.system@kojaya.test']);
        }

        config(['app.env' => 'testing']);
        $this->seed(UiAuditSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'ui.system@kojaya.test']);

        config(['app.env' => 'playwright']);
        $this->seed(UiAuditSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'ui.system@kojaya.test']);
        config(['app.env' => 'testing']);
    }

    public function test_playwright_environment_example_has_no_provider_secrets(): void
    {
        $contents = file_get_contents(base_path('.env.playwright.example'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_ENV=playwright', $contents);
        $this->assertStringContainsString('DB_DATABASE=database/playwright.sqlite', $contents);
        $this->assertStringContainsString('MIDTRANS_SERVER_KEY=', $contents);
        $this->assertStringNotContainsString('TdE4', $contents);
    }

    public function test_seeded_store_credit_local_and_global_reports_have_different_scope(): void
    {
        $this->seed(UiAuditSeeder::class);

        $manager = User::query()->where('email', 'ui.manajer@kojaya.test')->firstOrFail();
        $systemAdmin = User::query()->where('email', 'ui.system@kojaya.test')->firstOrFail();

        $this->actingAs($manager)
            ->get(route('cooperative.store-credit.report'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.organization_id', $manager->organization_id)
                ->where('summary.positive_account_count', 2)
                ->where('summary.negative_account_count', 1)
            );

        $this->actingAs($systemAdmin)
            ->get(route('cooperative.store-credit.report'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.organization_id', null)
                ->where('summary.positive_account_count', 4)
                ->where('summary.negative_account_count', 1)
            );
    }
}
