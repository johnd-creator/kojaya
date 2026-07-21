<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosTransactionRequest;
use App\Models\CooperativeMember;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosRegisterController extends Controller
{
    public function index(Request $request): Response
    {
        $members = CooperativeMember::query()
            ->where('organization_id', $request->user()->organization_id)
            ->active()
            ->with('storeAccount')
            ->orderBy('name')
            ->get(['id', 'member_no', 'name'])
            ->map(fn (CooperativeMember $member): array => [
                'id' => $member->id,
                'member_no' => $member->member_no,
                'name' => $member->name,
                'store_account' => $member->storeAccount
                    ? [
                        'balance' => (int) $member->storeAccount->balance,
                        'credit_limit' => (int) $member->storeAccount->credit_limit,
                        'available_spending' => (int) $member->storeAccount->availableCredit(),
                        'status' => $member->storeAccount->status->value,
                        'status_label' => $member->storeAccount->status->label(),
                    ]
                    : null,
            ]);

        return Inertia::render('Cooperative/Pos/Register', [
            'products' => PosProduct::query()->with('category')->where('is_active', true)->orderBy('name')->get(),
            'categories' => PosCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'members' => $members,
        ]);
    }

    public function store(StorePosTransactionRequest $request, PosTransactionService $service): RedirectResponse|JsonResponse
    {
        $transaction = $service->create($request->validated(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['data' => $transaction], 201);
        }

        return back()->with('success', "POS transaction {$transaction->transaction_no} completed.");
    }
}
