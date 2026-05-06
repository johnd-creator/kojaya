<?php

namespace Tests\Feature;

use App\Http\Middleware\LogActivity;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class P0SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_system_admin_bypasses_registered_policies(): void
    {
        $user = User::factory()->create();
        $user->assignRole('System Admin');

        $employee = Employee::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('update', $employee));
    }

    public function test_employee_policy_requires_employee_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('HR Unit');

        $otherOrg = Organization::factory()->create();
        $employee = Employee::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('update', $employee));

        $employee->update(['organization_id' => $organization->id]);

        $this->assertTrue(Gate::forUser($user)->allows('update', $employee));
    }

    public function test_budget_form_requests_are_not_denied_by_default(): void
    {
        $this->assertTrue((new StoreBudgetRequest)->authorize());
        $this->assertTrue((new UpdateBudgetRequest)->authorize());
    }

    public function test_log_activity_does_not_log_mutating_requests_without_authenticated_user(): void
    {
        $auditLogService = Mockery::mock(AuditLogService::class);
        $auditLogService->shouldNotReceive('log');
        $middleware = new LogActivity($auditLogService);

        $response = $middleware->handle(Request::create('/employees/1', 'PUT'), function (): Response {
            return new Response('', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_leave_status_update_requires_hr_policy(): void
    {
        $organization = Organization::factory()->create();
        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        $employeeUser->assignRole('Employee');
        $hrUser = User::factory()->create(['organization_id' => $organization->id]);
        $hrUser->assignRole('HR Unit');
        $employee = Employee::factory()->create(['organization_id' => $organization->id]);
        $leaveType = LeaveType::create([
            'name' => 'Cuti Tahunan',
            'default_days_allowance' => 12,
            'requires_attachment' => false,
            'is_paid' => true,
        ]);
        $leave = Leave::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga',
            'status' => 'Pending',
        ]);

        $this->actingAs($employeeUser)
            ->put(route('leaves.update-status', $leave), ['status' => 'Approved'])
            ->assertForbidden();

        $this->actingAs($hrUser)
            ->put(route('leaves.update-status', $leave), ['status' => 'Approved'])
            ->assertRedirect(route('leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'id' => $leave->id,
            'status' => 'Approved',
            'approver_id' => $hrUser->id,
        ]);
    }

    public function test_api_tokens_must_have_matching_ability(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['cooperative:read']);

        $this->getJson('/api/user')->assertForbidden();

        Sanctum::actingAs($user, ['profile:read']);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_api_token_rotation_requires_authentication(): void
    {
        $this->postJson('/api/token/rotate')
            ->assertUnauthorized();
    }

    public function test_api_token_rotation_revokes_old_token_and_preserves_abilities(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('android-phone', ['profile:read'])->plainTextToken;
        $oldTokenId = (int) explode('|', $plainTextToken, 2)[0];

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->postJson('/api/token/rotate', ['device_name' => 'android-phone-refresh'])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('abilities', ['profile:read'])
            ->assertJsonStructure([
                'token',
                'expires_at',
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $oldTokenId,
        ]);

        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
