<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Services\Procurement\ApprovalService;
use App\Services\Procurement\BudgetValidationService;
use App\Services\Procurement\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $mockUser = \Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('hasAnyRole')->andReturn(true);
        $resApr = $svc->approvePr($pr->fresh(), $mockUser, 1);
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
}
