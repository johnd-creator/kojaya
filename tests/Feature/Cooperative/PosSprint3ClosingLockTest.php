<?php

namespace Tests\Feature\Cooperative;

use App\Models\PosProduct;
use App\Models\PosVoidRequest;
use App\Models\User;
use App\Services\Cooperative\PosDailyClosingService;
use App\Services\Cooperative\PosReturnService;
use App\Services\Cooperative\PosTransactionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosSprint3ClosingLockTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_sale_on_locked_date_is_rejected(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        $closings = app(PosDailyClosingService::class);
        $closings->closeDay(now()->toDateString(), $cashier);

        $this->expectException(ValidationException::class);

        app(PosTransactionService::class)->create([
            'client_reference' => 'S3-LOCKED-SALE',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);
    }

    public function test_return_on_locked_origin_date_is_rejected(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S3-RETURN-LOCKED-ORIGIN',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        app(PosDailyClosingService::class)->closeDay($tx->sold_at->toDateString(), $cashier);

        $this->expectException(ValidationException::class);

        app(PosReturnService::class)->create([
            'pos_transaction_id' => $tx->id,
            'reason' => 'Barang diretur setelah closing',
            'items' => [
                ['pos_transaction_item_id' => $tx->items->first()->id, 'quantity' => 1],
            ],
        ], $cashier);
    }

    public function test_return_on_locked_return_date_is_rejected(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        $yesterday = now()->subDay()->toDateString();
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S3-RETURN-LOCKED-DATE',
            'sold_at' => $yesterday,
            'items' => [['pos_product_id' => $product->id, 'quantity' => 2]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 10000, 'cash_received' => 10000]],
        ], $cashier);

        app(PosDailyClosingService::class)->closeDay(now()->toDateString(), $cashier);

        $this->expectException(ValidationException::class);

        app(PosReturnService::class)->create([
            'pos_transaction_id' => $tx->id,
            'reason' => 'Tanggal retur sudah ditutup',
            'items' => [
                ['pos_transaction_item_id' => $tx->items->first()->id, 'quantity' => 1],
            ],
        ], $cashier);
    }

    public function test_void_on_locked_origin_date_is_rejected(): void
    {
        $cashier = $this->cashier();
        $supervisor = $this->supervisor();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);
        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S3-VOID-LOCKED',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);

        app(PosDailyClosingService::class)->closeDay($tx->sold_at->toDateString(), $cashier);

        $this->actingAs($cashier)->post(route('cooperative.pos.void-requests.store', $tx->id), [
            'reason' => 'Void setelah closing',
        ])->assertRedirect();
        $voidRequest = PosVoidRequest::query()->where('pos_transaction_id', $tx->id)->firstOrFail();

        $this->expectException(ValidationException::class);

        app(PosTransactionService::class)->approveVoid($voidRequest, $supervisor);
    }

    public function test_locked_date_does_not_block_open_dates(): void
    {
        $cashier = $this->cashier();
        $product = PosProduct::factory()->create([
            'cost_price' => 1000,
            'sale_price' => 5000,
            'stock' => 10,
        ]);

        app(PosDailyClosingService::class)->closeDay(now()->subDay()->toDateString(), $cashier);

        $tx = app(PosTransactionService::class)->create([
            'client_reference' => 'S3-OPEN-DATE-SALE',
            'items' => [['pos_product_id' => $product->id, 'quantity' => 1]],
            'payments' => [['payment_method' => 'CASH', 'amount' => 5000, 'cash_received' => 5000]],
        ], $cashier);

        $this->assertNotNull($tx->id);
        $this->assertSame((int) $product->id, $tx->items->first()->pos_product_id);
    }

    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'view_pos_reports', 'manage_pos_products']);

        return $user;
    }

    private function supervisor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['access_cooperative_pos', 'approve_pos_void']);

        return $user;
    }
}
