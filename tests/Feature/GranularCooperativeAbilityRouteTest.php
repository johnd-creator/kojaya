<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GranularCooperativeAbilityRouteTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cooperative_member_and_loan_routes_accept_granular_abilities(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        Sanctum::actingAs($user, ['cooperative.member.read', 'cooperative.loan.read']);

        $this->getJson('/api/v1/members')->assertOk();
        $this->getJson('/api/v1/loans')->assertOk();
    }

    public function test_legacy_cooperative_ability_remains_accepted_during_cutover(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        Sanctum::actingAs($user, ['cooperative:read']);

        $this->getJson('/api/v1/members')->assertOk();
    }
}
