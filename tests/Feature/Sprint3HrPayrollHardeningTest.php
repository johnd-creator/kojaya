<?php

namespace Tests\Feature;

use App\Models\CooperativeShuPeriod;
use App\Models\Employee;
use App\Models\GoodsReceiveNote;
use App\Models\GoodsReceiveNoteItem;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class Sprint3HrPayrollHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_ess_employee_can_view_thr_entitlement(): void
    {
        [$user, $employee] = $this->employeeUser([
            'hire_date' => '2025-06-01',
            'basic_salary' => 6000000,
        ]);

        Sanctum::actingAs($user, ['payroll:read']);

        $this->getJson('/api/ess/thr/entitlement?year=2026')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.months_worked', 12)
            ->assertJsonPath('data.amount', 6000000);

        $this->assertDatabaseHas('thr_entitlements', [
            'employee_id' => $employee->id,
            'year' => 2026,
            'months_worked' => 12,
        ]);
    }

    public function test_attendance_correction_request_can_be_approved_into_attendance_record(): void
    {
        [$user, $employee] = $this->employeeUser();

        Sanctum::actingAs($user, ['attendance:write']);

        $correctionId = $this->postJson('/api/ess/attendance/correction', [
            'date' => today()->subDay()->toDateString(),
            'corrected_clock_in' => '08:05',
            'corrected_clock_out' => '17:10',
            'reason' => 'Lupa check-in dan check-out karena kunjungan lapangan.',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING')
            ->json('data.id');

        $this->postJson("/api/ess/attendance/corrections/{$correctionId}/approve", [
            'review_note' => 'Disetujui HR.',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.attendance.clock_in', '08:05');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => today()->subDay()->toDateString(),
            'clock_in' => '08:05',
            'clock_out' => '17:10',
            'status' => 'PRESENT',
        ]);
    }

    public function test_closed_shu_period_can_request_revision_with_audit_log(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Permission::firstOrCreate(['name' => 'manage_cooperative_shu', 'guard_name' => 'web']);
        $user->givePermissionTo('manage_cooperative_shu');
        $period = CooperativeShuPeriod::query()->create([
            'year' => 2026,
            'cooperative_pool' => 1000000,
            'pos_profit_pool' => 250000,
            'status' => 'CLOSED',
            'closed_at' => now(),
            'closed_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/cooperative/shu/{$period->id}/request-revision", [
                'reason' => 'Ada koreksi data transaksi anggota setelah tutup buku.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cooperative_shu_periods', [
            'id' => $period->id,
            'status' => 'REVISION',
            'revision_requested_by' => $user->id,
        ]);
        $this->assertDatabaseHas('approval_logs', [
            'subject_type' => CooperativeShuPeriod::class,
            'subject_id' => (string) $period->id,
            'from_status' => 'CLOSED',
            'to_status' => 'REVISION',
        ]);
    }

    public function test_vendor_performance_endpoint_calculates_snapshot_and_updates_rating(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $vendor = Vendor::factory()->create([
            'organization_id' => $organization->id,
            'rating' => null,
        ]);
        $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);
        $purchaseOrder = PurchaseOrder::factory()->create([
            'organization_id' => $organization->id,
            'unit_id' => $organization->id,
            'vendor_id' => $vendor->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'RECEIVED',
        ]);
        $purchaseOrderItem = PurchaseOrderItem::query()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'description' => 'Kertas thermal',
            'qty' => 10,
            'price' => 10000,
            'amount' => 100000,
        ]);
        $goodsReceiveNote = GoodsReceiveNote::query()->create([
            'organization_id' => $organization->id,
            'unit_id' => $organization->id,
            'purchase_order_id' => $purchaseOrder->id,
            'warehouse_id' => $warehouse->id,
            'grn_no' => 'GRN-TEST-001',
            'status' => 'RECEIVED_FULL',
            'received_at' => now(),
        ]);
        GoodsReceiveNoteItem::query()->create([
            'goods_receive_note_id' => $goodsReceiveNote->id,
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'received_qty' => 10,
            'condition' => 'OK',
        ]);

        Sanctum::actingAs($user, ['reports:read']);

        $this->getJson("/api/v1/procurement/vendors/{$vendor->id}/performance")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.performance.score', 100)
            ->assertJsonPath('data.performance.rating', 5)
            ->assertJsonPath('data.vendor.rating', 5);

        $this->assertDatabaseHas('vendor_performance_snapshots', [
            'vendor_id' => $vendor->id,
            'rating' => 5,
        ]);
    }

    /**
     * @param  array<string, mixed>  $employeeAttributes
     * @return array{0: User, 1: Employee, 2: Organization}
     */
    private function employeeUser(array $employeeAttributes = []): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $employee = Employee::factory()->create(array_merge([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'email' => $user->email,
        ], $employeeAttributes));

        return [$user, $employee, $organization];
    }
}
