<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase0MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_mobile_login_returns_member_scoped_token(): void
    {
        Role::create(['name' => 'Anggota']);
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('Anggota');
        CooperativeMember::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'app' => 'member',
            'device_name' => 'Android Member',
        ])->assertOk();

        $response->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['profile:read', 'member:read', 'member:write'])
            ->assertJsonPath('user.cooperative_member_id', $user->cooperativeMember->id);

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/auth/session')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/v1/members')
            ->assertForbidden();
    }

    public function test_invalid_mobile_login_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_mobile_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('mobile', ['profile:read'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_member_self_service_requires_member_ability_and_profile_ownership(): void
    {
        Role::create(['name' => 'Anggota']);
        $memberUser = User::factory()->create();
        $memberUser->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create(['user_id' => $memberUser->id]);

        Sanctum::actingAs($memberUser, ['profile:read']);
        $this->getJson('/api/v1/member/dashboard')->assertForbidden();

        Sanctum::actingAs($memberUser, ['member:read', 'member:write']);
        $this->getJson('/api/v1/member/dashboard')
            ->assertOk()
            ->assertJsonPath('data.member.id', $member->id);

        $this->putJson('/api/v1/member/profile', [
            'name' => 'Nama Anggota Baru',
            'email' => 'anggota-baru@example.test',
            'phone' => '08123456789',
            'address' => 'Alamat baru',
        ])->assertOk()
            ->assertJsonPath('data.member.name', 'Nama Anggota Baru')
            ->assertJsonPath('data.user.email', 'anggota-baru@example.test');
    }

    public function test_ess_attendance_api_uses_sanctum_abilities(): void
    {
        Role::create(['name' => 'Employee']);
        $organization = Organization::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 500,
        ]);
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Employee');
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user, ['ess:read']);
        $this->getJson('/api/ess/dashboard')
            ->assertOk()
            ->assertJsonPath('data.employee.id', $employee->id);

        $this->postJson('/api/ess/attendance/check-in', [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
            'device_id' => 'device-1',
        ])->assertForbidden();

        Sanctum::actingAs($user, ['attendance:read', 'attendance:write']);
        $this->postJson('/api/ess/attendance/check-in', [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'accuracy' => 10,
            'device_id' => 'device-1',
        ])->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => today()->toDateString(),
            'status' => 'PRESENT',
        ]);

        $this->getJson('/api/ess/attendance/today')
            ->assertOk()
            ->assertJsonPath('data.employee_id', $employee->id);
    }

    public function test_ess_attendance_rejects_outside_geofence(): void
    {
        $organization = Organization::factory()->create([
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius' => 100,
        ]);
        $user = User::factory()->create(['organization_id' => $organization->id]);
        Employee::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user, ['attendance:write']);

        $this->postJson('/api/ess/attendance/check-in', [
            'latitude' => -6.3,
            'longitude' => 106.9,
            'accuracy' => 10,
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'Outside geofence');
    }

    public function test_logout_all_revokes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        $firstToken = $user->createToken('mobile-a', ['profile:read'])->plainTextToken;
        $user->createToken('mobile-b', ['profile:read']);

        $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->postJson('/api/auth/logout-all')
            ->assertOk();

        $this->assertCount(0, $user->tokens()->get());
    }
}
