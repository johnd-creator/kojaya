<?php

namespace Tests\Feature\Security;

use App\Enums\PermissionEnum;
use App\Models\BankTransferBatch;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BankBatchPermissionCutoverTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $financeUnitA;

    private User $financePusat;

    private User $systemAdmin;

    private User $adminPusat;

    private BankTransferBatch $batchA;

    private BankTransferBatch $batchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::factory()->create(['name' => 'Koperasi Unit A']);
        $this->orgB = Organization::factory()->create(['name' => 'Koperasi Unit B']);

        $this->financeUnitA = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Finance Unit User A',
        ]);
        $this->financeUnitA->assignRole('Finance Unit');

        $this->financePusat = User::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Finance Pusat User',
        ]);
        $this->financePusat->assignRole('Finance Pusat');

        $this->systemAdmin = User::factory()->create([
            'organization_id' => null,
            'name' => 'System Admin User',
        ]);
        $this->systemAdmin->assignRole('System Admin');

        $this->adminPusat = User::factory()->create([
            'organization_id' => null,
            'name' => 'Admin Pusat User',
        ]);
        $this->adminPusat->assignRole('Admin Pusat');

        $this->batchA = BankTransferBatch::create([
            'organization_id' => $this->orgA->id,
            'bank_name' => 'BCA',
            'account_number' => '11110000',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $this->batchB = BankTransferBatch::create([
            'organization_id' => $this->orgB->id,
            'bank_name' => 'Mandiri',
            'account_number' => '22220000',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
    }

    /**
     * Simulate a pre-R1 production database state where:
     * - manage_bank_batch exists
     * - manage_bank_reconciliation exists
     * - view_invoice_all exists
     * - view_bank_batch_all DOES NOT exist
     * - Finance Pusat and Finance Unit only have their pre-R1 permissions
     */
    private function establishPreCutoverState(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        $viewAll = Permission::where([
            'name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value,
            'guard_name' => $guard,
        ])->first();

        if ($viewAll) {
            foreach (Role::all() as $role) {
                if ($this->roleHasPermission($role, $viewAll->name)) {
                    $role->revokePermissionTo($viewAll);
                }
            }
            $viewAll->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function roleHasPermission(Role $role, string $permissionName): bool
    {
        try {
            return $role->hasPermissionTo($permissionName);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    private function runCutoverMigration(): void
    {
        $migration = require database_path('migrations/2026_09_11_193000_cutover_bank_batch_global_permission.php');
        $migration->up();
    }

    private function rollbackCutoverMigration(): void
    {
        $migration = require database_path('migrations/2026_09_11_193000_cutover_bank_batch_global_permission.php');
        $migration->down();
    }

    /**
     * Test Section 16 Matrix (01-15):
     * Verifies pre-cutover state, cutover migration execution, post-cutover role states,
     * preservation of unrelated permissions, cache invalidation, and idempotency.
     */
    public function test_cutover_migration_matrix_01_to_15(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $this->establishPreCutoverState();

        // 01: view_bank_batch_all absent before cutover
        $this->assertFalse(
            Permission::where(['name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value, 'guard_name' => $guard])->exists(),
            '01: view_bank_batch_all must be absent before cutover'
        );

        $financePusatRole = Role::where(['name' => 'Finance Pusat', 'guard_name' => $guard])->firstOrFail();
        $financeUnitRole = Role::where(['name' => 'Finance Unit', 'guard_name' => $guard])->firstOrFail();
        $systemAdminRole = Role::where(['name' => 'System Admin', 'guard_name' => $guard])->firstOrFail();
        $adminPusatRole = Role::where(['name' => 'Admin Pusat', 'guard_name' => $guard])->firstOrFail();

        // Capture unrelated permissions before cutover
        $financePusatPermsBefore = $financePusatRole->permissions->pluck('name')->sort()->values()->all();
        $financeUnitPermsBefore = $financeUnitRole->permissions->pluck('name')->sort()->values()->all();
        $systemAdminPermsBefore = $systemAdminRole->permissions->pluck('name')->sort()->values()->all();
        $adminPusatPermsBefore = $adminPusatRole->permissions->pluck('name')->sort()->values()->all();

        // 02: Finance Pusat lacks it before cutover
        $this->assertFalse(
            $this->roleHasPermission($financePusatRole, PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '02: Finance Pusat must lack view_bank_batch_all before cutover'
        );

        // 03: Finance Unit lacks it before cutover
        $this->assertFalse(
            $this->roleHasPermission($financeUnitRole, PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '03: Finance Unit must lack view_bank_batch_all before cutover'
        );

        // 04: Run migration / cutover
        $this->runCutoverMigration();

        // 05: Permission exists afterward
        $this->assertTrue(
            Permission::where(['name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value, 'guard_name' => $guard])->exists(),
            '05: view_bank_batch_all must exist after cutover'
        );

        // Reload roles
        $financePusatRole->refresh();
        $financeUnitRole->refresh();
        $systemAdminRole->refresh();
        $adminPusatRole->refresh();

        // 06: Finance Pusat has it afterward
        $this->assertTrue(
            $financePusatRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '06: Finance Pusat must have view_bank_batch_all after cutover'
        );

        // 07: Finance Unit does NOT have it afterward
        $this->assertFalse(
            $financeUnitRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '07: Finance Unit must NOT have view_bank_batch_all after cutover'
        );

        // 08: System Admin has it afterward
        $this->assertTrue(
            $systemAdminRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '08: System Admin must have view_bank_batch_all after cutover'
        );

        // 09: Admin Pusat has it afterward
        $this->assertTrue(
            $adminPusatRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '09: Admin Pusat must have view_bank_batch_all after cutover'
        );

        // 10: Unrelated Finance Pusat permissions preserved
        $financePusatPermsAfter = $financePusatRole->permissions
            ->reject(fn ($p) => $p->name === PermissionEnum::BANK_BATCH_VIEW_ALL->value)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
        $this->assertEquals($financePusatPermsBefore, $financePusatPermsAfter, '10: Unrelated Finance Pusat permissions must be preserved');
        $this->assertTrue($financePusatRole->hasPermissionTo('manage_bank_batch'));
        $this->assertTrue($financePusatRole->hasPermissionTo('manage_bank_reconciliation'));
        $this->assertTrue($financePusatRole->hasPermissionTo('view_invoice_all'));

        // 11: Unrelated Finance Unit permissions preserved
        $financeUnitPermsAfter = $financeUnitRole->permissions->pluck('name')->sort()->values()->all();
        $this->assertEquals($financeUnitPermsBefore, $financeUnitPermsAfter, '11: Unrelated Finance Unit permissions must be preserved');
        $this->assertTrue($financeUnitRole->hasPermissionTo('manage_bank_batch'));
        $this->assertTrue($financeUnitRole->hasPermissionTo('manage_bank_reconciliation'));

        // 12: Unrelated System Admin permissions preserved
        $systemAdminPermsAfter = $systemAdminRole->permissions
            ->reject(fn ($p) => $p->name === PermissionEnum::BANK_BATCH_VIEW_ALL->value)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
        $this->assertEquals($systemAdminPermsBefore, $systemAdminPermsAfter, '12: Unrelated System Admin permissions must be preserved');

        // 13: Unrelated Admin Pusat permissions preserved
        $adminPusatPermsAfter = $adminPusatRole->permissions
            ->reject(fn ($p) => $p->name === PermissionEnum::BANK_BATCH_VIEW_ALL->value)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();
        $this->assertEquals($adminPusatPermsBefore, $adminPusatPermsAfter, '13: Unrelated Admin Pusat permissions must be preserved');

        // 14: Permission cache sees post-cutover state on user instances
        $this->assertTrue(
            $this->financePusat->can(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '14: Finance Pusat user must evaluate can(view_bank_batch_all) as true via cache'
        );
        $this->assertFalse(
            $this->financeUnitA->can(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            '14: Finance Unit user must evaluate can(view_bank_batch_all) as false via cache'
        );

        // 15: Equivalent rerun is idempotent
        $this->runCutoverMigration();
        $financePusatRole->refresh();
        $financeUnitRole->refresh();
        $this->assertTrue($financePusatRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value));
        $this->assertFalse($financeUnitRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value));
    }

    /**
     * Test Section 17: Live authorization after cutover.
     * Proves that the upgraded database state produces the exact expected HTTP behaviors.
     */
    public function test_live_authorization_after_cutover(): void
    {
        $this->establishPreCutoverState();
        $this->runCutoverMigration();

        // Finance Unit A: GET /finance/bank-batches => Org A only
        $respUnitIndex = $this->actingAs($this->financeUnitA)->get(route('finance.bank-batches.index'));
        $respUnitIndex->assertOk();
        $batchesUnit = collect($respUnitIndex->viewData('page')['props']['batches'])->pluck('id')->all();
        $this->assertContains($this->batchA->id, $batchesUnit);
        $this->assertNotContains($this->batchB->id, $batchesUnit);

        // Finance Unit A: export Batch B => 404
        $respUnitExport = $this->actingAs($this->financeUnitA)->get(route('finance.bank-batches.export', $this->batchB->id));
        $respUnitExport->assertNotFound();

        // Finance Unit A: reconciliation show Batch B => 404 / blocked
        $respUnitRecon = $this->actingAs($this->financeUnitA)->get(route('finance.bank-reconciliation.show', $this->batchB->id));
        $respUnitRecon->assertNotFound();

        // Finance Pusat: GET /finance/bank-batches => Org A + Org B
        $respPusatIndex = $this->actingAs($this->financePusat)->get(route('finance.bank-batches.index'));
        $respPusatIndex->assertOk();
        $batchesPusat = collect($respPusatIndex->viewData('page')['props']['batches'])->pluck('id')->all();
        $this->assertContains($this->batchA->id, $batchesPusat);
        $this->assertContains($this->batchB->id, $batchesPusat);

        // Finance Pusat: export Batch B => 200
        $respPusatExport = $this->actingAs($this->financePusat)->get(route('finance.bank-batches.export', $this->batchB->id));
        $respPusatExport->assertOk();

        // Finance Pusat: reconciliation show Batch B => 200 / allowed
        $respPusatRecon = $this->actingAs($this->financePusat)->get(route('finance.bank-reconciliation.show', $this->batchB->id));
        $respPusatRecon->assertOk();
    }

    /**
     * Test Section 18: Stale Finance Unit grant calibration.
     * Proves that if Finance Unit accidentally possessed view_bank_batch_all before cutover,
     * the migration actively revokes it and restores strict tenant isolation.
     */
    public function test_stale_finance_unit_grant_is_revoked_by_cutover(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Ensure permission exists
        $viewAll = Permission::firstOrCreate([
            'name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value,
            'guard_name' => $guard,
        ]);

        $financeUnitRole = Role::where(['name' => 'Finance Unit', 'guard_name' => $guard])->firstOrFail();

        // Intentionally corrupt role state by giving view_bank_batch_all to Finance Unit
        $financeUnitRole->givePermissionTo($viewAll);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(
            $financeUnitRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            'Precondition: Finance Unit has stale grant before cutover'
        );

        // Run cutover migration
        $this->runCutoverMigration();

        $financeUnitRole->refresh();

        // Assert stale grant was actively removed
        $this->assertFalse(
            $financeUnitRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            'Postcondition: Stale grant must be revoked from Finance Unit by cutover'
        );

        // Live request: Finance Unit cannot export foreign batch
        $response = $this->actingAs($this->financeUnitA)->get(route('finance.bank-batches.export', $this->batchB->id));
        $response->assertNotFound();
    }

    /**
     * Test Section 13: Rollback security semantics.
     * Rollback must NOT grant view_bank_batch_all to Finance Unit.
     */
    public function test_rollback_security_semantics(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $this->runCutoverMigration();

        $financePusatRole = Role::where(['name' => 'Finance Pusat', 'guard_name' => $guard])->firstOrFail();
        $financeUnitRole = Role::where(['name' => 'Finance Unit', 'guard_name' => $guard])->firstOrFail();

        $this->assertTrue($financePusatRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value));
        $this->assertFalse($financeUnitRole->hasPermissionTo(PermissionEnum::BANK_BATCH_VIEW_ALL->value));

        // Execute rollback
        $this->rollbackCutoverMigration();

        $financePusatRole->refresh();
        $financeUnitRole->refresh();

        // Critical invariant: Finance Unit NEVER gains view_bank_batch_all during rollback
        $this->assertFalse(
            $this->roleHasPermission($financeUnitRole, PermissionEnum::BANK_BATCH_VIEW_ALL->value),
            'Critical invariant: Finance Unit must never gain view_bank_batch_all during rollback'
        );

        // Finance Pusat no longer has it
        $this->assertFalse($this->roleHasPermission($financePusatRole, PermissionEnum::BANK_BATCH_VIEW_ALL->value));

        // Permission record was cleaned up
        $this->assertFalse(
            Permission::where(['name' => PermissionEnum::BANK_BATCH_VIEW_ALL->value, 'guard_name' => $guard])->exists()
        );
    }
}
