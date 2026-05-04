<?php

namespace Tests\Feature;

use App\Models\JobGrade;
use App\Models\Organization;
use App\Models\SalaryComponentType;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalaryStructureManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_salary_structure_index(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $jobGrade = JobGrade::factory()->create();
        $otherJobGrade = JobGrade::factory()->create();
        $componentType = SalaryComponentType::factory()->create();

        $structure = SalaryStructure::factory()->create([
            'employee_type' => 'Organic',
            'organization_id' => $organization->id,
            'job_grade_id' => $jobGrade->id,
        ]);
        $structure->items()->create([
            'salary_component_type_id' => $componentType->id,
            'amount' => 3000000,
        ]);

        $otherStructure = SalaryStructure::factory()->create([
            'employee_type' => 'TKWT',
            'job_grade_id' => $otherJobGrade->id,
        ]);
        $otherStructure->items()->create([
            'salary_component_type_id' => $componentType->id,
            'amount' => 1500000,
        ]);

        $this->actingAs($user)
            ->get(route('salary-structures.index', [
                'employee_type' => 'Organic',
                'job_grade_id' => $jobGrade->id,
                'organization_id' => $organization->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SalaryStructure/Index')
                ->has('structures.data', 1)
                ->where('structures.data.0.id', $structure->id)
                ->where('filters.employee_type', 'Organic')
            );
    }

    public function test_user_can_create_update_and_delete_salary_structure(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $jobGrade = JobGrade::factory()->create();
        $baseSalary = SalaryComponentType::factory()->create(['code' => 'P1']);
        $allowance = SalaryComponentType::factory()->create(['code' => 'TP']);
        $bonus = SalaryComponentType::factory()->create(['code' => 'BON']);

        $this->actingAs($user)
            ->from(route('salary-structures.index'))
            ->post(route('salary-structures.store'), [
                'employee_type' => 'Organic',
                'job_grade_id' => $jobGrade->id,
                'organization_id' => $organization->id,
                'min_tenure_months' => 0,
                'max_tenure_months' => 24,
                'effective_from' => now()->startOfMonth()->toDateString(),
                'effective_until' => null,
                'items' => [
                    ['component_type_id' => $baseSalary->id, 'amount' => 5000000],
                    ['component_type_id' => $allowance->id, 'amount' => 750000],
                ],
            ])
            ->assertRedirect(route('salary-structures.index'));

        $structure = SalaryStructure::query()->first();
        $this->assertNotNull($structure);
        $this->assertCount(2, $structure->items);

        $this->actingAs($user)
            ->from(route('salary-structures.index'))
            ->put(route('salary-structures.update', $structure->id), [
                'employee_type' => 'Organic',
                'job_grade_id' => $jobGrade->id,
                'organization_id' => $organization->id,
                'min_tenure_months' => 12,
                'max_tenure_months' => null,
                'effective_from' => now()->startOfMonth()->toDateString(),
                'effective_until' => null,
                'items' => [
                    ['component_type_id' => $bonus->id, 'amount' => 1250000],
                ],
            ])
            ->assertRedirect(route('salary-structures.index'));

        $structure->refresh();
        $this->assertSame(12, $structure->min_tenure_months);
        $this->assertCount(1, $structure->items);
        $this->assertSame($bonus->id, $structure->items->first()->salary_component_type_id);

        $this->actingAs($user)
            ->delete(route('salary-structures.destroy', $structure->id))
            ->assertRedirect(route('salary-structures.index'));

        $this->assertDatabaseMissing('salary_structures', ['id' => $structure->id]);
    }
}
