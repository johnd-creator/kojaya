<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeLedgerEntry;
use App\Models\PointTransaction;
use App\Models\PosMemberPoint;
use App\Models\PosProduct;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosStockMovement;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosReturnService
{
    public function __construct(private readonly PointService $pointService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $cashier = null): PosReturn
    {
        return DB::transaction(function () use ($cashier, $data): PosReturn {
            $transaction = PosTransaction::query()
                ->with(['items', 'payments'])
                ->lockForUpdate()
                ->findOrFail($data['pos_transaction_id']);

            if ($transaction->status !== 'COMPLETED') {
                throw ValidationException::withMessages([
                    'pos_transaction_id' => 'Hanya transaksi selesai yang bisa diretur.',
                ]);
            }

            $return = PosReturn::query()->create([
                'pos_transaction_id' => $transaction->id,
                'cooperative_member_id' => $transaction->cooperative_member_id,
                'cashier_id' => $cashier?->id,
                'return_no' => $this->nextReturnNo(),
                'status' => 'APPROVED',
                'total_amount' => 0,
                'points_reversed' => 0,
                'reason' => $data['reason'] ?? null,
                'returned_at' => now(),
            ]);

            $total = 0.0;

            foreach ($data['items'] as $item) {
                $transactionItem = PosTransactionItem::query()
                    ->where('pos_transaction_id', $transaction->id)
                    ->lockForUpdate()
                    ->findOrFail($item['pos_transaction_item_id']);

                $quantity = (int) $item['quantity'];
                $returnedQuantity = (int) PosReturnItem::query()
                    ->where('pos_transaction_item_id', $transactionItem->id)
                    ->sum('quantity');

                if ($quantity <= 0 || $quantity > ((int) $transactionItem->quantity - $returnedQuantity)) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah retur melebihi sisa item transaksi.',
                    ]);
                }

                $lineTotal = round((float) $transactionItem->unit_price * $quantity, 2);
                $total = round($total + $lineTotal, 2);

                $return->items()->create([
                    'pos_transaction_item_id' => $transactionItem->id,
                    'pos_product_id' => $transactionItem->pos_product_id,
                    'quantity' => $quantity,
                    'unit_price' => $transactionItem->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $product = PosProduct::query()->lockForUpdate()->findOrFail($transactionItem->pos_product_id);
                $stockBefore = (int) $product->stock;
                $product->increment('stock', $quantity);
                $product->refresh();

                PosStockMovement::query()->create([
                    'pos_product_id' => $product->id,
                    'source_type' => PosReturn::class,
                    'source_id' => $return->id,
                    'movement_type' => 'RETURN',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $product->stock,
                    'notes' => "POS return {$return->return_no}",
                ]);
            }

            $return->forceFill(['total_amount' => $total])->save();

            $pointsReversed = $this->reversePoints($transaction, $return->refresh());

            if ($transaction->cooperative_member_id) {
                CooperativeLedgerEntry::query()->create([
                    'cooperative_member_id' => $transaction->cooperative_member_id,
                    'cooperative_payment_id' => null,
                    'source_type' => PosReturn::class,
                    'source_id' => $return->id,
                    'entry_type' => 'POS_RETURN',
                    'ledger_scope' => 'POS',
                    'debit' => 0,
                    'credit' => $total,
                    'period' => now()->format('Y-m'),
                    'description' => "Retur POS {$transaction->transaction_no}",
                    'posted_at' => now()->toDateString(),
                ]);
            }

            $return->forceFill([
                'points_reversed' => $pointsReversed,
            ])->save();

            return $return->load(['items', 'transaction']);
        });
    }

    private function reversePoints(PosTransaction $transaction, PosReturn $return): int
    {
        if (! $transaction->cooperative_member_id) {
            return 0;
        }

        $point = PosMemberPoint::query()
            ->where('pos_transaction_id', $transaction->id)
            ->first();

        if (! $point) {
            return 0;
        }

        $alreadyReversed = PointTransaction::query()
            ->where('transaction_type', 'REVERSED')
            ->where('source_type', PosReturn::class)
            ->where('source_id', $return->id)
            ->exists();

        if ($alreadyReversed) {
            return 0;
        }

        $points = (int) floor(((float) $return->total_amount / max((float) $transaction->total_amount, 1)) * (int) $point->points);

        if ($points <= 0 || ! $point->member) {
            return 0;
        }

        $this->pointService->recordTransaction(
            member: $point->member,
            transactionType: 'REVERSED',
            points: $points * -1,
            description: 'Pembalikan poin karena retur POS',
            postedAt: now(),
            sourceType: PosReturn::class,
            sourceId: (string) $return->id,
            referenceNumber: $return->return_no,
            metadata: [
                'pos_transaction_id' => $transaction->id,
                'pos_member_point_id' => $point->id,
            ],
        );

        return $points;
    }

    private function nextReturnNo(): string
    {
        return 'RET-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
    }
}
