<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcurementWebFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_procurement_index_pages_render_when_empty(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);

        Permission::create(['name' => 'view_pr_all', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_po_all', 'guard_name' => 'web']);
        Permission::create(['name' => 'view_grn_all', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage_vendors', 'guard_name' => 'web']);

        $role = Role::create(['name' => 'Procurement Viewer', 'guard_name' => 'web']);
        $role->syncPermissions(['view_pr_all', 'view_po_all', 'view_grn_all', 'manage_vendors']);
        $user->assignRole($role);

        $this->actingAs($user);

        $this->get('/procurement/vendors')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Procurement/Vendors/Index')
                ->has('vendors', 0)
            );

        $this->get('/procurement/purchase-requests')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Procurement/PurchaseRequests/Index')
                ->has('requests', 0)
            );

        $this->get('/procurement/purchase-orders')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Procurement/PurchaseOrders/Index')
                ->has('orders', 0)
            );

        $this->get('/procurement/grns')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Procurement/Grn/Index')
                ->has('receipts', 0)
            );
    }

    public function test_pr_submit_approve_generate_po_and_receive_grn_via_web_routes(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        Permission::create(['name' => 'create_pr', 'guard_name' => 'web']);
        Permission::create(['name' => 'approve_pr', 'guard_name' => 'web']);
        Permission::create(['name' => 'create_po', 'guard_name' => 'web']);
        Permission::create(['name' => 'receive_grn', 'guard_name' => 'web']);

        $role = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $role->syncPermissions(['create_pr', 'approve_pr', 'create_po', 'receive_grn']);
        $user->assignRole($role);
        $approver = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        $approver->assignRole($role);

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

        $this->actingAs($approver)
            ->post("/procurement/purchase-requests/{$prId}/approve", ['level' => 1])
            ->assertRedirect();
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

    public function test_create_po_from_pr_is_idempotent_when_requested_twice(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        Permission::create(['name' => 'create_pr', 'guard_name' => 'web']);
        Permission::create(['name' => 'approve_pr', 'guard_name' => 'web']);
        Permission::create(['name' => 'create_po', 'guard_name' => 'web']);

        $role = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
        $role->syncPermissions(['create_pr', 'approve_pr', 'create_po']);
        $user->assignRole($role);
        $approver = User::factory()->create([
            'organization_id' => $org->id,
            'email_verified_at' => now(),
        ]);
        $approver->assignRole($role);

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

        $this->post('/procurement/purchase-requests', [
            'title' => 'PR Idempotent',
            'cost_center' => 'CC-02',
            'items' => [
                ['description' => 'Item B', 'gl_account' => '6101', 'qty' => 1, 'price' => 1500000],
            ],
        ])->assertRedirect();

        $prId = $this->app['db']->table('purchase_requests')->value('id');

        $this->post("/procurement/purchase-requests/{$prId}/submit")->assertRedirect();
        $this->actingAs($approver)
            ->post("/procurement/purchase-requests/{$prId}/approve", ['level' => 1])
            ->assertRedirect();

        $firstResponse = $this->post("/procurement/purchase-orders/from-pr/{$prId}");
        $firstResponse->assertRedirect();

        $firstPoId = $this->app['db']->table('purchase_orders')->where('purchase_request_id', $prId)->value('id');
        $this->assertNotNull($firstPoId);

        $secondResponse = $this->post("/procurement/purchase-orders/from-pr/{$prId}");
        $secondResponse->assertRedirect(route('procurement.pos.show', $firstPoId));

        $this->assertSame(1, $this->app['db']->table('purchase_orders')->where('purchase_request_id', $prId)->count());
    }
}
