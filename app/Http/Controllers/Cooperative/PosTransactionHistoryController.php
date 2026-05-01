<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosTransactionHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PosTransaction::query()
            ->with(['member', 'cashier', 'payments'])
            ->withCount('items');

        if ($request->filled('date_from')) {
            $query->whereDate('sold_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sold_at', '<=', $request->input('date_to'));
        }

        return Inertia::render('Cooperative/Pos/Transactions/Index', [
            'transactions' => $query->orderByDesc('sold_at')->paginate(20)->withQueryString(),
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function show(PosTransaction $transaction): Response
    {
        $transaction->load(['member', 'cashier', 'payments', 'items.product']);

        return Inertia::render('Cooperative/Pos/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }
}
