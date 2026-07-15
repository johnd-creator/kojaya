<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiPageSize;
use App\Contracts\OrganizationScopedQueryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\LinkCooperativeMemberAccountRequest;
use App\Http\Requests\Cooperative\ProcessMemberResignationRequest;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UnlinkCooperativeMemberAccountRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberSensitiveDataRequest;
use App\Http\Resources\CooperativeMemberResource;
use App\Http\Resources\MemberResignationRequestResource;
use App\Models\CooperativeMember;
use App\Models\MemberResignationRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\MemberAccountLinkService;
use App\Services\Cooperative\MemberNumberGenerator;
use App\Services\Cooperative\MemberResignationRequestService;
use App\Services\Cooperative\MemberStatusTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CooperativeMemberApiController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request, OrganizationScopedQueryService $scopeService): JsonResponse
    {
        $this->authorize('viewAny', CooperativeMember::class);

        $members = CooperativeMember::query()
            ->with('organization');

        $scopeService->scopeVisibleTo($members, $request->user());

        $members = $members
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
    ): JsonResponse {
        $this->authorize('create', CooperativeMember::class);

        $memberNo = $memberNumberGenerator->generate();

        $member = DB::transaction(function () use ($request, $headOfficeResolver, $memberNo): CooperativeMember {
            $member = CooperativeMember::query()->create([
                ...$request->safe()->only([
                    'employee_id',
                    'no_anggota',
                    'tanggal_aktif',
                    'nama_anggota',
                    'member_no',
                    'name',
                    'email',
                    'phone',
                    'no_telp',
                    'jenis_anggota',
                    'jenis_kelamin',
                    'kategori',
                    'autodebet',
                ]),
                'organization_id' => $request->user()->organization_id ?? $headOfficeResolver->resolve()->id,
                'no_anggota' => $memberNo,
                'member_no' => $memberNo,
                'joined_at' => $request->validated('tanggal_aktif'),
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
            ]);

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
        AuditLogService $audit,
    ): JsonResponse {
        $this->authorize('update', $member);
        $before = $member->only(['name', 'email', 'phone', 'status', 'validation_status']);

        $member = DB::transaction(function () use ($request, $member): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update([
                ...$request->safe()->only([
                    'employee_id',
                    'no_anggota',
                    'nama_anggota',
                    'name',
                    'email',
                    'no_telp',
                    'phone',
                    'jenis_anggota',
                    'jenis_kelamin',
                    'kategori',
                    'autodebet',
                ]),
            ]);

            return $member->refresh();
        });

        $audit->log('member.profile.updated', 'cooperative.member', $member, [
            'old' => $before,
            'new' => $member->only(['name', 'email', 'phone', 'status', 'validation_status']),
            'reason' => 'Cooperative member profile updated through API.',
        ]);

        return response()->json([
            'data' => new CooperativeMemberResource($member->refresh()->load('organization')),
            'meta' => $this->openingBalanceWizardMeta($member, $request->validated('opening_saving_balance')),
        ]);
    }

    public function updateSensitiveData(
        UpdateCooperativeMemberSensitiveDataRequest $request,
        CooperativeMember $member,
        AuditLogService $audit,
    ): JsonResponse {
        $this->authorize('updateSensitiveData', $member);

        $fields = array_keys($request->validated());
        $member = DB::transaction(function () use ($request, $member): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update($request->validated());

            return $member->refresh();
        });

        $audit->log('member.pii.updated', 'cooperative.member', $member, [
            'new' => ['fields' => $fields],
            'reason' => 'Cooperative member sensitive data updated through dedicated API action.',
        ]);

        return response()->json([
            'data' => new CooperativeMemberResource($member->load('organization')),
        ]);
    }

    public function linkAccount(
        LinkCooperativeMemberAccountRequest $request,
        CooperativeMember $member,
        MemberAccountLinkService $linkService,
    ): JsonResponse {
        $member = $linkService->link($request->user(), $member, User::query()->findOrFail($request->validated('user_id')), $request->validated('reason'));

        return response()->json(['data' => new CooperativeMemberResource($member->load('organization'))]);
    }

    public function unlinkAccount(
        UnlinkCooperativeMemberAccountRequest $request,
        CooperativeMember $member,
        MemberAccountLinkService $linkService,
    ): JsonResponse {
        $member = $linkService->unlink($request->user(), $member, $request->validated('reason'));

        return response()->json(['data' => new CooperativeMemberResource($member->load('organization'))]);
    }

    public function activate(
        Request $request,
        CooperativeMember $member,
        MemberNumberGenerator $memberNumberGenerator,
        MemberStatusTransitionService $transitions,
    ): JsonResponse {
        $this->authorize('activate', $member);

        $updateData = [
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'resigned_at' => null,
        ];

        if (str_starts_with($member->no_anggota ?? '', 'TMP')) {
            $noAnggota = $memberNumberGenerator->generate();
            $updateData['no_anggota'] = $noAnggota;
            $updateData['member_no'] = $noAnggota;
        }

        $member = DB::transaction(function () use ($member, $updateData, $transitions, $request): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->forceFill($updateData)->save();

            return $transitions->activate($member->refresh(), $request->user());
        });

        return response()->json(['data' => new CooperativeMemberResource($member->refresh()->load('organization'))]);
    }

    public function resign(Request $request, CooperativeMember $member, CooperativeMemberService $memberService): JsonResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member, $request->user());

        return response()->json(['data' => new CooperativeMemberResource($member->refresh()->load('organization'))]);
    }

    public function resignationRequests(Request $request, OrganizationScopedQueryService $scopeService): JsonResponse
    {
        $this->authorize('viewAny', MemberResignationRequest::class);

        $query = MemberResignationRequest::query()
            ->with(['member.organization', 'reviewer']);
        $scopeService->scopeVisibleTo($query, $request->user());

        $query
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
