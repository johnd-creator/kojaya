<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosTransactionRequest;
use App\Models\CooperativeMember;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\PosCategoryAccessService;
use App\Services\Cooperative\PosProductAccessService;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosRegisterController extends Controller
{
    public function index(
        Request $request,
        PosProductAccessService $productAccess,
        PosCategoryAccessService $categoryAccess,
    ): Response {
        $this->authorizePermission('access_cooperative_pos');

        $user = $request->user();
        $targetOrgId = $user->can('view_cooperative_all')
            ? (session('active_organization_id') ?? $user->organization_id)
            : $user->organization_id;

        $members = CooperativeMember::query()
            ->when($targetOrgId, fn ($q) => $q->where('organization_id', $targetOrgId), fn ($q) => $q->whereRaw('1 = 0'))
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
            'products' => $productAccess->scopeVisibleTo(PosProduct::query(), $request->user())
                ->with('category')->where('is_active', true)->orderBy('name')->get(),
            'categories' => $categoryAccess->scopeVisibleTo(
                PosCategory::query()->where('is_active', true),
                $request->user()
            )->orderBy('name')->get(),
            'members' => $members,
        ]);
    }

    public function store(StorePosTransactionRequest $request, PosTransactionService $service): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('access_cooperative_pos');

        $transaction = $service->create($request->validated(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['data' => $transaction], 201);
        }

        return back()->with('success', "POS transaction {$transaction->transaction_no} completed.");
    }
}
