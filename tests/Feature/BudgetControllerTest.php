<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function test_unit_user_sees_only_own_organization_budgets(): void
    {
        Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => 'web']);

        $orgA = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $orgB = Organization::factory()->create(['code' => 'UNIT-B', 'level' => 'L2', 'type' => 'BRANCH']);

        Budget::create([
            'organization_id' => $orgA->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        Budget::create([
            'organization_id' => $orgB->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $user->assignRole('Finance Unit');

        $response = $this->actingAs($user)->get('/budgets');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Budget/Index')
            ->has('budgets.data', 1)
            ->where('budgets.data.0.organization_id', $orgA->id)
        );
    }

    public function test_admin_pusat_can_create_budget_for_any_organization(): void
    {
        Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);

        $orgA = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $orgB = Organization::factory()->create(['code' => 'UNIT-B', 'level' => 'L2', 'type' => 'BRANCH']);

        $user = User::factory()->create(['organization_id' => $orgA->id]);
        $user->assignRole('Admin Pusat');

        $response = $this->actingAs($user)->post('/budgets', [
            'organization_id' => $orgB->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'organization_id' => $orgB->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);
    }

    public function test_budget_line_can_be_added_only_when_budget_is_draft(): void
    {
        Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => 'web']);

        $org = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Finance Unit');

        $budget = Budget::create([
            'organization_id' => $org->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        $response = $this->actingAs($user)->post("/budgets/{$budget->id}/lines", [
            'gl_account' => '6100-OPX',
            'category' => 'OPEX',
            'allocated_amount' => 1000000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budget_lines', [
            'budget_id' => $budget->id,
            'gl_account' => '6100-OPX',
            'category' => 'OPEX',
        ]);

        $budget->update(['status' => 'ACTIVE']);

        $blocked = $this->actingAs($user)->post("/budgets/{$budget->id}/lines", [
            'gl_account' => '6200-OPX',
            'category' => 'OPEX',
            'allocated_amount' => 1000000,
        ]);

        $blocked->assertRedirect();
        $this->assertDatabaseMissing('budget_lines', [
            'budget_id' => $budget->id,
            'gl_account' => '6200-OPX',
        ]);
    }
}
