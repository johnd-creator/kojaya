<?php

namespace App\Services\Cooperative;

use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use Illuminate\Support\Collection;

class PosSalesReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summaryForYear(int $year): array
    {
        $query = PosTransaction::query()->whereYear('sold_at', $year);

        return [
            'year' => $year,
            'transactions' => (clone $query)->count(),
            'revenue' => (float) (clone $query)->sum('total_amount'),
            'gross_profit' => (float) (clone $query)->sum('gross_profit'),
            'member_transactions' => (clone $query)->whereNotNull('cooperative_member_id')->count(),
        ];
    }

    public function productSalesForYear(int $year): Collection
    {
        return PosTransactionItem::query()
            ->selectRaw('pos_product_id, sum(quantity) as quantity, sum(line_total) as revenue, sum(line_profit) as gross_profit')
            ->with('product.category')
            ->whereHas('transaction', fn ($query) => $query->whereYear('sold_at', $year))
            ->groupBy('pos_product_id')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (PosTransactionItem $item): array => [
                'pos_product_id' => $item->pos_product_id,
                'product_name' => $item->product?->name ?? 'Produk tidak tersedia',
                'quantity' => (int) $item->quantity,
                'revenue' => (float) $item->revenue,
                'gross_profit' => (float) $item->gross_profit,
            ]);
    }
}
