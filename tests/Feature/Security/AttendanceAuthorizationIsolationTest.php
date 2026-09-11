<?php

namespace Tests\Feature\Security;

use App\Enums\AttendanceCorrectionStatus;
use App\Enums\PermissionEnum;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkShift;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceAuthorizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private Employee $employeeA;

    private Employee $employeeB;

    private Attendance $attendanceA;

    private Attendance $attendanceB;

    private User $userA;

    private User $userB;

    private User $noPermUser;

    private User $unitViewerA;

    private User $unitApproverA;

    private User $unitApproverB;

    private User $globalViewer;

    private User $globalApprover;

    private User $nullOrgApprover;

    private User $nullOrgViewer;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolePermissionSeeder::class);

        $this->orgA = Organization::factory()->create([
            'name' => 'Unit Alpha',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);
        $this->orgB = Organization::factory()->create([
            'name' => 'Unit Beta',
            'latitude' => null,
            'longitude' => null,
            'radius' => null,
        ]);

        $this->userA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->userB = User::factory()->create(['organization_id' => $this->orgB->id]);

        $this->employeeA = Employee::factory()->create([
            'user_id' => $this->userA->id,
            'organization_id' => $this->orgA->id,
            'first_name' => 'Alice',
            'status' => 'ACTIVE',
        ]);

        $this->employeeB = Employee::factory()->create([
            'user_id' => $this->userB->id,
            'organization_id' => $this->orgB->id,
            'first_name' => 'Bob',
            'status' => 'ACTIVE',
        ]);

        $this->attendanceA = Attendance::factory()->create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'status' => 'PRESENT',
            'date' => today()->toDateString(),
            'clock_in' => '08:00:00',
        ]);

        $this->attendanceB = Attendance::factory()->create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'status' => 'PRESENT',
            'date' => today()->toDateString(),
            'clock_in' => '08:15:00',
        ]);

        $this->noPermUser = User::factory()->create(['organization_id' => $this->orgA->id]);

        $this->unitViewerA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->unitViewerA->givePermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value);

        $this->unitApproverA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->unitApproverA->givePermissionTo([
            PermissionEnum::ATTENDANCE_VIEW_UNIT->value,
            PermissionEnum::ATTENDANCE_APPROVE->value,
        ]);

        $this->unitApproverB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->unitApproverB->givePermissionTo([
            PermissionEnum::ATTENDANCE_VIEW_UNIT->value,
            PermissionEnum::ATTENDANCE_APPROVE->value,
        ]);

        $this->globalViewer = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->globalViewer->givePermissionTo(PermissionEnum::ATTENDANCE_VIEW_ALL->value);

        $this->globalApprover = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->globalApprover->givePermissionTo([
            PermissionEnum::ATTENDANCE_VIEW_ALL->value,
            PermissionEnum::ATTENDANCE_APPROVE->value,
        ]);

        $this->nullOrgApprover = User::factory()->create(['organization_id' => null]);
        $this->nullOrgApprover->givePermissionTo([
            PermissionEnum::ATTENDANCE_VIEW_UNIT->value,
            PermissionEnum::ATTENDANCE_APPROVE->value,
        ]);

        $this->nullOrgViewer = User::factory()->create(['organization_id' => null]);
        $this->nullOrgViewer->givePermissionTo(PermissionEnum::ATTENDANCE_VIEW_UNIT->value);
    }

    /** 01 unauthenticated actor cannot access admin attendance */
    public function test_01_unauthenticated_actor_cannot_access_admin_attendance(): void
    {
        $this->get(route('attendances.index'))
            ->assertRedirect(route('login'));
    }

    /** 02 authenticated actor with no attendance permission receives 403 */
    public function test_02_authenticated_actor_with_no_attendance_permission_receives_403(): void
    {
        $this->actingAs($this->noPermUser)
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    /** 03 Employee self-service role cannot access unit-wide admin attendance */
    public function test_03_employee_role_cannot_access_unit_wide_admin_attendance(): void
    {
        $employeeUser = User::factory()->create(['organization_id' => $this->orgA->id]);
        $employeeUser->assignRole('Employee');

        $this->actingAs($employeeUser)
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    /** 04 unit viewer sees own-org attendance rows */
    public function test_04_unit_viewer_sees_own_org_attendance_rows(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 1)
                ->where('attendances.data.0.id', $this->attendanceA->id)
            );
    }

    /** 05 unit viewer cannot see foreign attendance rows */
    public function test_05_unit_viewer_cannot_see_foreign_attendance_rows(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->where('attendances.data', fn ($data) => collect($data)->where('organization_id', $this->orgB->id)->isEmpty())
            );
    }

    /** 06 foreign organization_id filter cannot expand unit scope */
    public function test_06_foreign_organization_id_filter_cannot_expand_unit_scope(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index', ['organization_id' => $this->orgB->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 0)
            );
    }

    /** 07 foreign employee_id filter cannot expand unit scope */
    public function test_07_foreign_employee_id_filter_cannot_expand_unit_scope(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index', ['employee_id' => $this->employeeB->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 0)
            );
    }

    /** 08 unit viewer employee dropdown contains no foreign employees */
    public function test_08_unit_viewer_employee_dropdown_contains_no_foreign_employees(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('employees', 1)
                ->where('employees.0.id', $this->employeeA->id)
            );
    }

    /** 09 unit viewer organization dropdown contains no foreign org */
    public function test_09_unit_viewer_organization_dropdown_contains_no_foreign_org(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('organizations', 1)
                ->where('organizations.0.id', $this->orgA->id)
            );
    }

    /** 10 unit viewer today_present count contains own org only */
    public function test_10_unit_viewer_today_present_count_contains_own_org_only(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->where('stats.today_present', 1)
            );
    }

    /** 11 global viewer sees authorized global attendance */
    public function test_11_global_viewer_sees_authorized_global_attendance(): void
    {
        $this->actingAs($this->globalViewer)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->has('attendances.data', 2)
                ->has('organizations', 2)
                ->has('employees', 2)
                ->where('stats.today_present', 2)
            );
    }

    /** 12 read-only unit viewer cannot POST attendance */
    public function test_12_read_only_unit_viewer_cannot_post_attendance(): void
    {
        $this->actingAs($this->unitViewerA)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'date' => '2026-03-01',
                'status' => 'PRESENT',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeA->id,
            'date' => '2026-03-01',
        ]);
    }

    /** 13 no-permission actor cannot POST attendance */
    public function test_13_no_permission_actor_cannot_post_attendance(): void
    {
        $this->actingAs($this->noPermUser)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'date' => '2026-03-01',
                'status' => 'PRESENT',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeA->id,
            'date' => '2026-03-01',
        ]);
    }

    /** 14 unit attendance approver can mutate own-org Employee attendance */
    public function test_14_unit_attendance_approver_can_mutate_own_org_employee_attendance(): void
    {
        $this->actingAs($this->unitApproverA)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'date' => '2026-03-01',
                'clock_in' => '08:00',
                'clock_out' => '17:00',
                'status' => 'PRESENT',
                'notes' => 'Recorded by Unit Approver A',
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'date' => '2026-03-01',
            'status' => 'PRESENT',
            'notes' => 'Recorded by Unit Approver A',
        ]);
    }

    /** 15 unit attendance approver cannot mutate foreign Employee attendance */
    public function test_15_unit_attendance_approver_cannot_mutate_foreign_employee_attendance(): void
    {
        $this->actingAs($this->unitApproverA)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'date' => '2026-03-01',
                'status' => 'ABSENT',
            ])
            ->assertForbidden();
    }

    /** 16 rejected foreign mutation leaves foreign Attendance unchanged */
    public function test_16_rejected_foreign_mutation_leaves_foreign_attendance_unchanged(): void
    {
        $originalStatus = $this->attendanceB->status;
        $originalClockIn = $this->attendanceB->clock_in;

        $this->actingAs($this->unitApproverA)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'date' => (string) $this->attendanceB->date,
                'status' => 'ABSENT',
                'clock_in' => '12:00',
            ])
            ->assertForbidden();

        $this->attendanceB->refresh();
        $this->assertSame($originalStatus, $this->attendanceB->status);
        $this->assertSame($originalClockIn, $this->attendanceB->clock_in);
    }

    /** 17 client organization_id cannot forge attendance tenant */
    public function test_17_client_organization_id_cannot_forge_attendance_tenant(): void
    {
        // Approver sends Employee A with foreign Org B in payload
        $this->actingAs($this->unitApproverA)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'organization_id' => $this->orgB->id,
                'date' => '2026-03-02',
                'status' => 'PRESENT',
            ])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgB->id,
        ]);
    }

    /** 18 Employee A + Org B mismatch cannot persist */
    public function test_18_employee_a_org_b_mismatch_cannot_persist(): void
    {
        $this->actingAs($this->globalApprover)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'organization_id' => $this->orgB->id,
                'date' => '2026-03-03',
                'status' => 'PRESENT',
            ])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgB->id,
        ]);
    }

    /** 19 Employee B + Org A mismatch cannot persist */
    public function test_19_employee_b_org_a_mismatch_cannot_persist(): void
    {
        $this->actingAs($this->globalApprover)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'organization_id' => $this->orgA->id,
                'date' => '2026-03-04',
                'status' => 'PRESENT',
            ])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgA->id,
        ]);
    }

    /** 20 existing foreign Attendance cannot be overwritten by Org A */
    public function test_20_existing_foreign_attendance_cannot_be_overwritten_by_org_a(): void
    {
        $this->actingAs($this->unitApproverA)
            ->post(route('attendances.store'), [
                'employee_id' => $this->attendanceB->employee_id,
                'date' => (string) $this->attendanceB->date,
                'status' => 'ABSENT',
                'notes' => 'Tamper attempt',
            ])
            ->assertForbidden();

        $this->attendanceB->refresh();
        $this->assertSame('PRESENT', $this->attendanceB->status);
        $this->assertNotSame('Tamper attempt', $this->attendanceB->notes);
    }

    /** 21 NULL-org unit approver fails closed */
    public function test_21_null_org_unit_approver_fails_closed(): void
    {
        $this->actingAs($this->nullOrgApprover)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeA->id,
                'date' => '2026-03-05',
                'status' => 'PRESENT',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'date' => '2026-03-05',
        ]);
    }

    /** 22 explicit global approver works where intended */
    public function test_22_explicit_global_approver_works_where_intended(): void
    {
        $this->actingAs($this->globalApprover)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'date' => '2026-03-06',
                'status' => 'PRESENT',
                'notes' => 'Recorded by Global Approver',
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'date' => '2026-03-06',
            'notes' => 'Recorded by Global Approver',
        ]);
    }

    /** 23 approval API requires valid token ability */
    public function test_23_approval_api_requires_valid_token_ability(): void
    {
        $correction = AttendanceCorrection::create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => today()->toDateString(),
            'reason' => 'Forgot clock-in',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:read']);

        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertForbidden();

        $correction->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $correction->status);
    }

    /** 24 attendance:write token without approve_attendance cannot approve correction */
    public function test_24_attendance_write_token_without_approve_attendance_cannot_approve_correction(): void
    {
        $correction = AttendanceCorrection::create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => today()->toDateString(),
            'reason' => 'Forgot clock-in',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitViewerA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertForbidden();

        $correction->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $correction->status);
    }

    /** 25 unit approver cannot approve foreign correction */
    public function test_25_unit_approver_cannot_approve_foreign_correction(): void
    {
        $correctionB = AttendanceCorrection::create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'requested_by' => $this->userB->id,
            'date' => today()->toDateString(),
            'reason' => 'Forgot clock-in',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correctionB->id}/approve")
            ->assertForbidden();

        $correctionB->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $correctionB->status);
    }

    /** 26 global approver may approve valid correction where intended */
    public function test_26_global_approver_may_approve_valid_correction_where_intended(): void
    {
        $correctionB = AttendanceCorrection::create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'requested_by' => $this->userB->id,
            'date' => today()->subDay()->toDateString(),
            'corrected_clock_in' => '08:05:00',
            'reason' => 'Forgot clock-in',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->globalApprover, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correctionB->id}/approve", [
            'review_note' => 'Approved by global HR',
        ])->assertOk()
            ->assertJsonPath('data.status', 'APPROVED');

        $correctionB->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Approved, $correctionB->status);
    }

    /** 27 requester cannot approve own correction */
    public function test_27_requester_cannot_approve_own_correction(): void
    {
        // Give unitApproverA an employee profile and let them request a correction
        $approverEmployee = Employee::factory()->create([
            'user_id' => $this->unitApproverA->id,
            'organization_id' => $this->orgA->id,
        ]);

        $correction = AttendanceCorrection::create([
            'employee_id' => $approverEmployee->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->unitApproverA->id,
            'date' => today()->toDateString(),
            'reason' => 'Self request',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertForbidden();

        $correction->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $correction->status);
    }

    /** 28 reviewer targeting own employee identity cannot self-approve where applicable */
    public function test_28_reviewer_targeting_own_employee_identity_cannot_self_approve(): void
    {
        $approverEmployee = Employee::factory()->create([
            'user_id' => $this->unitApproverA->id,
            'organization_id' => $this->orgA->id,
        ]);

        // Requested by another user, but targets unitApproverA's employee profile
        $correction = AttendanceCorrection::create([
            'employee_id' => $approverEmployee->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => today()->toDateString(),
            'reason' => 'Colleague requested for approver',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertForbidden();

        $correction->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $correction->status);
    }

    /** 29 corrupt correction employee/org mismatch cannot be approved */
    public function test_29_corrupt_correction_employee_org_mismatch_cannot_be_approved(): void
    {
        // Employee B belongs to Org B, but correction record maliciously claims Org A
        $corruptCorrection = AttendanceCorrection::create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => today()->subDays(2)->toDateString(),
            'reason' => 'Corrupt record',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->globalApprover, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$corruptCorrection->id}/approve")
            ->assertForbidden();

        $corruptCorrection->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $corruptCorrection->status);
    }

    /** 30 rejected approval creates zero Attendance mutation */
    public function test_30_rejected_approval_creates_zero_attendance_mutation(): void
    {
        $date = today()->subDays(3)->toDateString();

        $correctionB = AttendanceCorrection::create([
            'employee_id' => $this->employeeB->id,
            'organization_id' => $this->orgB->id,
            'requested_by' => $this->userB->id,
            'date' => $date,
            'corrected_clock_in' => '08:00:00',
            'reason' => 'Test zero mutation',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correctionB->id}/approve")
            ->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $this->employeeB->id,
            'date' => $date,
        ]);
    }

    /** 31 rejected approval leaves correction PENDING */
    public function test_31_rejected_approval_leaves_correction_pending(): void
    {
        $correction = AttendanceCorrection::create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => today()->subDays(4)->toDateString(),
            'reason' => 'Test pending state',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitViewerA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertForbidden();

        $correction->refresh();
        $this->assertSame(AttendanceCorrectionStatus::Pending, $correction->status);
        $this->assertNull($correction->reviewed_by);
        $this->assertNull($correction->reviewed_at);
    }

    /** 32 successful approval creates/updates Attendance with Employee authoritative organization */
    public function test_32_successful_approval_derives_authoritative_employee_organization(): void
    {
        $date = today()->subDays(5)->toDateString();

        $correction = AttendanceCorrection::create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => $date,
            'corrected_clock_in' => '08:30:00',
            'corrected_clock_out' => '17:30:00',
            'reason' => 'Approved validly',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:write']);

        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'date' => $date,
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
            'status' => 'PRESENT',
        ]);
    }

    /** 33 concurrent/double approval cannot apply duplicate workflow mutation */
    public function test_33_double_approval_cannot_apply_duplicate_workflow_mutation(): void
    {
        $date = today()->subDays(6)->toDateString();

        $correction = AttendanceCorrection::create([
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'requested_by' => $this->userA->id,
            'date' => $date,
            'corrected_clock_in' => '08:00:00',
            'reason' => 'Test double approval',
            'status' => AttendanceCorrectionStatus::Pending,
        ]);

        Sanctum::actingAs($this->unitApproverA, ['attendance:write']);

        // First approval succeeds
        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertOk();

        // Second approval attempt fails because status is no longer Pending
        $this->postJson("/api/ess/attendance/corrections/{$correction->id}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors('correction');
    }

    /** 34 existing self-service check-in still works */
    public function test_34_existing_self_service_check_in_still_works(): void
    {
        $workShift = WorkShift::factory()->create([
            'type' => 'NON_SHIFT',
            'is_flexible' => false,
            'end_time' => '17:00',
        ]);
        $this->employeeA->update(['work_shift_id' => $workShift->id]);

        // Clear existing today's attendance for employee A
        $this->attendanceA->delete();

        $this->actingAs($this->userA)
            ->from(route('attendance.self-service'))
            ->post(route('attendance.check-in'))
            ->assertRedirect(route('attendance.self-service'));

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employeeA->id,
            'organization_id' => $this->orgA->id,
            'date' => today()->toDateString(),
            'status' => 'PRESENT',
        ]);
    }

    /** 35 existing self-service check-out still works */
    public function test_35_existing_self_service_check_out_still_works(): void
    {
        $workShift = WorkShift::factory()->create([
            'type' => 'NON_SHIFT',
            'is_flexible' => false,
            'end_time' => '17:00',
        ]);
        $this->employeeA->update(['work_shift_id' => $workShift->id]);

        $this->actingAs($this->userA)
            ->from(route('attendance.self-service'))
            ->post(route('attendances.checkOut'))
            ->assertRedirect(route('attendance.self-service'));

        $this->attendanceA->refresh();
        $this->assertNotNull($this->attendanceA->clock_out);
    }

    /** 36 ESS attendance history remains own employee only */
    public function test_36_ess_attendance_history_remains_own_employee_only(): void
    {
        Sanctum::actingAs($this->userA, ['attendance:read']);

        $response = $this->getJson('/api/ess/attendance/history')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($response);
        foreach ($response as $item) {
            $this->assertSame($this->employeeA->id, $item['employee_id']);
            $this->assertNotSame($this->employeeB->id, $item['employee_id']);
        }
    }

    /** 37 self-service correction request remains own employee only */
    public function test_37_self_service_correction_request_remains_own_employee_only(): void
    {
        Sanctum::actingAs($this->userA, ['attendance:write']);

        $response = $this->postJson('/api/ess/attendance/correction', [
            'date' => today()->subDays(7)->toDateString(),
            'corrected_clock_in' => '08:00',
            'corrected_clock_out' => '17:00',
            'reason' => 'Meeting client outside office',
            'employee_id' => $this->employeeB->id, // Malicious spoof attempt
            'organization_id' => $this->orgB->id,  // Malicious spoof attempt
        ])->assertCreated();

        // Server-side MUST have bound it to employeeA and orgA!
        $this->assertSame($this->employeeA->id, $response->json('data.employee_id'));
        $this->assertSame($this->orgA->id, $response->json('data.organization_id'));
        $this->assertSame($this->userA->id, $response->json('data.requested_by'));
    }

    /** 38 NULL-org cannot gain global attendance visibility */
    public function test_38_null_org_cannot_gain_global_attendance_visibility(): void
    {
        $this->actingAs($this->nullOrgViewer)
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    /** 39 no attendance aggregate contamination */
    public function test_39_no_attendance_aggregate_contamination(): void
    {
        $this->actingAs($this->unitViewerA)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Attendance/Index')
                ->where('stats.today_present', 1)
                ->where('attendances.total', 1)
            );
    }

    /** 40 foreign employee existence cannot be materially enumerated through unauthorized store validation */
    public function test_40_unauthorized_store_denied_before_validation_enumeration(): void
    {
        // Unauthenticated -> redirected to login
        $this->post(route('attendances.store'), [
            'employee_id' => $this->employeeB->id,
            'date' => '2026-03-01',
            'status' => 'PRESENT',
        ])->assertRedirect(route('login'));

        // Unauthorized authenticated user -> 403 Forbidden without validation leakage
        $this->actingAs($this->noPermUser)
            ->post(route('attendances.store'), [
                'employee_id' => $this->employeeB->id,
                'date' => '2026-03-01',
                'status' => 'PRESENT',
            ])->assertForbidden();

        $this->actingAs($this->noPermUser)
            ->post(route('attendances.store'), [
                'employee_id' => 999999, // Non-existent employee
                'date' => '2026-03-01',
                'status' => 'PRESENT',
            ])->assertForbidden();
    }

    /** Role seeder contract regression tests */
    public function test_role_seeder_contracts(): void
    {
        $hrPusat = Role::where('name', 'HR Pusat')->firstOrFail();
        $this->assertTrue($hrPusat->hasPermissionTo('view_attendance_all'));
        $this->assertTrue($hrPusat->hasPermissionTo('approve_attendance'));

        $hrUnit = Role::where('name', 'HR Unit')->firstOrFail();
        $this->assertTrue($hrUnit->hasPermissionTo('view_attendance_unit'));
        $this->assertTrue($hrUnit->hasPermissionTo('approve_attendance'));

        $adminUnit = Role::where('name', 'Admin Unit')->firstOrFail();
        $this->assertTrue($adminUnit->hasPermissionTo('view_attendance_unit'));
        $this->assertFalse($adminUnit->hasPermissionTo('approve_attendance'));

        $employee = Role::where('name', 'Employee')->firstOrFail();
        $this->assertFalse($employee->hasPermissionTo('view_attendance_unit'));
        $this->assertTrue($employee->hasPermissionTo('access_ess_portal'));
    }
}
