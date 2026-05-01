<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Cooperative/Reports', [
            'summary' => $this->summaryData(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi', 'Kasir Koperasi', 'Anggota']), 403);

        return response()->json(['data' => $this->summaryData()]);
    }

    public function sales(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['System Admin', 'Pengurus Koperasi', 'Kasir Koperasi']), 403);

        return response()->json([
            'data' => PosTransaction::query()
                ->selectRaw("date(sold_at) as date, count(*) as transactions, sum(total_amount) as total")
                ->groupByRaw('date(sold_at)')
                ->orderByDesc('date')
                ->limit(31)
                ->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryData(): array
    {
        return [
            'active_members' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
            'saving_balance' => (float) CooperativeLedgerEntry::query()->sum('credit'),
            'member_credit_balance' => (float) CooperativeLedgerEntry::query()->sum('debit'),
            'unpaid_dues' => (float) CooperativeDuesInvoice::query()->whereIn('status', ['UNPAID', 'PARTIAL'])->sum('amount'),
            'today_sales' => (float) PosTransaction::query()->whereDate('sold_at', today())->sum('total_amount'),
            'monthly_sales' => (float) PosTransaction::query()->whereBetween('sold_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
            'low_stock_products' => PosProduct::query()->whereColumn('stock', '<=', 'minimum_stock')->count(),
        ];
    }
}
