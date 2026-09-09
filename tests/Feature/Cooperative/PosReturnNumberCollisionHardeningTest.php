<?php

namespace Tests\Feature\Cooperative;

use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosTransaction;
use App\Models\User;
use App\Services\Cooperative\PosInventoryService;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PosReturnNumberCollisionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organization = Organization::factory()->create();
        $this->cashier = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->cashier->givePermissionTo('access_cooperative_pos');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_consecutive_returns_in_same_second_generate_unique_collision_proof_numbers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 14:20:00'));

        $service = app(PosReturnService::class);
        $returnCount = 10;
        $returnNos = [];

        for ($i = 0; $i < $returnCount; $i++) {
            $transaction = $this->createCompletedSale(
                org: $this->organization,
                totalAmount: 15000,
                soldAt: '2026-09-09',
                cashier: $this->cashier,
            );

            $item = $transaction->items()->firstOrFail();

            $return = $service->create([
                'pos_transaction_id' => $transaction->id,
                'returned_at' => '2026-09-09',
                'reason' => "Test return #{$i}",
                'items' => [
                    [
                        'pos_transaction_item_id' => $item->id,
                        'quantity' => 1,
                        'unit_price' => (float) $item->unit_price,
                    ],
                ],
            ], $this->cashier);

            $returnNos[] = $return->return_no;

            $this->assertDatabaseHas('pos_returns', [
                'id' => $return->id,
                'return_no' => $return->return_no,
                'status' => 'APPROVED',
            ]);

            // VARCHAR(60) length constraint check
            $this->assertLessThanOrEqual(60, strlen($return->return_no));
            $this->assertSame(46, strlen($return->return_no));
            $this->assertMatchesRegularExpression('/^RET-20260909-142000-[0-9A-HJKMNP-TV-Z]{26}$/', $return->return_no);
        }

        $this->assertCount($returnCount, $returnNos);
        $this->assertCount($returnCount, array_unique($returnNos), 'All return numbers created in the same second must be strictly unique.');
    }

    public function test_transaction_atomicity_and_rollback_on_failure_during_return_creation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-09 14:20:00'));

        $transaction = $this->createCompletedSale(
            org: $this->organization,
            totalAmount: 20000,
            soldAt: '2026-09-09',
            cashier: $this->cashier,
        );

        $item = $transaction->items()->firstOrFail();

        $location = app(PosInventoryService::class)->ensureDefaultLocation();
        $this->mock(PosInventoryService::class, function (MockInterface $mock) use ($location) {
            $mock->shouldReceive('resolveLocationFor')->andReturn($location);
            $mock->shouldReceive('restoreSaleStock')->andThrow(new RuntimeException('Simulated inventory fault during return'));
        });

        $service = app(PosReturnService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Simulated inventory fault during return');

        try {
            $service->create([
                'pos_transaction_id' => $transaction->id,
                'returned_at' => '2026-09-09',
                'reason' => 'Rollback verification',
                'items' => [
                    [
                        'pos_transaction_item_id' => $item->id,
                        'quantity' => 1,
                        'unit_price' => (float) $item->unit_price,
                    ],
                ],
            ], $this->cashier);
        } finally {
            $this->assertSame(0, PosReturn::query()->count(), 'PosReturn record must be rolled back on failure.');
            $this->assertSame(0, PosReturnItem::query()->count(), 'PosReturnItem records must be rolled back on failure.');
        }
    }

    private function createCompletedSale(
        Organization $org,
        float $totalAmount,
        string $soldAt,
        User $cashier,
        int $quantity = 1,
    ): PosTransaction {
        $category = PosCategory::factory()->create(['organization_id' => $org->id]);
        $product = PosProduct::factory()->create([
            'organization_id' => $org->id,
            'pos_category_id' => $category->id,
            'cost_price' => 1000,
            'sale_price' => $totalAmount,
            'stock' => 100,
        ]);

        app(PosInventoryService::class)->syncDefaultLocationStocks();

        return app(PosTransactionService::class)->create([
            'client_reference' => 'SALE-'.uniqid(),
            'sold_at' => $soldAt,
            'discount_amount' => 0,
            'items' => [
                [
                    'pos_product_id' => $product->id,
                    'quantity' => $quantity,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => 'CASH',
                    'amount' => $totalAmount,
                    'cash_received' => $totalAmount,
                ],
            ],
        ], $cashier);
    }
}
