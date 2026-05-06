<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OvertimeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_create_overtime_request_with_evidence(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('HR Unit');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $rule = OvertimeRule::create([
            'organization_id' => $org->id,
            'name' => 'Standard OT',
            'code' => 'OT-STD',
            'multiplier' => 1.5,
            'requires_approval' => true,
        ]);

        $file = UploadedFile::fake()->image('evidence.jpg');

        $response = $this->actingAs($user)->post(route('overtime.store'), [
            'employee_id' => $employee->id,
            'overtime_rule_id' => $rule->id,
            'date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'reason' => 'Urgent fix',
            'evidence' => $file,
        ]);

        $response->assertRedirect(route('overtime.index'));

        $ot = OvertimeRequest::first();
        $this->assertEquals(2.0, $ot->total_hours);
        $this->assertEquals('PENDING', $ot->status);
        $this->assertNotNull($ot->evidence_path);
        Storage::disk('public')->assertExists($ot->evidence_path);
    }

    public function test_admin_can_approve_overtime_request(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Role::findOrCreate('HR Unit', 'web');
        $user->assignRole('HR Unit');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $rule = OvertimeRule::create(['organization_id' => $org->id, 'name' => 'OT', 'code' => 'OT', 'multiplier' => 1.5, 'requires_approval' => true]);

        $ot = OvertimeRequest::create([
            'employee_id' => $employee->id,
            'organization_id' => $org->id,
            'overtime_rule_id' => $rule->id,
            'date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'total_hours' => 2,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->post(route('overtime.approve', $ot->id));

        $response->assertRedirect();
        $ot->refresh();
        $this->assertEquals('APPROVED', $ot->status);
        $this->assertEquals($user->id, $ot->approved_by);
    }

    public function test_admin_can_reject_overtime_request(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Role::findOrCreate('HR Unit', 'web');
        $user->assignRole('HR Unit');
        $employee = Employee::factory()->create(['organization_id' => $org->id]);
        $rule = OvertimeRule::create(['organization_id' => $org->id, 'name' => 'OT', 'code' => 'OT', 'multiplier' => 1.5, 'requires_approval' => true]);

        $ot = OvertimeRequest::create([
            'employee_id' => $employee->id,
            'organization_id' => $org->id,
            'overtime_rule_id' => $rule->id,
            'date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'total_hours' => 2,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->post(route('overtime.reject', $ot->id), [
            'rejection_reason' => 'Not urgent',
        ]);

        $response->assertRedirect();
        $ot->refresh();
        $this->assertEquals('REJECTED', $ot->status);
        $this->assertEquals('Not urgent', $ot->rejection_reason);
    }
}
