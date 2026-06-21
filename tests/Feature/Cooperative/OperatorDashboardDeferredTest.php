<?php

namespace Tests\Feature\Cooperative;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperatorDashboardDeferredTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_operator_dashboard_renders_without_analytics_eagerly(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_cooperative_report');

        $this->actingAs($user)
            ->get(route('cooperative.operator.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Operator/Dashboard')
            );
    }

    public function test_operator_dashboard_loads_deferred_analytics(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view_cooperative_report');

        $this->actingAs($user)
            ->get(route('cooperative.operator.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Operator/Dashboard')
                ->loadDeferredProps('analytics', fn (Assert $page) => $page
                    ->has('analytics')
                )
            );
    }

    public function test_operator_dashboard_is_forbidden_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cooperative.operator.dashboard'))
            ->assertForbidden();
    }
}
