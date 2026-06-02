<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class P4FrontendArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_pages_receive_shared_frontend_shell_props(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Project Manager');

        $this->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Client/Index')
                ->has('appearance')
                ->has('sidebarOpen')
                ->has('auth.permissions')
            );
    }
}
