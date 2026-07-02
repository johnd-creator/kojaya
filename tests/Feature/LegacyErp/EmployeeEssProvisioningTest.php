<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\Hr\EmployeeEssProvisioningService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeEssProvisioningTest extends TestCase
{
    use DatabaseMigrations;

    protected Organization $organization;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create([
            'code' => 'HQ',
            'level' => 'L1',
            'type' => 'HEADQUARTERS',
        ]);

        Permission::query()->firstOrCreate(['name' => 'manage_departments', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'view_employee_all', 'guard_name' => 'web']);

        $hrManager = Role::query()->firstOrCreate(['name' => 'HR Pusat', 'guard_name' => 'web']);
        $hrManager->givePermissionTo(['manage_departments', 'view_employee_all']);

        $this->admin = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->admin->assignRole('HR Pusat');
    }

    public function test_enabling_ess_does_not_use_employee_code_as_password(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'employee_code' => 'EMP-2026-0001',
            'email' => 'employee@example.com',
            'user_id' => null,
        ]);

        $service = $this->app->make(EmployeeEssProvisioningService::class);

        $result = $service->enable($employee->fresh());

        $this->assertNotNull($result['user']);
        $this->assertSame('reset_required', $result['password_status']);

        $employee->refresh();
        $this->assertSame($result['user']->id, $employee->user_id);

        // The new user must NOT have a password equal to the employee_code.
        $this->assertFalse(
            Hash::check('EMP-2026-0001', $result['user']->password),
            'ESS user password must not be the employee_code (would leak via printed cards / org chart).',
        );

        // Reset link should be a valid Fortify password.reset URL with token + email query.
        $this->assertNotNull($result['reset_link']);
        $this->assertStringContainsString('email=employee%40example.com', $result['reset_link']);
        $this->assertStringContainsString('/reset-password/', $result['reset_link']);
    }

    public function test_enabling_ess_for_employee_without_email_throws_validation_error(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'email' => null,
            'user_id' => null,
        ]);

        $service = $this->app->make(EmployeeEssProvisioningService::class);

        $this->expectException(ValidationException::class);

        $service->enable($employee);
    }

    public function test_enabling_ess_twice_does_not_double_provision(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'email' => 'second@example.com',
            'user_id' => null,
        ]);

        $service = $this->app->make(EmployeeEssProvisioningService::class);
        $service->enable($employee->fresh());

        $this->expectException(ValidationException::class);
        $service->enable($employee->fresh());
    }

    public function test_controller_endpoint_flashes_reset_link_on_success(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'email' => 'controller@example.com',
            'user_id' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.enable-ess', $employee));

        $response->assertRedirect(route('employees.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('ess_password_reset_link');

        $employee->refresh();
        $this->assertNotNull($employee->user_id, 'Employee must be linked to a user after enable-ess.');
    }

    public function test_revoking_ess_removes_link_to_user(): void
    {
        $employee = Employee::factory()->create([
            'organization_id' => $this->organization->id,
            'email' => 'revoke@example.com',
            'user_id' => null,
        ]);

        $service = $this->app->make(EmployeeEssProvisioningService::class);
        $service->enable($employee->fresh());

        $employee->refresh();
        $this->assertNotNull($employee->user_id);

        $this->actingAs($this->admin)
            ->from(route('employees.index'))
            ->post(route('employees.revoke-ess', $employee))
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success');

        $employee->refresh();
        $this->assertNull($employee->user_id);
    }
}
