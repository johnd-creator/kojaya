<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class P5MemberAuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_member_user_redirected_to_member_portal_after_login(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/member');
    }

    public function test_admin_user_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('System Admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_member_middleware_allows_user_with_cooperative_member(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Anggota');

        CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user)->get('/member');

        $response->assertOk();
    }

    public function test_member_middleware_redirects_user_without_cooperative_member(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $response = $this->actingAs($user)->get('/member');

        $response->assertRedirect('/dashboard');
    }

    public function test_member_middleware_redirects_guest_to_login(): void
    {
        $response = $this->get('/member');

        $response->assertRedirect('/login');
    }
}
