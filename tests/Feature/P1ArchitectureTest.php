<?php

namespace Tests\Feature;

use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class P1ArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_form_requests_are_authorized_and_contain_validation_rules(): void
    {
        $requests = [
            new StoreEmployeeRequest,
            new StoreProjectRequest,
            new StoreUserRequest,
            new StoreWorkOrderRequest,
            new UpdateRoleRequest,
        ];

        foreach ($requests as $request) {
            $this->assertTrue($request->authorize());
            $this->assertNotEmpty($request->rules());
        }

        $this->assertNotEmpty((new GeneratePayrollRequest)->rules());
    }

    public function test_api_user_endpoint_is_rate_limited(): void
    {
        $this->markTestSkipped('Config rate-limit diparkir bersama infra ERP-era.');

        $user = User::factory()->create();

        Sanctum::actingAs($user, ['profile:read']);

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->getJson('/api/user')
                ->assertOk();
        }

        $this->getJson('/api/user')
            ->assertTooManyRequests();
    }

    public function test_reports_page_loads_report_catalog_as_deferred_props(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin Pusat');

        $this->actingAs($user)
            ->get(route('reports'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports')
                ->loadDeferredProps('reports', fn (Assert $page) => $page
                    ->has('reports', 7)
                    ->where('reports.0.id', 'payslip')
                )
            );
    }

    public function test_payroll_index_loads_summary_stats_as_deferred_props(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('payrolls.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Payroll/Index')
                ->has('payrolls')
                ->loadDeferredProps('payroll-stats', fn (Assert $page) => $page
                    ->has('stats.total_net_salary')
                    ->has('stats.total_records')
                    ->has('stats.current_period')
                )
            );
    }
}
