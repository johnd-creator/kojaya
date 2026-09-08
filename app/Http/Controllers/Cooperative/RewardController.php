<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreRewardRequest;
use App\Http\Requests\Cooperative\UpdateRewardRequest;
use App\Models\Reward;
use App\Services\Authorization\OrganizationScopeService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $this->authorize('viewAny', Reward::class);

        $query = Reward::query()->with('redemptions');
        $scopeService->scopeVisibleTo($query, $request->user());

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $rewards = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('Cooperative/Rewards/Index', [
            'rewards' => $rewards,
            'filters' => $request->only(['category', 'status']),
        ]);
    }

    public function store(
        StoreRewardRequest $request,
        OrganizationScopeService $scopeService
    ): RedirectResponse {
        $this->authorize('create', Reward::class);

        $organizationId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'));

        Reward::query()->create([
            ...$request->safe()->except('organization_id'),
            'organization_id' => $organizationId,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function update(
        UpdateRewardRequest $request,
        string $reward,
        OrganizationScopedQueryService $scopeService
    ): RedirectResponse {
        /** @var Reward $rewardModel */
        $rewardModel = $scopeService->resolveVisible(Reward::class, $request->user(), $reward);

        $this->authorize('update', $rewardModel);

        $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'));
        $authoritativeOrgId = (string) $scopeService->organizationIdForModel($rewardModel);

        if ($authoritativeOrgId !== $targetOrgId) {
            throw new AuthorizationException('Target organization does not match reward organization.');
        }

        $rewardModel->update($request->safe()->except('organization_id'));

        return back()->with('success', 'Reward berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        string $reward,
        OrganizationScopedQueryService $scopeService
    ): RedirectResponse {
        /** @var Reward $rewardModel */
        $rewardModel = $scopeService->resolveVisible(Reward::class, $request->user(), $reward);

        $this->authorize('delete', $rewardModel);

        $targetOrgId = $scopeService->resolveTargetOrganization($request->user(), $request->input('organization_id'));
        $authoritativeOrgId = (string) $scopeService->organizationIdForModel($rewardModel);

        if ($authoritativeOrgId !== $targetOrgId) {
            throw new AuthorizationException('Target organization does not match reward organization.');
        }

        $rewardModel->delete();

        return back()->with('success', 'Reward berhasil dihapus.');
    }
}
