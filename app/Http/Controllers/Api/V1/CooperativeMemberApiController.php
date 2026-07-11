<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ProcessMemberResignationRequest;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Http\Resources\CooperativeMemberResource;
use App\Http\Resources\MemberResignationRequestResource;
use App\Models\CooperativeMember;
use App\Models\MemberResignationRequest;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Services\Cooperative\MemberNumberGenerator;
use App\Services\Cooperative\MemberResignationRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CooperativeMemberApiController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CooperativeMember::class);

        $members = CooperativeMember::query()
            ->with('organization')
            ->when(! $this->canViewAllMembers($request), fn ($query) => $query->where('user_id', $request->user()?->id))
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
            ->paginate($this->apiPageSize($request));

        return CooperativeMemberResource::collection($members)->response();
    }

    public function store(
        StoreCooperativeMemberRequest $request,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        MemberNumberGenerator $memberNumberGenerator,
        CooperativeMemberUserProvisioningService $userProvisioningService,
    ): JsonResponse {
        $this->authorize('create', CooperativeMember::class);

        $memberNo = $memberNumberGenerator->generate();

        $member = DB::transaction(function () use ($request, $headOfficeResolver, $memberNo, $userProvisioningService): CooperativeMember {
            $member = CooperativeMember::query()->create([
                ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
                'organization_id' => $headOfficeResolver->resolve()->id,
                'no_anggota' => $memberNo,
                'member_no' => $memberNo,
                'joined_at' => $request->input('joined_at') ?: now()->toDateString(),
                'status' => $request->input('status', 'PENDING'),
            ]);

            $userProvisioningService->provision($member, $request->validated('member_login_password'));

            return $member->refresh();
        });

        return response()->json([
            'data' => new CooperativeMemberResource($member->load('organization')),
            'meta' => $this->openingBalanceWizardMeta($member, $request->validated('opening_saving_balance')),
        ], 201);
    }

    public function show(Request $request, CooperativeMember $member, AuditLogService $audit): JsonResponse
    {
        $this->authorize('view', $member);

        if ($request->user()?->can(\App\Enums\PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value)) {
            $audit->log('member.pii.viewed', 'cooperative.member', $member);
        }

        return response()->json([
            'data' => new CooperativeMemberResource($member->load('organization')),
        ]);
    }

    public function update(
        UpdateCooperativeMemberRequest $request,
        CooperativeMember $member,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        CooperativeMemberUserProvisioningService $userProvisioningService,
    ): JsonResponse {
        $this->authorize('update', $member);

        $member = DB::transaction(function () use ($request, $member, $headOfficeResolver, $userProvisioningService): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update([
                ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
                'organization_id' => $headOfficeResolver->resolve()->id,
            ]);

            $userProvisioningService->provision($member->refresh(), $request->validated('member_login_password'));

            return $member->refresh();
        });

        return response()->json([
            'data' => new CooperativeMemberResource($member->refresh()->load('organization')),
            'meta' => $this->openingBalanceWizardMeta($member, $request->validated('opening_saving_balance')),
        ]);
    }

    public function activate(
        Request $request,
        CooperativeMember $member,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        MemberNumberGenerator $memberNumberGenerator,
    ): JsonResponse {
        $this->authorize('activate', $member);

        $updateData = [
            'status' => 'ACTIVE',
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'resigned_at' => null,
        ];

        if (str_starts_with($member->no_anggota ?? '', 'TMP')) {
            $noAnggota = $memberNumberGenerator->generate();
            $updateData['no_anggota'] = $noAnggota;
            $updateData['member_no'] = $noAnggota;
        }

        $member = DB::transaction(function () use ($member, $updateData, $userProvisioningService): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update($updateData);
            $userProvisioningService->provision($member->refresh());

            return $member->refresh();
        });

        return response()->json(['data' => new CooperativeMemberResource($member->refresh()->load('organization'))]);
    }

    public function resign(Request $request, CooperativeMember $member, CooperativeMemberService $memberService): JsonResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member);

        return response()->json(['data' => new CooperativeMemberResource($member->refresh()->load('organization'))]);
    }

    public function resignationRequests(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MemberResignationRequest::class);

        $query = MemberResignationRequest::query()
            ->with(['member.organization', 'reviewer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = $request->string('search')->toString();
                $q->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%")
                        ->orWhere('no_anggota', 'like', "%{$search}%");
                });
            });

        $query->orderByRaw("CASE status WHEN 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        return MemberResignationRequestResource::collection(
            $query->paginate($this->apiPageSize($request))
        )->response();
    }

    public function processResignationRequest(
        ProcessMemberResignationRequest $request,
        MemberResignationRequest $resignationRequest,
        MemberResignationRequestService $service,
    ): JsonResponse {
        $this->authorize('approve', $resignationRequest);

        $decision = $request->validated('decision');

        if ($decision === 'APPROVE') {
            $service->approve($resignationRequest, $request->user(), $request->validated('review_notes'));

            return response()->json([
                'data' => new MemberResignationRequestResource($resignationRequest->refresh()->load(['member.organization', 'reviewer'])),
                'message' => 'Pengunduran diri disetujui dan status anggota diperbarui menjadi RESIGNED.',
            ]);
        }

        $service->reject($resignationRequest, $request->user(), $request->validated('review_notes'));

        return response()->json([
            'data' => new MemberResignationRequestResource($resignationRequest->refresh()->load(['member.organization', 'reviewer'])),
            'message' => 'Pengajuan pengunduran diri ditolak.',
        ]);
    }

    private function canViewAllMembers(Request $request): bool
    {
        $user = $request->user();

        return $user?->can('view_cooperative_all')
            || $user?->can('manage_cooperative_member')
            || $user?->can('manage_cooperative_payment')
            || $user?->can('access_cooperative_pos')
            || $user?->can('view_cooperative_report');
    }

    /** @return array<string, array<string, mixed>> */
    private function openingBalanceWizardMeta(CooperativeMember $member, mixed $openingSavingBalance): array
    {
        $amount = is_numeric($openingSavingBalance) ? (float) $openingSavingBalance : 0.0;

        if ($amount <= 0) {
            return [];
        }

        return [
            'opening_balance' => [
                'mode' => 'wizard_required',
                'message' => 'Saldo awal historis harus diisi melalui Wizard Saldo Awal agar tercatat rapi ke ledger per kategori dan periode.',
                'wizard_url' => route('cooperative.members.opening-balance.show', $member, false),
            ],
        ];
    }
}
