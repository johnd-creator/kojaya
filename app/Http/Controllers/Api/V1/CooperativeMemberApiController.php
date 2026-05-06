<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Services\Cooperative\CooperativeOpeningBalanceService;
use App\Services\Cooperative\MemberNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CooperativeMemberApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);

        $members = CooperativeMember::query()
            ->with('organization')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('member_no', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json($members);
    }

    public function store(
        StoreCooperativeMemberRequest $request,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        MemberNumberGenerator $memberNumberGenerator,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        CooperativeOpeningBalanceService $openingBalanceService,
    ): JsonResponse {
        $this->authorizeCooperativeAccess($request);

        $member = CooperativeMember::query()->create([
            ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
            'organization_id' => $headOfficeResolver->resolve()->id,
            'member_no' => $memberNumberGenerator->generate(),
            'joined_at' => $request->input('joined_at') ?: now()->toDateString(),
            'status' => $request->input('status', 'PENDING'),
        ]);

        $userProvisioningService->provision($member, $request->validated('member_login_password'));
        $openingBalanceService->sync($member, $request->validated('opening_saving_balance'));

        return response()->json(['data' => $member->load('organization')], 201);
    }

    public function show(Request $request, CooperativeMember $member): JsonResponse
    {
        $this->authorizeCooperativeAccess($request, $member);

        return response()->json([
            'data' => $member->load(['organization', 'documents', 'invoices.contributionType', 'ledgerEntries']),
        ]);
    }

    public function update(
        UpdateCooperativeMemberRequest $request,
        CooperativeMember $member,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        CooperativeOpeningBalanceService $openingBalanceService,
    ): JsonResponse {
        $this->authorizeCooperativeAccess($request, $member);

        $member->update([
            ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
            'organization_id' => $headOfficeResolver->resolve()->id,
        ]);

        $userProvisioningService->provision($member->refresh(), $request->validated('member_login_password'));
        $openingBalanceService->sync($member->refresh(), $request->validated('opening_saving_balance'));

        return response()->json(['data' => $member->refresh()->load('organization')]);
    }

    public function activate(
        Request $request,
        CooperativeMember $member,
        CooperativeMemberUserProvisioningService $userProvisioningService,
    ): JsonResponse {
        $this->authorizeCooperativeAccess($request, $member);

        $member->update([
            'status' => 'ACTIVE',
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'resigned_at' => null,
        ]);

        $userProvisioningService->provision($member->refresh());

        return response()->json(['data' => $member->refresh()]);
    }

    public function resign(Request $request, CooperativeMember $member): JsonResponse
    {
        $this->authorizeCooperativeAccess($request, $member);

        $member->update([
            'status' => 'RESIGNED',
            'resigned_at' => now()->toDateString(),
        ]);

        return response()->json(['data' => $member->refresh()]);
    }

    private function authorizeCooperativeAccess(Request $request, ?CooperativeMember $member = null): void
    {
        $user = $request->user();

        abort_unless($user, 401);

        if ($user->can('manage_cooperative_member')) {
            return;
        }

        if ($member && $user->can('view_cooperative_member') && $member->user_id === $user->id) {
            return;
        }

        abort(403);
    }
}
