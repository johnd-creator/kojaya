<?php

namespace App\Http\Controllers\Cooperative;

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
    public function index(Request $request): Response
    {
        $this->authorizeRedemptionView($request);

        $query = RewardRedemption::query()->with(['reward', 'member']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Redemptions/Index', [
            'redemptions' => $query->latest('redeemed_at')->paginate(10)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Request $request, RewardRedemption $redemption): Response
    {
        $this->authorizeRedemptionManagement($request);

        $redemption->load(['reward', 'member', 'pointTransaction']);

        return Inertia::render('Cooperative/Redemptions/Show', [
            'redemption' => $redemption,
        ]);
    }

    public function updateStatus(
        UpdateRedemptionStatusRequest $request,
        RewardRedemption $redemption,
        PointService $pointService
    ): RedirectResponse {
        $this->authorizeRedemptionManagement($request);

        $status = $request->validated('status');

        $pointService->updateRedemptionStatus(
            redemption: $redemption,
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

    private function authorizeRedemptionView(Request $request): void
    {
        abort_unless($request->user()?->can('manage_cooperative_redemption'), 403);
    }

    private function authorizeRedemptionManagement(Request $request): void
    {
        abort_unless($request->user()?->can('manage_cooperative_redemption'), 403);
    }
}
