<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => 'web']);
    }

    public function test_budget_line_unique_validation(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Finance Unit');

        $budget = Budget::create([
            'organization_id' => $org->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        $this->actingAs($user)->post(route('budgets.lines.store', $budget), [
            'gl_account' => '6100-OPX',
            'category' => 'OPEX',
            'allocated_amount' => 1000,
            'cost_center' => 'CC1',
        ])->assertSessionHasNoErrors();

        // Try duplicate
        $this->actingAs($user)->post(route('budgets.lines.store', $budget), [
            'gl_account' => '6100-OPX',
            'category' => 'OPEX',
            'allocated_amount' => 2000,
            'cost_center' => 'CC1',
        ])->assertSessionHasErrors(['gl_account']);

        // Different cost center should be allowed
        $this->actingAs($user)->post(route('budgets.lines.store', $budget), [
            'gl_account' => '6100-OPX',
            'category' => 'OPEX',
            'allocated_amount' => 2000,
            'cost_center' => 'CC2',
        ])->assertSessionHasNoErrors();
    }

    public function test_import_budget_lines(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Finance Unit');

        $budget = Budget::create([
            'organization_id' => $org->id,
            'year' => '2026',
            'period' => 'ANNUAL',
            'status' => 'DRAFT',
        ]);

        Excel::fake();

        $file = UploadedFile::fake()->create('budget.xlsx');

        $this->actingAs($user)->post(route('budgets.import', $budget), [
            'file' => $file,
        ])->assertRedirect();

        Excel::assertImported('budget.xlsx');
    }

    // Since we can't easily test actual Excel parsing without a real file or complex mocking,
    // we trust Maatwebsite's testing utilities for the parsing part,
    // but we can test the Import class logic directly if needed.
    // However, for Feature test, ensuring the endpoint accepts the file and calls Excel::import is usually enough
    // combined with Unit test for the Import class if complex logic existed.
    // Here we rely on the integration test above.
}
