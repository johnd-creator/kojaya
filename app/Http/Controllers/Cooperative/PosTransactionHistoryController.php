<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\CooperativeMember;
use App\Models\PosTransaction;
use App\Models\User;
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

        if ($request->filled('transaction_no')) {
            $no = $request->string('transaction_no')->toString();
            $query->where('transaction_no', 'like', "%{$no}%");
        }

        if ($request->filled('member_id')) {
            $query->where('cooperative_member_id', (int) $request->input('member_id'));
        }

        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', (int) $request->input('cashier_id'));
        }

        if ($request->filled('payment_method')) {
            $method = $request->string('payment_method')->toString();
            $query->whereHas('payments', fn ($q) => $q->where('payment_method', $method));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return Inertia::render('Cooperative/Pos/Transactions/Index', [
            'transactions' => $query->orderByDesc('sold_at')->paginate(20)->withQueryString(),
            'filters' => $request->only(['date_from', 'date_to', 'transaction_no', 'member_id', 'cashier_id', 'payment_method', 'status']),
            'cashiers' => User::query()
                ->whereHas('posTransactions')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->values(),
            'members' => CooperativeMember::query()
                ->whereHas('posTransactions')
                ->orderBy('name')
                ->get(['id', 'member_no', 'name'])
                ->map(fn (CooperativeMember $member) => [
                    'id' => $member->id,
                    'member_no' => $member->member_no,
                    'name' => $member->name,
                ])
                ->values(),
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
