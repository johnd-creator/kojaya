<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\CooperativeShuPeriod;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseMigrations;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_renders_operational_cooperative_metrics(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $type = CooperativeContributionType::query()->create([
            'code' => 'SW',
            'name' => 'Simpanan Wajib',
            'category' => 'MANDATORY',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $activeMember = CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'member_no' => 'MBR-001',
            'name' => 'Anggota Aktif',
            'email' => 'aktif@example.test',
            'status' => 'ACTIVE',
            'joined_at' => now()->subMonths(2)->toDateString(),
        ]);

        CooperativeMember::query()->create([
            'organization_id' => $organization->id,
            'member_no' => 'MBR-002',
            'name' => 'Anggota Pending',
            'email' => 'pending@example.test',
            'status' => 'PENDING',
        ]);

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 25000,
            'due_date' => now()->endOfMonth()->toDateString(),
            'status' => 'PARTIAL',
        ]);

        $paidInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->subMonth()->format('Y-m'),
            'amount' => 50000,
            'paid_amount' => 50000,
            'due_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'status' => 'PAID',
        ]);

        CooperativePayment::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'cooperative_dues_invoice_id' => $paidInvoice->id,
            'user_id' => $user->id,
            'amount' => 25000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);

        $category = PosCategory::query()->create([
            'name' => 'Minuman',
            'slug' => 'minuman',
            'is_active' => true,
        ]);
        $product = PosProduct::query()->create([
            'pos_category_id' => $category->id,
            'sku' => 'DRINK-001',
            'name' => 'Air Mineral',
            'cost_price' => 3000,
            'sale_price' => 5000,
            'stock' => 2,
            'minimum_stock' => 5,
            'is_active' => true,
        ]);
        $transaction = PosTransaction::query()->create([
            'transaction_no' => 'POS-001',
            'cooperative_member_id' => $activeMember->id,
            'cashier_id' => $user->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
            'gross_profit' => 40000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);

        PosTransactionItem::query()->create([
            'pos_transaction_id' => $transaction->id,
            'pos_product_id' => $product->id,
            'quantity' => 20,
            'unit_price' => 5000,
            'cost_price' => 3000,
            'unit_profit' => 2000,
            'line_total' => 100000,
            'line_profit' => 40000,
        ]);

        PosMemberPoint::query()->create([
            'cooperative_member_id' => $activeMember->id,
            'pos_transaction_id' => $transaction->id,
            'year' => now()->year,
            'profit_amount' => 40000,
            'points' => 40,
            'posted_at' => now()->toDateString(),
        ]);

        CooperativeShuPeriod::query()->create([
            'year' => now()->subYear()->year,
            'cooperative_pool' => 1000000,
            'pos_profit_pool' => 500000,
            'status' => 'CLOSED',
            'closed_at' => now()->subMonth(),
            'closed_by' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('dashboard.summary')
                ->has('dashboard.workQueue')
                ->has('dashboard.collections')
                ->has('dashboard.pos')
                ->has('dashboard.inventory')
                ->has('dashboard.members')
                ->has('dashboard.shu')
                ->has('dashboard.generatedAt')
                ->where('dashboard.summary.today_sales', 100000)
                ->where('dashboard.summary.today_transactions', 1)
                ->where('dashboard.summary.pending_payments', 1)
                ->where('dashboard.summary.low_stock_products', 1)
                ->where('dashboard.workQueue.pending_members', 1)
                ->where('dashboard.collections.collection_rate', 25)
                ->where('dashboard.pos.annual_gross_profit', 40000)
                ->where('dashboard.shu.annual_pos_points', 40)
                ->where('dashboard.inventory.low_stock_count', 1)
                ->where('dashboard.members.active', 1)
            );
    }
}
