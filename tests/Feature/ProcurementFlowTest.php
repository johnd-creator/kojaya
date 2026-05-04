<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\GoodsReceiveNoteItem;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\SparePart;
use App\Models\SparePartStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Procurement\ApprovalService;
use App\Services\Procurement\BudgetValidationService;
use App\Services\Procurement\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcurementFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeService(): ProcurementService
    {
        return new ProcurementService(new BudgetValidationService, new ApprovalService);
    }

    public function test_pr_submit_approve_create_po_and_grn(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $user->assignRole('Manager');

        $budget = Budget::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'year' => date('Y'),
            'period' => 'ANNUAL',
            'status' => 'APPROVED',
        ]);
        BudgetLine::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'budget_id' => $budget->id,
            'gl_account' => '6101',
            'allocated_amount' => 10000000,
            'committed_amount' => 0,
            'realized_amount' => 0,
        ]);

        $pr = PurchaseRequest::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'title' => 'PR Test',
            'status' => 'DRAFT',
            'total_amount' => 0,
        ]);
        PurchaseRequestItem::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'purchase_request_id' => $pr->id,
            'description' => 'Item A',
            'gl_account' => '6101',
            'qty' => 2,
            'price' => 1000000,
            'amount' => 2000000,
        ]);
        $pr->total_amount = 2000000;
        $pr->save();

        $svc = $this->makeService();
        $res = $svc->submitPr($pr->fresh()->load('items'));
        $this->assertTrue($res['ok']);
        $this->assertEquals('SUBMITTED', $pr->fresh()->status);

        $resApr = $svc->approvePr($pr->fresh(), $user, 1);
        $this->assertTrue($resApr['ok']);
        $this->assertEquals('APPROVED', $pr->fresh()->status);

        $po = $svc->createPoFromPr($pr->fresh());
        $this->assertInstanceOf(PurchaseOrder::class, $po);
        $this->assertEquals('PO_CREATED', $pr->fresh()->status);

        $grn = $svc->createGrnFromPo($po->fresh());
        $resGrn = $svc->receiveGrn($grn->fresh(), [
            ['po_item_id' => $po->items()->first()->id, 'received_qty' => 2],
        ]);
        $this->assertTrue($resGrn['ok']);
        $this->assertEquals('RECEIVED_FULL', $grn->fresh()->status);
    }

    public function test_receive_grn_is_idempotent_and_does_not_duplicate_stock(): void
    {
        $org = Organization::factory()->create();
        $warehouse = Warehouse::factory()->create(['organization_id' => $org->id]);
        $sparePart = SparePart::factory()->create(['organization_id' => $org->id]);

        $pr = PurchaseRequest::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'title' => 'PR Spare Part',
            'status' => 'APPROVED',
            'total_amount' => 500000,
        ]);

        PurchaseRequestItem::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'purchase_request_id' => $pr->id,
            'spare_part_id' => $sparePart->id,
            'description' => 'Bearing',
            'gl_account' => '6101',
            'qty' => 2,
            'price' => 250000,
            'amount' => 500000,
        ]);

        $svc = $this->makeService();
        $po = $svc->createPoFromPr($pr->fresh()->load('items'));
        $this->assertSame($warehouse->id, $po->warehouse_id);

        $grn = $svc->createGrnFromPo($po->fresh());
        $poItemId = $po->items()->value('id');

        $firstReceive = $svc->receiveGrn($grn->fresh(), [
            ['po_item_id' => $poItemId, 'received_qty' => 2, 'condition' => 'OK'],
        ]);
        $secondReceive = $svc->receiveGrn($grn->fresh(), [
            ['po_item_id' => $poItemId, 'received_qty' => 2, 'condition' => 'OK'],
        ]);

        $this->assertTrue($firstReceive['ok']);
        $this->assertTrue($secondReceive['ok']);
        $this->assertTrue($secondReceive['already_received']);

        $grn->refresh();
        $this->assertSame('RECEIVED_FULL', $grn->status);
        $this->assertSame(1, GoodsReceiveNoteItem::query()->where('goods_receive_note_id', $grn->id)->count());

        $stock = SparePartStock::query()
            ->where('spare_part_id', $sparePart->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertSame('2.00', $stock->quantity);
    }
}
