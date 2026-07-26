<?php

namespace App\Services\Cooperative;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\PosTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberFinancialActivityService
{
    /**
     * @return array{transactions: LengthAwarePaginatorContract, summary: array<string, mixed>}
     */
    public function paginate(CooperativeMember $member, Request $request): array
    {
        [$posQuery, $paymentQuery] = $this->sourceQueries($member, $request);
        $union = (clone $posQuery)->unionAll(clone $paymentQuery);
        $activitiesQuery = DB::query()->fromSub($union, 'activities');

        $perPage = 12;
        $transactions = (clone $activitiesQuery)
            ->orderByDesc('occurred_at')
            ->orderByDesc('source_id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($transactions->items());
        $posIds = $rows->where('source', 'pos')->pluck('source_id')->map(fn ($id): int => (int) $id);
        $paymentIds = $rows->where('source', 'payment')->pluck('source_id')->map(fn ($id): int => (int) $id);

        $posTransactions = PosTransaction::query()
            ->with(['items.product', 'payments'])
            ->whereIn('id', $posIds)
            ->get()
            ->keyBy('id');
        $payments = CooperativePayment::query()
            ->with(['invoice.contributionType', 'contributionType'])
            ->whereIn('id', $paymentIds)
            ->get()
            ->keyBy('id');

        $transactions->setCollection($rows->map(function (object $row) use ($posTransactions, $payments): array {
            if ($row->source === 'pos') {
                $transaction = $posTransactions->get((int) $row->source_id);

                return [
                    'id' => 'pos:'.$row->source_id,
                    'source' => 'pos',
                    'title' => 'Belanja di Toko Koperasi',
                    'subtitle' => $transaction?->transaction_no,
                    'amount' => (float) $row->amount,
                    'occurred_at' => $row->occurred_at,
                    'status' => $row->status,
                    'line_items' => $transaction?->items->map(fn ($item): array => [
                        'name' => $item->product?->name ?? 'Produk',
                        'quantity' => $item->quantity,
                        'amount' => (float) $item->line_total,
                    ])->values()->all() ?? [],
                    'payment_methods' => $transaction?->payments->pluck('payment_method')->values()->all() ?? [],
                ];
            }

            $payment = $payments->get((int) $row->source_id);
            $typeName = $payment?->invoice?->contributionType?->name
                ?: $payment?->contributionType?->name
                ?: 'Simpanan';

            return [
                'id' => 'payment:'.$row->source_id,
                'source' => 'payment',
                'title' => 'Pembayaran '.$typeName,
                'subtitle' => $payment?->invoice?->period
                    ? 'Periode '.$payment->invoice->period
                    : 'Pembayaran simpanan',
                'amount' => (float) $row->amount,
                'occurred_at' => $row->occurred_at,
                'status' => $row->status,
                'line_items' => [],
                'payment_methods' => $payment?->payment_method ? [$payment->payment_method] : [],
            ];
        })->values());

        $aggregate = (clone $activitiesQuery)->selectRaw(
            'COUNT(*) as total_activities, '
            ."SUM(CASE WHEN source = 'pos' THEN 1 ELSE 0 END) as pos_count, "
            ."SUM(CASE WHEN source = 'payment' THEN 1 ELSE 0 END) as payment_count, "
            .'COALESCE(SUM(amount), 0) as total_amount, '
            .'MAX(occurred_at) as last_activity_at',
        )->first();

        $posTotals = (clone $posQuery)
            ->select([])
            ->selectRaw('COUNT(*) as total_transactions, MAX(sold_at) as last_transaction_at')
            ->first();
        $totalItemsQuery = DB::table('pos_transaction_items')
            ->join('pos_transactions', 'pos_transactions.id', '=', 'pos_transaction_items.pos_transaction_id')
            ->where('pos_transactions.cooperative_member_id', $member->id);
        $this->applyDateFilters($totalItemsQuery, $request, 'pos_transactions.sold_at');

        return [
            'transactions' => $transactions,
            'summary' => [
                'total_activities' => (int) ($aggregate->total_activities ?? 0),
                'pos_count' => (int) ($aggregate->pos_count ?? 0),
                'payment_count' => (int) ($aggregate->payment_count ?? 0),
                'total_amount' => (float) ($aggregate->total_amount ?? 0),
                'last_activity_at' => $aggregate->last_activity_at,
                'total_transactions' => (int) ($posTotals->total_transactions ?? 0),
                'total_items' => (int) $totalItemsQuery->sum('pos_transaction_items.quantity'),
                'last_transaction_at' => $posTotals->last_transaction_at,
            ],
        ];
    }

    /** @return array{0: Builder, 1: Builder} */
    private function sourceQueries(CooperativeMember $member, Request $request): array
    {
        $posQuery = DB::table('pos_transactions')
            ->where('cooperative_member_id', $member->id)
            ->selectRaw("'pos' as source, id as source_id, sold_at as occurred_at, total_amount as amount, status");
        $paymentQuery = DB::table('cooperative_payments')
            ->where('cooperative_member_id', $member->id)
            ->selectRaw("'payment' as source, id as source_id, COALESCE(paid_at, created_at) as occurred_at, amount, status");

        $this->applyDateFilters($posQuery, $request, 'sold_at');
        $this->applyDateFilters($paymentQuery, $request, 'paid_at', 'created_at');

        return [$posQuery, $paymentQuery];
    }

    private function applyDateFilters(Builder $query, Request $request, string $dateColumn, ?string $fallbackColumn = null): void
    {
        if ($request->filled('date_from')) {
            if ($fallbackColumn) {
                $query->where(function (Builder $dateQuery) use ($dateColumn, $fallbackColumn, $request): void {
                    $dateQuery->whereDate($dateColumn, '>=', $request->input('date_from'))
                        ->orWhere(function (Builder $fallbackQuery) use ($dateColumn, $fallbackColumn, $request): void {
                            $fallbackQuery->whereNull($dateColumn)
                                ->whereDate($fallbackColumn, '>=', $request->input('date_from'));
                        });
                });
            } else {
                $query->whereDate($dateColumn, '>=', $request->input('date_from'));
            }
        }

        if ($request->filled('date_to')) {
            if ($fallbackColumn) {
                $query->where(function (Builder $dateQuery) use ($dateColumn, $fallbackColumn, $request): void {
                    $dateQuery->whereDate($dateColumn, '<=', $request->input('date_to'))
                        ->orWhere(function (Builder $fallbackQuery) use ($dateColumn, $fallbackColumn, $request): void {
                            $fallbackQuery->whereNull($dateColumn)
                                ->whereDate($fallbackColumn, '<=', $request->input('date_to'));
                        });
                });
            } else {
                $query->whereDate($dateColumn, '<=', $request->input('date_to'));
            }
        }
    }
}
