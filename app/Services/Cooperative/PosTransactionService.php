<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosProduct;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosTransactionService
{
    public function __construct(private MemberPointService $memberPointService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $cashier = null): PosTransaction
    {
        if (! empty($data['client_reference'])) {
            $existing = PosTransaction::query()
                ->where('client_reference', $data['client_reference'])
                ->with(['items.product', 'payments', 'member'])
                ->first();

            if ($existing) {
                $this->memberPointService->postFromTransaction($existing);

                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($data, $cashier): PosTransaction {
                $paymentMethod = $data['payment_method'];
                $memberId = $data['cooperative_member_id'] ?? null;

                if ($paymentMethod === 'MEMBER_CREDIT') {
                    if (! $memberId || ! CooperativeMember::query()->whereKey($memberId)->where('status', 'ACTIVE')->exists()) {
                        throw ValidationException::withMessages([
                            'cooperative_member_id' => 'Member credit requires an active cooperative member.',
                        ]);
                    }
                }

                $subtotal = 0;
                $grossProfit = 0;
                $items = [];

                foreach ($data['items'] as $item) {
                    $product = PosProduct::query()->lockForUpdate()->findOrFail($item['pos_product_id']);
                    $quantity = (int) $item['quantity'];

                    if (! $product->is_active || $product->stock < $quantity) {
                        throw ValidationException::withMessages([
                            'items' => "Insufficient stock for {$product->name}.",
                        ]);
                    }

                    $lineTotal = (float) $product->sale_price * $quantity;
                    $unitProfit = (float) $product->sale_price - (float) $product->cost_price;
                    $lineProfit = $unitProfit * $quantity;
                    $subtotal += $lineTotal;
                    $grossProfit += $lineProfit;
                    $items[] = [$product, $quantity, $lineTotal, $unitProfit, $lineProfit];
                }

                $discount = (float) ($data['discount_amount'] ?? 0);
                $total = max($subtotal - $discount, 0);

                $transaction = PosTransaction::query()->create([
                    'transaction_no' => $this->nextTransactionNo(),
                    'client_reference' => $data['client_reference'] ?? null,
                    'cooperative_member_id' => $memberId,
                    'cashier_id' => $cashier?->id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'gross_profit' => max($grossProfit - $discount, 0),
                    'status' => 'COMPLETED',
                    'sold_at' => now(),
                ]);

                foreach ($items as [$product, $quantity, $lineTotal, $unitProfit, $lineProfit]) {
                    $transaction->items()->create([
                        'pos_product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->sale_price,
                        'cost_price' => $product->cost_price,
                        'unit_profit' => $unitProfit,
                        'line_total' => $lineTotal,
                        'line_profit' => $lineProfit,
                    ]);

                    $stockBefore = $product->stock;
                    $product->decrement('stock', $quantity);
                    $product->refresh();

                    PosStockMovement::query()->create([
                        'pos_product_id' => $product->id,
                        'source_type' => PosTransaction::class,
                        'source_id' => $transaction->id,
                        'movement_type' => 'SALE',
                        'quantity' => -$quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $product->stock,
                        'notes' => "POS transaction {$transaction->transaction_no}",
                    ]);
                }

                $transaction->payments()->create([
                    'payment_method' => $paymentMethod,
                    'amount' => $total,
                    'reference_no' => $data['reference_no'] ?? null,
                ]);

                if ($paymentMethod === 'MEMBER_CREDIT' && $memberId) {
                    CooperativeLedgerEntry::query()->create([
                        'cooperative_member_id' => $memberId,
                        'source_type' => PosTransaction::class,
                        'source_id' => $transaction->id,
                        'entry_type' => 'POS_MEMBER_CREDIT',
                        'debit' => $total,
                        'credit' => 0,
                        'description' => "Kredit belanja POS {$transaction->transaction_no}",
                        'posted_at' => now()->toDateString(),
                    ]);
                }

                $this->memberPointService->postFromTransaction($transaction->refresh());

                return $transaction->load(['items.product', 'payments', 'member']);
            });
        } catch (QueryException $exception) {
            if (! empty($data['client_reference'])) {
                foreach (range(1, 10) as $_) {
                    $existing = PosTransaction::query()
                        ->where('client_reference', $data['client_reference'])
                        ->with(['items.product', 'payments', 'member'])
                        ->first();

                    if ($existing) {
                        $this->memberPointService->postFromTransaction($existing);

                        return $existing;
                    }

                    usleep(50000);
                }
            }

            throw $exception;
        }
    }

    private function nextTransactionNo(): string
    {
        return 'POS-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
