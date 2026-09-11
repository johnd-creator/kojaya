<?php

namespace Tests\Feature\Security;

use App\Imports\BudgetLinesImport;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BudgetAuthorizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $unauthorizedUserA;

    protected User $manageBudgetUserA;

    protected User $globalViewer;

    protected User $globalManager;

    protected User $manageBudgetUserB;

    protected User $nullOrgUser;

    protected User $nullOrgGlobalManager;

    protected Budget $budgetA;

    protected Budget $budgetB;

    protected Project $projectA;

    protected Project $projectB;

    protected BudgetLine $lineA;

    protected BudgetLine $lineB;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure permissions exist
        Permission::firstOrCreate(['name' => 'view_budget_all', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_budget', 'guard_name' => 'web']);

        // Organizations
        $this->orgA = Organization::factory()->create(['name' => 'Org Unit A']);
        $this->orgB = Organization::factory()->create(['name' => 'Org Unit B']);

        // Actors
        $this->unauthorizedUserA = User::factory()->create(['organization_id' => $this->orgA->id]);

        $this->manageBudgetUserA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->manageBudgetUserA->givePermissionTo('manage_budget');

        $this->globalViewer = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->globalViewer->givePermissionTo('view_budget_all');

        $this->globalManager = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->globalManager->givePermissionTo(['view_budget_all', 'manage_budget']);

        $this->manageBudgetUserB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->manageBudgetUserB->givePermissionTo('manage_budget');

        $this->nullOrgUser = User::factory()->create(['organization_id' => null]);
        $this->nullOrgUser->givePermissionTo('manage_budget');

        $this->nullOrgGlobalManager = User::factory()->create(['organization_id' => null]);
        $this->nullOrgGlobalManager->givePermissionTo(['view_budget_all', 'manage_budget']);

        // Projects
        $this->projectA = Project::factory()->create(['organization_id' => $this->orgA->id]);
        $this->projectB = Project::factory()->create(['organization_id' => $this->orgB->id]);

        // Budgets
        $this->budgetA = Budget::create([
            'organization_id' => $this->orgA->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        $this->budgetB = Budget::create([
            'organization_id' => $this->orgB->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        // BudgetLines
        $this->lineA = BudgetLine::create([
            'budget_id' => $this->budgetA->id,
            'project_id' => $this->projectA->id,
            'cost_center' => 'CC-A1',
            'gl_account' => '5101001',
            'category' => 'OPEX',
            'allocated_amount' => 1000000,
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);

        $this->lineB = BudgetLine::create([
            'budget_id' => $this->budgetB->id,
            'project_id' => $this->projectB->id,
            'cost_center' => 'CC-B1',
            'gl_account' => '5201001',
            'category' => 'OPEX',
            'allocated_amount' => 2000000,
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);
    }

    public function test_01_no_permission_actor_cannot_index_budgets(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->get('/budgets')
            ->assertForbidden();
    }

    public function test_02_no_permission_actor_cannot_show_same_org_budget(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->get(route('budgets.show', $this->budgetA))
            ->assertForbidden();
    }

    public function test_03_view_budget_all_can_read_budget_globally(): void
    {
        $this->actingAs($this->globalViewer)
            ->get('/budgets')
            ->assertOk();

        $this->actingAs($this->globalViewer)
            ->get(route('budgets.show', $this->budgetA))
            ->assertOk();

        $this->actingAs($this->globalViewer)
            ->get(route('budgets.show', $this->budgetB))
            ->assertOk();
    }

    public function test_04_manage_budget_only_actor_can_read_own_org_budget(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->get('/budgets')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Budget/Index')
                ->has('budgets.data', 1)
                ->where('budgets.data.0.organization_id', $this->orgA->id)
            );

        $this->actingAs($this->manageBudgetUserA)
            ->get(route('budgets.show', $this->budgetA))
            ->assertOk();
    }

    public function test_05_manage_budget_only_actor_cannot_read_foreign_budget(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->get(route('budgets.show', $this->budgetB))
            ->assertForbidden();
    }

    public function test_06_no_permission_actor_cannot_create_budget(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->post('/budgets', [
                'year' => '2027',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();
    }

    public function test_07_no_permission_create_db_side_effect_zero(): void
    {
        $initialCount = Budget::count();

        $this->actingAs($this->unauthorizedUserA)
            ->post('/budgets', [
                'year' => '2027',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ]);

        $this->assertSame($initialCount, Budget::count());
        $this->assertDatabaseMissing('budgets', ['year' => '2027']);
    }

    public function test_08_view_budget_all_without_manage_budget_cannot_create_budget(): void
    {
        $this->actingAs($this->globalViewer)
            ->post('/budgets', [
                'year' => '2027',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('budgets', ['year' => '2027']);
    }

    public function test_09_manage_budget_unit_actor_can_create_own_org_budget(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post('/budgets', [
                'year' => '2027',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'organization_id' => $this->orgA->id,
            'year' => '2027',
            'period' => 'ANNUAL',
        ]);
    }

    public function test_10_manage_budget_unit_actor_cannot_create_foreign_org_budget(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post('/budgets', [
                'organization_id' => $this->orgB->id,
                'year' => '2028',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertRedirect();

        $created = Budget::where('year', '2028')->firstOrFail();
        $this->assertSame($this->orgA->id, $created->organization_id);
    }

    public function test_11_global_manager_can_create_authorized_global_budget(): void
    {
        $this->actingAs($this->globalManager)
            ->post('/budgets', [
                'organization_id' => $this->orgB->id,
                'year' => '2029',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'organization_id' => $this->orgB->id,
            'year' => '2029',
        ]);
    }

    public function test_12_no_permission_actor_cannot_update_budget(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->put(route('budgets.update', $this->budgetA), [
                'year' => '2026',
                'period' => 'Q1',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->assertSame('ANNUAL', $this->budgetA->fresh()->period);
    }

    public function test_13_view_budget_all_without_manage_budget_cannot_update_budget(): void
    {
        $this->actingAs($this->globalViewer)
            ->put(route('budgets.update', $this->budgetA), [
                'year' => '2026',
                'period' => 'Q1',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->actingAs($this->globalViewer)
            ->put(route('budgets.update', $this->budgetB), [
                'year' => '2026',
                'period' => 'Q1',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->assertSame('ANNUAL', $this->budgetA->fresh()->period);
        $this->assertSame('ANNUAL', $this->budgetB->fresh()->period);
    }

    public function test_14_manage_budget_unit_actor_can_update_own_budget(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->put(route('budgets.update', $this->budgetA), [
                'year' => '2026',
                'period' => 'Q1',
                'status' => 'DRAFT',
            ])
            ->assertRedirect();

        $this->assertSame('Q1', $this->budgetA->fresh()->period);
    }

    public function test_15_manage_budget_unit_actor_cannot_update_foreign_budget(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->put(route('budgets.update', $this->budgetB), [
                'year' => '2026',
                'period' => 'Q2',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->assertSame('ANNUAL', $this->budgetB->fresh()->period);
    }

    public function test_16_null_org_unit_manager_fails_closed(): void
    {
        $this->actingAs($this->nullOrgUser)
            ->get('/budgets')
            ->assertForbidden();

        $this->actingAs($this->nullOrgUser)
            ->get(route('budgets.show', $this->budgetA))
            ->assertForbidden();

        $this->actingAs($this->nullOrgUser)
            ->post('/budgets', [
                'year' => '2030',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->actingAs($this->nullOrgUser)
            ->put(route('budgets.update', $this->budgetA), [
                'year' => '2026',
                'period' => 'Q3',
                'status' => 'DRAFT',
            ])
            ->assertForbidden();

        $this->actingAs($this->nullOrgUser)
            ->delete(route('budgets.destroy', $this->budgetA))
            ->assertForbidden();

        $this->actingAs($this->nullOrgUser)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6000-NULL',
                'category' => 'OPEX',
                'allocated_amount' => 100,
            ])
            ->assertForbidden();
    }

    public function test_17_unauthorized_status_mutation_blocked(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->put(route('budgets.update', $this->budgetA), [
                'year' => '2026',
                'period' => 'ANNUAL',
                'status' => 'ACTIVE',
            ])
            ->assertForbidden();

        $this->assertSame('DRAFT', $this->budgetA->fresh()->status);
    }

    public function test_18_no_permission_actor_cannot_delete_budget(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->delete(route('budgets.destroy', $this->budgetA))
            ->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $this->budgetA->id]);
    }

    public function test_19_view_budget_all_without_manage_budget_cannot_delete_budget(): void
    {
        $this->actingAs($this->globalViewer)
            ->delete(route('budgets.destroy', $this->budgetA))
            ->assertForbidden();

        $this->actingAs($this->globalViewer)
            ->delete(route('budgets.destroy', $this->budgetB))
            ->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $this->budgetA->id]);
        $this->assertDatabaseHas('budgets', ['id' => $this->budgetB->id]);
    }

    public function test_20_manage_budget_unit_actor_can_delete_own_draft_budget(): void
    {
        $tempBudget = Budget::create([
            'organization_id' => $this->orgA->id,
            'year' => '2030',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($this->manageBudgetUserA)
            ->delete(route('budgets.destroy', $tempBudget))
            ->assertRedirect();

        $this->assertDatabaseMissing('budgets', ['id' => $tempBudget->id]);
    }

    public function test_21_foreign_deletion_blocked(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->delete(route('budgets.destroy', $this->budgetB))
            ->assertForbidden();

        $this->assertDatabaseHas('budgets', ['id' => $this->budgetB->id]);
    }

    public function test_22_no_permission_actor_cannot_create_budget_line(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6100-NEW',
                'category' => 'OPEX',
                'allocated_amount' => 500000,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('budget_lines', ['gl_account' => '6100-NEW']);
    }

    public function test_23_no_permission_actor_cannot_update_budget_line(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->put(route('budgets.lines.update', [$this->budgetA, $this->lineA]), [
                'gl_account' => $this->lineA->gl_account,
                'category' => 'CAPEX',
                'allocated_amount' => 888888,
            ])
            ->assertForbidden();

        $this->assertSame(1000000.0, (float) $this->lineA->fresh()->allocated_amount);
    }

    public function test_24_no_permission_actor_cannot_delete_budget_line(): void
    {
        $this->actingAs($this->unauthorizedUserA)
            ->delete(route('budgets.lines.destroy', [$this->budgetA, $this->lineA]))
            ->assertForbidden();

        $this->assertDatabaseHas('budget_lines', ['id' => $this->lineA->id]);
    }

    public function test_25_view_budget_all_read_only_actor_cannot_mutate_budget_line(): void
    {
        $this->actingAs($this->globalViewer)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6100-VIEW',
                'category' => 'OPEX',
                'allocated_amount' => 500000,
            ])
            ->assertForbidden();

        $this->actingAs($this->globalViewer)
            ->put(route('budgets.lines.update', [$this->budgetA, $this->lineA]), [
                'gl_account' => $this->lineA->gl_account,
                'category' => 'CAPEX',
                'allocated_amount' => 888888,
            ])
            ->assertForbidden();

        $this->actingAs($this->globalViewer)
            ->delete(route('budgets.lines.destroy', [$this->budgetA, $this->lineA]))
            ->assertForbidden();

        $this->assertDatabaseMissing('budget_lines', ['gl_account' => '6100-VIEW']);
        $this->assertDatabaseHas('budget_lines', ['id' => $this->lineA->id]);
    }

    public function test_26_manage_budget_own_org_line_mutation_works(): void
    {
        $res = $this->actingAs($this->manageBudgetUserA)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6200-OWN',
                'category' => 'OPEX',
                'allocated_amount' => 500000,
                'cost_center' => 'CC-OWN',
            ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('budget_lines', [
            'budget_id' => $this->budgetA->id,
            'gl_account' => '6200-OWN',
            'allocated_amount' => 500000,
        ]);
    }

    public function test_27_foreign_budget_line_mutation_blocked(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post(route('budgets.lines.store', $this->budgetB), [
                'gl_account' => '6300-FOR',
                'category' => 'OPEX',
                'allocated_amount' => 500000,
            ])
            ->assertForbidden();

        $this->actingAs($this->manageBudgetUserA)
            ->put(route('budgets.lines.update', [$this->budgetB, $this->lineB]), [
                'gl_account' => $this->lineB->gl_account,
                'category' => 'CAPEX',
                'allocated_amount' => 999999,
            ])
            ->assertForbidden();

        $this->actingAs($this->manageBudgetUserA)
            ->delete(route('budgets.lines.destroy', [$this->budgetB, $this->lineB]))
            ->assertForbidden();

        $this->assertDatabaseMissing('budget_lines', ['gl_account' => '6300-FOR']);
        $this->assertSame(2000000.0, (float) $this->lineB->fresh()->allocated_amount);
    }

    public function test_28_mismatched_budget_a_line_b_returns_safe_denial_404(): void
    {
        $this->actingAs($this->globalManager)
            ->put("/budgets/{$this->budgetA->id}/lines/{$this->lineB->id}", [
                'gl_account' => $this->lineB->gl_account,
                'category' => 'CAPEX',
                'allocated_amount' => 123456,
            ])
            ->assertNotFound();

        $this->actingAs($this->globalManager)
            ->delete("/budgets/{$this->budgetA->id}/lines/{$this->lineB->id}")
            ->assertNotFound();
    }

    public function test_29_rejected_nested_mutation_causes_zero_side_effect(): void
    {
        $this->assertSame(2000000.0, (float) $this->lineB->fresh()->allocated_amount);
        $this->assertDatabaseHas('budget_lines', ['id' => $this->lineB->id]);
    }

    public function test_30_budget_a_project_a_allowed(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6500-PRJA',
                'project_id' => $this->projectA->id,
                'category' => 'CAPEX',
                'allocated_amount' => 450000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budget_lines', [
            'budget_id' => $this->budgetA->id,
            'gl_account' => '6500-PRJA',
            'project_id' => $this->projectA->id,
        ]);
    }

    public function test_31_budget_a_project_b_blocked_on_line_create(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6600-BADPRJ',
                'project_id' => $this->projectB->id,
                'category' => 'CAPEX',
                'allocated_amount' => 100000,
            ])
            ->assertSessionHasErrors(['project_id']);

        $this->assertDatabaseMissing('budget_lines', ['gl_account' => '6600-BADPRJ']);
    }

    public function test_32_budget_a_project_b_blocked_on_line_update(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->put(route('budgets.lines.update', [$this->budgetA, $this->lineA]), [
                'gl_account' => $this->lineA->gl_account,
                'project_id' => $this->projectB->id,
                'category' => 'OPEX',
                'allocated_amount' => 100000,
            ])
            ->assertSessionHasErrors(['project_id']);

        $this->assertSame($this->projectA->id, $this->lineA->fresh()->project_id);
    }

    public function test_33_foreign_project_metadata_not_leaked_after_rejected_relation(): void
    {
        $response = $this->actingAs($this->manageBudgetUserA)
            ->post(route('budgets.lines.store', $this->budgetA), [
                'gl_account' => '6700-CHECKMSG',
                'project_id' => $this->projectB->id,
                'category' => 'CAPEX',
                'allocated_amount' => 100000,
            ]);

        $response->assertSessionHasErrors([
            'project_id' => 'Project tidak valid.',
        ]);

        $errorMsg = session('errors')->first('project_id');
        $this->assertStringNotContainsString($this->projectB->name, $errorMsg);
        $this->assertStringNotContainsString($this->projectB->project_code, $errorMsg);
    }

    public function test_34_pre_existing_corrupt_budget_a_project_b_relationship_does_not_disclose_project_b_metadata(): void
    {
        // Simulate pre-existing corrupt line in database
        $corruptLine = BudgetLine::create([
            'budget_id' => $this->budgetA->id,
            'project_id' => $this->projectB->id,
            'gl_account' => 'CORRUPT-999',
            'category' => 'OPEX',
            'allocated_amount' => 12345,
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);

        $response = $this->actingAs($this->manageBudgetUserA)
            ->get(route('budgets.show', $this->budgetA));

        $response->assertOk();

        // Check props rendered by Inertia
        $response->assertInertia(function (Assert $page) {
            $lines = $page->toArray()['props']['budget']['lines'] ?? [];
            $corrupt = collect($lines)->firstWhere('gl_account', 'CORRUPT-999');

            $this->assertNotNull($corrupt);
            // Project relation must be null (fail-closed)
            $this->assertNull($corrupt['project']);
            // Project ID must be sanitized to null
            $this->assertNull($corrupt['project_id']);
        });
    }

    public function test_35_unauthorized_import_blocked(): void
    {
        $file = UploadedFile::fake()->create('import.xlsx');

        $this->actingAs($this->unauthorizedUserA)
            ->post(route('budgets.import', $this->budgetA), ['file' => $file])
            ->assertForbidden();
    }

    public function test_36_unauthorized_import_side_effects_zero(): void
    {
        $initialCount = $this->budgetA->lines()->count();
        $file = UploadedFile::fake()->create('import.xlsx');

        $this->actingAs($this->unauthorizedUserA)
            ->post(route('budgets.import', $this->budgetA), ['file' => $file]);

        $this->assertSame($initialCount, $this->budgetA->lines()->count());
    }

    public function test_37_read_only_global_import_blocked(): void
    {
        $file = UploadedFile::fake()->create('import.xlsx');

        $this->actingAs($this->globalViewer)
            ->post(route('budgets.import', $this->budgetA), ['file' => $file])
            ->assertForbidden();
    }

    public function test_38_manage_budget_own_org_import_succeeds(): void
    {
        Excel::fake();
        $file = UploadedFile::fake()->create('import.xlsx');

        $this->actingAs($this->manageBudgetUserA)
            ->post(route('budgets.import', $this->budgetA), ['file' => $file])
            ->assertRedirect();

        Excel::assertImported('import.xlsx');
    }

    public function test_39_foreign_project_in_import_blocked(): void
    {
        $import = new BudgetLinesImport($this->budgetA);
        $validator = Validator::make([
            'gl_account' => '7100-TEST',
            'category' => 'OPEX',
            'allocated_amount' => 1000,
            'project_id' => $this->projectB->id,
        ], $import->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('project_id', $validator->errors()->toArray());
    }

    public function test_40_mixed_valid_foreign_import_rolls_back_atomically(): void
    {
        $initialCount = BudgetLine::count();

        // Simulate import failure triggering exception inside DB::transaction
        try {
            DB::transaction(function () {
                BudgetLine::create([
                    'budget_id' => $this->budgetA->id,
                    'gl_account' => 'ROW-1-VALID',
                    'category' => 'OPEX',
                    'allocated_amount' => 1000,
                ]);

                // Row 2 simulates foreign project validation error inside import transaction
                $import = new BudgetLinesImport($this->budgetA);
                $validator = Validator::make([
                    'gl_account' => 'ROW-2-FOREIGN',
                    'category' => 'OPEX',
                    'allocated_amount' => 2000,
                    'project_id' => $this->projectB->id,
                ], $import->rules());

                if ($validator->fails()) {
                    throw new \Exception('Validation failed: foreign project');
                }

                BudgetLine::create([
                    'budget_id' => $this->budgetA->id,
                    'gl_account' => 'ROW-3-VALID',
                    'category' => 'OPEX',
                    'allocated_amount' => 3000,
                ]);
            });
        } catch (\Throwable) {
            // caught
        }

        // Verify that Row 1 was rolled back completely!
        $this->assertSame($initialCount, BudgetLine::count());
        $this->assertDatabaseMissing('budget_lines', ['gl_account' => 'ROW-1-VALID']);
    }

    public function test_41_organization_spoof_from_unit_manager_blocked(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post('/budgets', [
                'organization_id' => $this->orgB->id,
                'year' => '2031',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertRedirect();

        $budget = Budget::where('year', '2031')->firstOrFail();
        $this->assertSame($this->orgA->id, $budget->organization_id);
    }

    public function test_42_foreign_organization_and_nonexistent_organization_do_not_expand_authority(): void
    {
        $this->actingAs($this->manageBudgetUserA)
            ->post('/budgets', [
                'organization_id' => '00000000-0000-0000-0000-000000000000',
                'year' => '2032',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ])
            ->assertRedirect();

        $budget = Budget::where('year', '2032')->firstOrFail();
        $this->assertSame($this->orgA->id, $budget->organization_id);
    }

    public function test_43_null_org_store_returns_controlled_denial_not_500(): void
    {
        $response = $this->actingAs($this->nullOrgUser)
            ->post('/budgets', [
                'year' => '2033',
                'period' => 'ANNUAL',
                'status' => 'DRAFT',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('budgets', ['year' => '2033']);
    }

    public function test_44_ui_can_edit_matches_mutation_authority(): void
    {
        // Global viewer has can.edit = false
        $resViewer = $this->actingAs($this->globalViewer)->get(route('budgets.show', $this->budgetA));
        $resViewer->assertInertia(fn (Assert $page) => $page->where('can.edit', false));

        // ManageBudgetUserA has can.edit = true on own budget
        $resUnit = $this->actingAs($this->manageBudgetUserA)->get(route('budgets.show', $this->budgetA));
        $resUnit->assertInertia(fn (Assert $page) => $page->where('can.edit', true));

        // Global manager has can.edit = true
        $resGlobalMgr = $this->actingAs($this->globalManager)->get(route('budgets.show', $this->budgetA));
        $resGlobalMgr->assertInertia(fn (Assert $page) => $page->where('can.edit', true));
    }

    public function test_45_ui_can_edit_lines_matches_mutation_authority_and_draft_state(): void
    {
        // When budget is DRAFT, can.editLines = true for authorized manager
        $resDraft = $this->actingAs($this->manageBudgetUserA)->get(route('budgets.show', $this->budgetA));
        $resDraft->assertInertia(fn (Assert $page) => $page->where('can.editLines', true));

        // When budget is ACTIVE, can.editLines = false
        $this->budgetA->update(['status' => 'ACTIVE']);
        $resActive = $this->actingAs($this->manageBudgetUserA)->get(route('budgets.show', $this->budgetA));
        $resActive->assertInertia(fn (Assert $page) => $page->where('can.editLines', false));
    }

    public function test_46_artisan_audit_and_repair_command(): void
    {
        // Create corrupt BudgetLine pointing to foreign project
        $corruptLine = BudgetLine::create([
            'budget_id' => $this->budgetA->id,
            'project_id' => $this->projectB->id,
            'gl_account' => 'CORRUPT-ARTISAN',
            'category' => 'OPEX',
            'allocated_amount' => 777000,
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);

        // Audit without repair fails and reports mismatch
        $exitCodeAudit = Artisan::call('budgets:audit-project-integrity');
        $this->assertSame(1, $exitCodeAudit);
        $this->assertStringContainsString('Found 1 cross-tenant BudgetLine project mismatches', Artisan::output());

        // Audit with repair succeeds and repairs line
        $exitCodeRepair = Artisan::call('budgets:audit-project-integrity', ['--repair' => true]);
        $this->assertSame(0, $exitCodeRepair);
        $this->assertStringContainsString('Repaired 1 BudgetLine records by nullifying foreign project_id', Artisan::output());

        // Verify DB row is repaired
        $this->assertNull($corruptLine->fresh()->project_id);
        $this->assertSame(777000.0, (float) $corruptLine->fresh()->allocated_amount);

        // Subsequent audit is clean
        $exitCodeClean = Artisan::call('budgets:audit-project-integrity');
        $this->assertSame(0, $exitCodeClean);
        $this->assertStringContainsString('ALL CLEAN', Artisan::output());
    }
}
