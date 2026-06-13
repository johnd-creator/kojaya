<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\User;
use App\Services\Cooperative\PosSalesReportService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosPhase4ReportsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_summary_aggregates_sales_gross_profit_and_returns(): void
    {
        $cashier = User::factory()->create();
        $member = CooperativeMember::factory()->create(['credit_limit' => 50000, 'status' => 'ACTIVE']);
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        app(PosTransactionService::class)->create([
            'cooperative_member_id' => $member->id,
            'client_reference' => 'PHASE4-T1',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        app(PosTransactionService::class)->create([
            'client_reference' => 'PHASE4-T2',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 3]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 15000, 'cash_received' => 15000]],
        ], $cashier);

        $service = app(PosSalesReportService::class);
        $today = now()->toDateString();
        $summary = $service->summaryForPeriod($today, $today);

        $this->assertSame(2, $summary['transactions']);
        $this->assertSame(25000.0, $summary['gross_sales']);
        $this->assertSame(20000.0, $summary['gross_profit']);
        $this->assertSame(1, $summary['member_transactions']);
    }

    public function test_payment_reconciliation_groups_by_method(): void
    {
        $cashier = User::factory()->create();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        app(PosTransactionService::class)->create([
            'client_reference' => 'PHASE4-PAY1',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);

        app(PosTransactionService::class)->create([
            'client_reference' => 'PHASE4-PAY2',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'QRIS', 'amount' => 5000]],
        ], $cashier);

        $service = app(PosSalesReportService::class);
        $rows = $service->paymentReconciliation(now()->toDateString(), now()->toDateString());

        $this->assertCount(2, $rows);
        $methods = collect($rows)->pluck('method')->all();
        $this->assertContains('CASH', $methods);
        $this->assertContains('QRIS', $methods);
    }

    public function test_daily_trend_returns_per_day_revenue(): void
    {
        $cashier = User::factory()->create();
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        app(PosTransactionService::class)->create([
            'client_reference' => 'PHASE4-T',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);

        $service = app(PosSalesReportService::class);
        $trend = $service->dailyTrend(now()->toDateString(), now()->toDateString());

        $this->assertCount(1, $trend);
        $this->assertSame(5000.0, $trend[0]['revenue']);
        $this->assertSame(1, $trend[0]['transactions']);
    }

    public function test_top_members_lists_top_spenders(): void
    {
        $cashier = User::factory()->create();
        $m1 = CooperativeMember::factory()->create(['credit_limit' => 100000, 'status' => 'ACTIVE']);
        $m2 = CooperativeMember::factory()->create(['credit_limit' => 100000, 'status' => 'ACTIVE']);
        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create([
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        $service = app(PosTransactionService::class);
        $service->create([
            'cooperative_member_id' => $m1->id,
            'client_reference' => 'PHASE4-M1',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 4]],
            'payments' => [['payment_method' => 'MEMBER_CREDIT', 'amount' => 20000]],
        ], $cashier);
        $service->create([
            'cooperative_member_id' => $m2->id,
            'client_reference' => 'PHASE4-M2',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'MEMBER_CREDIT', 'amount' => 10000]],
        ], $cashier);

        $report = app(PosSalesReportService::class);
        $top = $report->topMembers(now()->toDateString(), now()->toDateString());

        $this->assertSame($m1->id, $top[0]['cooperative_member_id']);
        $this->assertSame(20000.0, $top[0]['total']);
        $this->assertSame($m2->id, $top[1]['cooperative_member_id']);
    }

    public function test_product_sales_aggregates_by_product(): void
    {
        $cashier = User::factory()->create();
        $category = PosCategory::factory()->create();
        $p1 = PosProduct::factory()->create(['pos_category_id' => $category->id, 'cost_price' => 1000, 'sale_price' => 5000, 'stock' => 100]);
        $p2 = PosProduct::factory()->create(['pos_category_id' => $category->id, 'cost_price' => 2000, 'sale_price' => 6000, 'stock' => 100]);

        $service = app(PosTransactionService::class);
        $service->create([
            'client_reference' => 'PHASE4-P1',
            'items' => [
                ['pos_product_id' => $p1->id, 'quantity' => 2],
                ['pos_product_id' => $p2->id, 'quantity' => 1],
            ],
            'payments' => [['payment_method' => 'CASH', 'amount' => 16000, 'cash_received' => 16000]],
        ], $cashier);

        $report = app(PosSalesReportService::class);
        $rows = $report->productSalesForPeriod(now()->toDateString(), now()->toDateString());

        $this->assertCount(2, $rows);
        $p1Row = $rows->firstWhere('pos_product_id', $p1->id);
        $this->assertSame(2, $p1Row['quantity']);
        $this->assertSame(10000.0, $p1Row['revenue']);
        $this->assertSame(8000.0, $p1Row['gross_profit']);
    }

    public function test_reports_page_is_accessible_with_view_reports_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports']);

        $this->actingAs($user)
            ->get(route('cooperative.pos.reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Cooperative/Pos/Reports/Index'));
    }

    public function test_reports_page_raw_url_resolves_to_pos_report_controller(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports']);

        $response = $this->actingAs($user)
            ->get('/cooperative/pos/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cooperative/Pos/Reports/Index')
                ->has('payment_reconciliation')
                ->has('daily_trend')
                ->has('top_products')
                ->has('top_members')
                ->has('cashier_performance'));

        $payload = $response->original->getData();
        $page = $payload['page'];
        $this->assertArrayHasKey('props', $page);
        $this->assertArrayHasKey('payment_reconciliation', $page['props']);
    }

    public function test_only_one_route_resolves_to_pos_reports_index(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->filter(fn ($route) => $route->getName() === 'cooperative.pos.reports.index')
            ->values();

        $this->assertCount(1, $routes, 'cooperative.pos.reports.index should only have a single route definition.');
    }

    public function test_reports_csv_export_streams_csv(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports']);

        $category = PosCategory::factory()->create();
        $product = PosProduct::factory()->create(['pos_category_id' => $category->id, 'cost_price' => 1000, 'sale_price' => 5000, 'stock' => 10]);
        app(PosTransactionService::class)->create([
            'client_reference' => 'PHASE4-CSV',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $user);

        $response = $this->actingAs($user)->get(route('cooperative.pos.reports.export.csv'));
        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('RINGKASAN', $content);
        $this->assertStringContainsString('REKONSILIASI PEMBAYARAN', $content);
    }
}
