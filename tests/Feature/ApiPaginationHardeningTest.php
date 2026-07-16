<?php

namespace Tests\Feature;

use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Factories\NotificationFactory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiPaginationHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    #[DataProvider('paginationBoundaryProvider')]
    public function test_pagination_limit_resolver_matches_standard_contract(mixed $input, int $expected): void
    {
        $query = is_array($input) ? ['per_page' => $input] : ($input === null ? [] : ['per_page' => $input]);
        $request = Request::create('/api/test', 'GET', $query);

        $this->assertSame($expected, app(\App\Support\PaginationLimitResolver::class)->resolve($request));
    }

    /** @return array<string, array{0: mixed, 1: int}> */
    public static function paginationBoundaryProvider(): array
    {
        return [
            'omitted' => [null, 15],
            'negative one' => [-1, 1],
            'zero' => [0, 1],
            'one' => [1, 1],
            'fifty' => [50, 50],
            'fifty one' => [51, 50],
            'large' => [999999, 50],
            'non numeric' => ['not-a-number', 15],
            'array' => [['50'], 15],
        ];
    }

    public function test_notification_api_clamps_page_size_to_centralized_bounds(): void
    {
        $user = User::factory()->create();
        NotificationFactory::new()->forUser($user)->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/notifications?per_page=999999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);

        $this->actingAs($user)
            ->getJson('/api/notifications?per_page=-1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        $this->actingAs($user)
            ->getJson('/api/notifications?per_page=not-a-number')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15);
    }

    public function test_notification_recent_uses_the_same_resolver_with_endpoint_maximum(): void
    {
        $user = User::factory()->create();
        NotificationFactory::new()->forUser($user)->count(12)->create();

        $this->actingAs($user)
            ->getJson('/api/notifications/recent?limit=999999')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.limit', 10);

        $this->actingAs($user)
            ->getJson('/api/notifications/recent?limit=-1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.limit', 1);

        $this->actingAs($user)
            ->getJson('/api/notifications/recent?limit[]=invalid')
            ->assertOk()
            ->assertJsonPath('meta.limit', 5);
    }

    public function test_member_cooperative_loan_and_payment_routes_use_the_runtime_boundary_contract(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('Pengurus Koperasi');
        CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        $loanMember = CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        Loan::factory()->active()->create([
            'organization_id' => $organization->id,
            'cooperative_member_id' => $loanMember->id,
        ]);
        Sanctum::actingAs($actor, [
            'cooperative:read',
            'cooperative.member.read',
            'cooperative.loan.read',
            'cooperative.dues.read',
        ]);

        $this->getJson('/api/v1/members?per_page=999999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);

        $this->getJson('/api/v1/loans?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/v1/dues/invoices?per_page=51')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_member_ess_and_technician_routes_use_the_runtime_boundary_contract(): void
    {
        $organization = Organization::factory()->create();
        $memberUser = User::factory()->create(['organization_id' => $organization->id]);
        $memberUser->assignRole('Anggota');
        CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);
        Sanctum::actingAs($memberUser, ['member:read']);

        $this->getJson('/api/v1/member/notifications?per_page=999999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);

        $employeeUser = User::factory()->create(['organization_id' => $organization->id]);
        $employeeUser->assignRole('Employee');
        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $employeeUser->id,
        ]);
        Sanctum::actingAs($employeeUser, ['ess:read', 'attendance:read']);

        $this->getJson('/api/ess/attendance/history?per_page=51')
            ->assertOk()
            ->assertJsonPath('per_page', 50);

        $technician = User::factory()->create(['organization_id' => $organization->id]);
        $technician->assignRole('Technician');
        WorkOrder::factory()->create(['assigned_to' => $technician->id]);
        Sanctum::actingAs($technician, ['work-orders:read']);

        $this->getJson('/api/technician/work-orders?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
    }

    public function test_audit_compliance_and_employee_document_routes_use_the_runtime_boundary_contract(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create(['organization_id' => $organization->id]);
        $actor->assignRole('System Admin');
        $actor->givePermissionTo(['view_audit_logs']);

        $this->actingAs($actor)
            ->getJson('/api/audit-logs?per_page=999999')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);

        Sanctum::actingAs($actor, ['reports:read', 'employee-documents:read']);

        $this->getJson('/api/reports/non-compliant-employees?per_page=51')
            ->assertOk()
            ->assertJsonPath('data.per_page', 50);

        $employee = Employee::factory()->create(['organization_id' => $organization->id]);

        $this->getJson("/api/employees/{$employee->id}/certificates?per_page=0")
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);
    }
}
