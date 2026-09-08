<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\AdjustMemberPointRequest;
use App\Models\CooperativeMember;
use App\Models\PointTransaction;
use App\Services\Cooperative\PointService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointController extends Controller
{
    public function index(
        Request $request,
        PointService $pointService,
        OrganizationScopedQueryService $scopeService
    ): Response {
        $this->authorize('viewAny', PointTransaction::class);

        $user = $request->user();
        $targetOrgId = $request->input('organization_id');

        $visibility = $scopeService->visibilityFor($user, 'view_cooperative_all');

        if (! $visibility->global) {
            if ($targetOrgId !== null && (string) $targetOrgId !== (string) $user->organization_id) {
                abort(403, 'Cannot access another organization.');
            }
            $targetOrgId = (string) $user->organization_id;
        }

        $query = CooperativeMember::query()->with(['user']);
        $scopeService->scopeVisibleTo($query, $user);

        if ($targetOrgId) {
            $query->where('cooperative_members.organization_id', $targetOrgId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($memberQuery) use ($search): void {
                $memberQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('name')
            ->paginate(10)
            ->through(function (CooperativeMember $member) use ($pointService): array {
                $summary = $pointService->balanceSummary($member);

                return [
                    'id' => $member->id,
                    'member_no' => $member->member_no,
                    'name' => $member->name,
                    'status' => $member->status,
                    'organization_id' => $member->organization_id,
                    ...$summary,
                ];
            })
            ->withQueryString();

        $statsMemberQuery = CooperativeMember::query();
        $scopeService->scopeVisibleTo($statsMemberQuery, $user);
        if ($targetOrgId) {
            $statsMemberQuery->where('cooperative_members.organization_id', $targetOrgId);
        }

        $activeMembers = (clone $statsMemberQuery)->where('status', 'ACTIVE')->count();
        $totalBalance = (clone $statsMemberQuery)->get()->sum(
            fn (CooperativeMember $member): int => $pointService->balanceSummary($member)['total_points']
        );

        return Inertia::render('Cooperative/Points/Index', [
            'members' => $members,
            'filters' => $request->only(['search', 'status', 'organization_id']),
            'stats' => [
                'active_members' => $activeMembers,
                'total_balance' => $totalBalance,
            ],
        ]);
    }

    public function adjust(
        AdjustMemberPointRequest $request,
        string $member,
        PointService $pointService,
        OrganizationScopedQueryService $scopeService
    ): RedirectResponse {
        $user = $request->user();
        $this->authorize('create', PointTransaction::class);

        /** @var CooperativeMember $memberModel */
        $memberModel = $scopeService->resolveVisible(CooperativeMember::class, $user, $member);

        $targetOrgId = $pointService->resolveTargetOrganization($user, $request->input('organization_id'));

        if ((string) $memberModel->organization_id !== $targetOrgId) {
            throw new AuthorizationException('Target organization does not match member organization.');
        }

        $pointService->adjust(
            actor: $user,
            member: $memberModel,
            points: (int) $request->validated('points'),
            description: (string) $request->validated('description'),
            targetOrgId: $targetOrgId,
        );

        return back()->with('success', 'Poin anggota berhasil disesuaikan.');
    }
}
