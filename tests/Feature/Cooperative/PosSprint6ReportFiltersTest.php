<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosSalesReportService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PosSprint6ReportFiltersTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_returns_count_and_total_apply_cashier_filter(): void
    {
        $cashierA = $this->cashier('cashier-a');
        $cashierB = $this->cashier('cashier-b');
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        $txA = $this->makeSale($cashierA, $product, 2, 'S6-CASHIER-A');
        $this->makeSale($cashierB, $product, 2, 'S6-CASHIER-B');

        $this->makeReturn($cashierA, $txA, 1);

        $service = app(PosSalesReportService::class);
        $summary = $service->summaryForPeriod(
            now()->toDateString(),
            now()->toDateString(),
            ['cashier_id' => $cashierA->id],
        );

        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(1, $summary['returns']['count']);
    }

    public function test_returns_count_and_total_apply_member_filter(): void
    {
        $cashier = $this->cashier('cashier');
        $memberA = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $memberB = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        $txA = $this->makeMemberSale($cashier, $product, 2, 'S6-MEMBER-A', $memberA);
        $this->makeMemberSale($cashier, $product, 2, 'S6-MEMBER-B', $memberB);

        $this->makeReturn($cashier, $txA, 1);

        $service = app(PosSalesReportService::class);
        $summary = $service->summaryForPeriod(
            now()->toDateString(),
            now()->toDateString(),
            ['cooperative_member_id' => $memberA->id],
        );

        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(1, $summary['returns']['count']);
    }

    public function test_returns_apply_payment_filter(): void
    {
        $cashier = $this->cashier('cashier');
        $member = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        $txCash = $this->makeSale($cashier, $product, 1, 'S6-PAY-CASH', 'CASH');
        $this->makeMemberSale($cashier, $product, 1, 'S6-PAY-CREDIT', $member, 'MEMBER_CREDIT');

        $this->makeReturn($cashier, $txCash, 1);

        $service = app(PosSalesReportService::class);
        $summary = $service->summaryForPeriod(
            now()->toDateString(),
            now()->toDateString(),
            ['payment_method' => 'CASH'],
        );

        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(1, $summary['returns']['count']);
    }

    public function test_out_of_filter_returns_are_excluded_from_count_and_total(): void
    {
        $cashierA = $this->cashier('cashier-A');
        $cashierB = $this->cashier('cashier-B');
        $productA = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);
        $productB = PosProduct::factory()->create([
            'cost_price' => 2000,
            'sale_price' => 9000,
            'stock' => 100,
        ]);

        $txA = $this->makeSale($cashierA, $productA, 2, 'S6-NEG-A');
        $txB = $this->makeSale($cashierB, $productB, 3, 'S6-NEG-B');

        $returnA = $this->makeReturn($cashierA, $txA, 1);
        $this->makeReturn($cashierB, $txB, 1);

        $service = app(PosSalesReportService::class);
        $summary = $service->summaryForPeriod(
            now()->toDateString(),
            now()->toDateString(),
            ['cashier_id' => $cashierA->id],
        );

        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(1, $summary['returns']['count']);
        $this->assertSame((float) $returnA->total_amount, (float) $summary['returns']['total']);
    }

    public function test_member_filter_excludes_other_member_returns(): void
    {
        $cashier = $this->cashier('cashier');
        $memberA = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $memberB = CooperativeMember::factory()->active()->create(['credit_limit' => 100000]);
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 100,
        ]);

        $txA = $this->makeMemberSale($cashier, $product, 2, 'S6-MEMFILTER-A', $memberA);
        $this->makeMemberSale($cashier, $product, 2, 'S6-MEMFILTER-B', $memberB);

        $returnA = $this->makeReturn($cashier, $txA, 1);
        $this->makeReturn($cashier, PosTransaction::query()->where('client_reference', 'S6-MEMFILTER-B')->firstOrFail(), 1);

        $service = app(PosSalesReportService::class);
        $summary = $service->summaryForPeriod(
            now()->toDateString(),
            now()->toDateString(),
            ['cooperative_member_id' => $memberA->id],
        );

        $this->assertSame(1, $summary['transactions']);
        $this->assertSame(1, $summary['returns']['count']);
        $this->assertSame((float) $returnA->total_amount, (float) $summary['returns']['total']);
    }

    public function test_export_csv_includes_all_filters_in_url(): void
    {
        $user = $this->cashier('cashier');
        $user->givePermissionTo('view_pos_reports');

        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 5,
        ]);

        $query = http_build_query([
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'pos_product_id' => $product->id,
            'category_id' => 1,
            'cashier_id' => $user->id,
            'payment_method' => 'CASH',
        ]);

        $this->actingAs($user)
            ->get('/cooperative/pos/reports/export.csv?'.$query)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_pdf_enqueues_background_job(): void
    {
        $user = $this->cashier('cashier');
        $user->givePermissionTo('view_pos_reports');

        $query = http_build_query([
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'pos_product_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/cooperative/pos/reports/export.pdf?'.$query)
            ->assertStatus(202)
            ->assertJsonStructure(['job_id', 'status', 'progress']);

        $this->assertSame('pending', $response->json('status'));
    }

    private function makeSale(User $cashier, PosProduct $product, int $qty, string $clientRef, string $payment = 'CASH'): PosTransaction
    {
        return app(PosTransactionService::class)->create([
            'client_reference' => $clientRef,
            'cashier_id' => $cashier->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => $qty]],
            'payments' => [['payment_method' => $payment, 'amount' => $product->sale_price * $qty, 'cash_received' => $product->sale_price * $qty]],
        ], $cashier);
    }

    private function makeMemberSale(User $cashier, PosProduct $product, int $qty, string $clientRef, CooperativeMember $member, string $payment = 'CASH'): PosTransaction
    {
        return app(PosTransactionService::class)->create([
            'client_reference' => $clientRef,
            'cashier_id' => $cashier->id,
            'cooperative_member_id' => $member->id,
            'items' => [['pos_product_id' => $product->id, 'quantity' => $qty]],
            'payments' => [['payment_method' => $payment, 'amount' => $product->sale_price * $qty, 'cash_received' => $product->sale_price * $qty]],
        ], $cashier);
    }

    private function makeReturn(User $cashier, PosTransaction $tx, int $qty): PosReturn
    {
        /** @var PosTransactionItem $firstItem */
        $firstItem = $tx->items->first();

        return app(PosReturnService::class)->create([
            'pos_transaction_id' => $tx->id,
            'reason' => 'Pengujian laporan',
            'items' => [
                ['pos_transaction_item_id' => $firstItem->id, 'quantity' => $qty],
            ],
        ], $cashier);
    }

    private function cashier(string $name): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports', 'manage_pos_products']);

        return $user;
    }
}
