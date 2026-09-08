<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CooperativeReportDeferredTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cooperative_reports_page_has_deferred_summary(): void
    {
        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_cooperative_report');

        CooperativeMember::factory()->count(3)->create([
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Reports')
            );
    }

    public function test_cooperative_reports_loads_deferred_summary_data(): void
    {
        $org = \App\Models\Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->givePermissionTo('view_cooperative_report');

        CooperativeMember::factory()->count(3)->create([
            'organization_id' => $org->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Reports')
                ->loadDeferredProps('summary', fn (Assert $page) => $page
                    ->has('summary.active_members')
                    ->has('summary.saving_balance')
                    ->has('summary.member_credit_balance')
                    ->has('summary.unpaid_dues')
                    ->has('summary.today_sales')
                    ->has('summary.monthly_sales')
                    ->has('summary.low_stock_products')
                    ->has('summary.annual_pos_profit')
                    ->has('summary.annual_pos_points')
                    ->where('summary.active_members', 3)
                )
            );
    }
}
