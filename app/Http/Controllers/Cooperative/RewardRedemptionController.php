<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\UpdateRedemptionStatusRequest;
use App\Models\RewardRedemption;
use App\Services\Cooperative\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RewardRedemptionController extends Controller
{
    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $this->authorize('viewAny', RewardRedemption::class);

        $query = RewardRedemption::query()->with(['reward', 'member']);
        $scopeService->scopeVisibleTo($query, $request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Redemptions/Index', [
            'redemptions' => $query->latest('redeemed_at')->paginate(10)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(
        Request $request,
        string $redemption,
        OrganizationScopedQueryService $scopeService
    ): Response {
        /** @var RewardRedemption $redemptionModel */
        $redemptionModel = $scopeService->resolveVisible(RewardRedemption::class, $request->user(), $redemption);

        $this->authorize('view', $redemptionModel);

        $redemptionModel->load(['reward', 'member', 'pointTransaction']);

        return Inertia::render('Cooperative/Redemptions/Show', [
            'redemption' => $redemptionModel,
        ]);
    }

    public function updateStatus(
        UpdateRedemptionStatusRequest $request,
        string $redemption,
        PointService $pointService,
        OrganizationScopedQueryService $scopeService
    ): RedirectResponse {
        /** @var RewardRedemption $redemptionModel */
        $redemptionModel = $scopeService->resolveVisible(RewardRedemption::class, $request->user(), $redemption);

        $this->authorize('update', $redemptionModel);

        $status = $request->validated('status');

        $pointService->updateRedemptionStatus(
            redemption: $redemptionModel,
            status: $status,
            notes: $request->validated('notes'),
        );

        $messages = [
            'PROCESSING' => 'Redemption sedang diproses.',
            'SHIPPED' => 'Redemption telah dikirim.',
            'DELIVERED' => 'Redemption telah diterima.',
            'CANCELLED' => 'Redemption telah dibatalkan.',
        ];

        return back()->with('success', $messages[$status] ?? 'Status redemption diperbarui.');
    }
}
