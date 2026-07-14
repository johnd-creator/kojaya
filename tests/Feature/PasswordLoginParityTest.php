<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordLoginParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_login_returns_member_status_and_routing_metadata(): void
    {
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'app' => 'member',
            'device_name' => 'Android Phone',
        ])->assertOk();

        $response
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure([
                'token_type',
                'token',
                'abilities',
                'user',
                'member_status',
                'validation_status',
                'onboarding_next_step',
                'auth_result',
            ]);

        $this->assertNotNull($response->json('member_status'));
        $this->assertNotNull($response->json('validation_status'));
        $this->assertSame('login_existing', $response->json('auth_result'));
    }

    public function test_password_and_google_login_share_same_contract_shape(): void
    {
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'app' => 'member',
        ])->assertOk();

        $keys = array_keys($response->json());

        $requiredParityKeys = [
            'token_type',
            'token',
            'abilities',
            'user',
            'member_status',
            'validation_status',
            'onboarding_next_step',
            'auth_result',
        ];

        foreach ($requiredParityKeys as $key) {
            $this->assertContains($key, $keys, "Password login must include '{$key}' for Google parity.");
        }
    }

    public function test_password_login_for_non_member_user_returns_null_member_fields(): void
    {
        Role::firstOrCreate(['name' => 'Admin Koperasi']);
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('Admin Koperasi');

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'app' => 'member',
        ])->assertOk();

        $this->assertNull($response->json('member_status'));
        $this->assertNull($response->json('validation_status'));
        $this->assertNull($response->json('onboarding_next_step'));
    }
}
