<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcurementWebFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pr_submit_approve_generate_po_and_receive_grn_via_web_routes(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        Role::create(['name' => 'Manager', 'guard_name' => 'web']);
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

        $this->actingAs($user);

        $store = $this->post('/procurement/purchase-requests', [
            'title' => 'PR Web',
            'cost_center' => 'CC-01',
            'items' => [
                ['description' => 'Item A', 'gl_account' => '6101', 'qty' => 2, 'price' => 1000000],
            ],
        ]);
        $store->assertRedirect();

        $prId = $this->app['db']->table('purchase_requests')->value('id');
        $this->assertNotNull($prId);

        $this->post("/procurement/purchase-requests/{$prId}/submit")->assertRedirect();
        $this->assertSame('SUBMITTED', $this->app['db']->table('purchase_requests')->where('id', $prId)->value('status'));

        $this->post("/procurement/purchase-requests/{$prId}/approve", ['level' => 1])->assertRedirect();
        $this->assertSame('APPROVED', $this->app['db']->table('purchase_requests')->where('id', $prId)->value('status'));

        $this->post("/procurement/purchase-orders/from-pr/{$prId}")->assertRedirect();
        $poId = $this->app['db']->table('purchase_orders')->value('id');
        $this->assertNotNull($poId);

        $this->post("/procurement/grns/from-po/{$poId}")->assertRedirect();
        $grnId = $this->app['db']->table('goods_receive_notes')->value('id');
        $this->assertNotNull($grnId);

        $poItemId = $this->app['db']->table('purchase_order_items')->value('id');

        $this->post("/procurement/grns/{$grnId}/receive", [
            'items' => [
                ['po_item_id' => $poItemId, 'received_qty' => 2, 'condition' => 'OK'],
            ],
        ])->assertRedirect();

        $this->assertSame('RECEIVED_FULL', $this->app['db']->table('goods_receive_notes')->where('id', $grnId)->value('status'));
        $this->assertSame('RECEIVED', $this->app['db']->table('purchase_orders')->where('id', $poId)->value('status'));
    }
}
