<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeTransfer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeTransferTest extends TestCase
{
    use DatabaseMigrations;

    protected Organization $orgA;

    protected Organization $orgB;

    protected Organization $orgC;

    protected User $hrUnit;

    protected User $adminPusat;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $this->orgB = Organization::factory()->create(['code' => 'UNIT-B', 'level' => 'L2', 'type' => 'BRANCH']);
        $this->orgC = Organization::factory()->create(['code' => 'UNIT-C', 'level' => 'L2', 'type' => 'BRANCH']);

        Role::firstOrCreate(['name' => 'HR Unit', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);

        $this->hrUnit = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->hrUnit->assignRole('HR Unit');

        $this->adminPusat = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->adminPusat->assignRole('Admin Pusat');

        $this->employee = Employee::factory()->create([
            'organization_id' => $this->orgA->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_hr_unit_can_create_transfer_request(): void
    {
        $this->actingAs($this->hrUnit);

        $response = $this->post(route('employee-transfers.store'), [
            'employee_id' => $this->employee->id,
            'to_organization_id' => $this->orgB->id,
            'effective_date' => now()->addDays(7)->toDateString(),
            'reason' => 'Business need',
        ]);

        $response->assertRedirect(route('employee-transfers.index'));

        $this->assertDatabaseHas('employee_transfers', [
            'employee_id' => $this->employee->id,
            'from_organization_id' => $this->orgA->id,
            'to_organization_id' => $this->orgB->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_admin_pusat_can_approve_transfer(): void
    {
        $transfer = EmployeeTransfer::factory()->create([
            'employee_id' => $this->employee->id,
            'from_organization_id' => $this->orgA->id,
            'to_organization_id' => $this->orgB->id,
            'status' => 'PENDING',
            'requested_by' => $this->hrUnit->id,
        ]);

        $this->actingAs($this->adminPusat);

        $response = $this->post(route('employee-transfers.approve', $transfer), [
            'notes' => 'Approved',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('employee_transfers', [
            'id' => $transfer->id,
            'status' => 'APPROVED',
            'approved_by' => $this->adminPusat->id,
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'organization_id' => $this->orgB->id,
        ]);
    }

    public function test_admin_pusat_can_reject_transfer(): void
    {
        $transfer = EmployeeTransfer::factory()->create([
            'employee_id' => $this->employee->id,
            'from_organization_id' => $this->orgA->id,
            'to_organization_id' => $this->orgB->id,
            'status' => 'PENDING',
            'requested_by' => $this->hrUnit->id,
        ]);

        $this->actingAs($this->adminPusat);

        $response = $this->post(route('employee-transfers.reject', $transfer), [
            'notes' => 'Not approved',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('employee_transfers', [
            'id' => $transfer->id,
            'status' => 'REJECTED',
            'approved_by' => $this->adminPusat->id,
        ]);

        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'organization_id' => $this->orgA->id,
        ]);
    }

    public function test_hr_unit_only_sees_transfers_from_their_organization(): void
    {
        EmployeeTransfer::factory()->count(2)->create([
            'from_organization_id' => $this->orgA->id,
            'status' => 'PENDING',
        ]);

        EmployeeTransfer::factory()->count(3)->create([
            'from_organization_id' => $this->orgB->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->hrUnit);

        $response = $this->get(route('employee-transfers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('EmployeeTransfer/Index')
            ->has('transfers')
            ->count('transfers.data', 2)
        );
    }

    public function test_admin_pusat_can_see_all_transfers(): void
    {
        EmployeeTransfer::factory()->count(2)->create([
            'from_organization_id' => $this->orgA->id,
            'status' => 'PENDING',
        ]);

        EmployeeTransfer::factory()->count(3)->create([
            'from_organization_id' => $this->orgB->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($this->adminPusat);

        $response = $this->get(route('employee-transfers.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('EmployeeTransfer/Index')
            ->has('transfers')
            ->count('transfers.data', 5)
        );
    }
}
