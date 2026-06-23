<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Services\Cooperative\CooperativeOpeningBalanceService;
use App\Services\Cooperative\MemberNumberGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CooperativeMemberApiController extends Controller
{
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
        $this->authorize('create', CooperativeMember::class);

        $memberNo = $memberNumberGenerator->generate();

        $member = CooperativeMember::query()->create([
            ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
            'organization_id' => $headOfficeResolver->resolve()->id,
            'no_anggota' => $memberNo,
            'member_no' => $memberNo,
            'joined_at' => $request->input('joined_at') ?: now()->toDateString(),
            'status' => $request->input('status', 'PENDING'),
        ]);

        $userProvisioningService->provision($member, $request->validated('member_login_password'));

        $openingBalanceWarning = $this->resolveOpeningBalanceWarning(
            $member,
            $request->validated('opening_saving_balance'),
            $request->user(),
            $openingBalanceService,
        );

        return response()->json([
            'data' => $member->load('organization'),
            'meta' => array_filter([
                'opening_balance' => $openingBalanceWarning,
            ]),
        ], 201);
    }

    public function show(Request $request, CooperativeMember $member): JsonResponse
    {
        $this->authorize('view', $member);

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
        $this->authorize('update', $member);

        $member->update([
            ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
            'organization_id' => $headOfficeResolver->resolve()->id,
        ]);

        $userProvisioningService->provision($member->refresh(), $request->validated('member_login_password'));

        $openingBalanceWarning = $this->resolveOpeningBalanceWarning(
            $member->refresh(),
            $request->validated('opening_saving_balance'),
            $request->user(),
            $openingBalanceService,
        );

        return response()->json([
            'data' => $member->refresh()->load('organization'),
            'meta' => array_filter([
                'opening_balance' => $openingBalanceWarning,
            ]),
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

        $member->update($updateData);

        $userProvisioningService->provision($member->refresh());

        return response()->json(['data' => $member->refresh()]);
    }

    public function resign(Request $request, CooperativeMember $member, CooperativeMemberService $memberService): JsonResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member);

        return response()->json(['data' => $member->refresh()]);
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

    /**
     * Tentukan apakah API harus menulis ledger legacy atau hanya memberi
     * warning bahwa wizard saldo awal adalah jalur yang benar.
     *
     * - Jika user punya permission wizard dan nominal > 0, **tidak** menulis
     *   ledger sama sekali dan kembalikan metadata berisi URL/state wizard
     *   agar client admin bisa mengarahkan operator ke wizard.
     * - Jika anggota sudah punya batch aktif (POSTED/DRAFT), tolak tulis
     *   legacy agar ledger wizard tidak tertimpa.
     * - Jika user tidak punya permission wizard dan anggota belum punya
     *   batch aktif, tulis entry legacy seperti perilaku lama.
     *
     * @return array<string, mixed>|null
     */
    private function resolveOpeningBalanceWarning(
        CooperativeMember $member,
        mixed $openingSavingBalance,
        mixed $user,
        CooperativeOpeningBalanceService $openingBalanceService,
    ): ?array {
        $amount = is_numeric($openingSavingBalance) ? (float) $openingSavingBalance : 0.0;

        if ($amount <= 0) {
            return null;
        }

        $hasWizardBatch = $member->activeOpeningBalanceBatch() !== null;

        if ($hasWizardBatch) {
            return [
                'mode' => 'wizard_locked',
                'message' => 'Anggota sudah memiliki batch saldo awal (wizard). Saldo awal tidak lagi dapat diisi lewat API; gunakan endpoint wizard untuk koreksi.',
                'wizard_url' => route('cooperative.members.opening-balance.show', $member, false),
            ];
        }

        if ($user !== null && method_exists($user, 'can') && $user->can('manage_cooperative_opening_balance')) {
            return [
                'mode' => 'wizard_required',
                'message' => 'Saldo awal historis harus diisi melalui Wizard Saldo Awal agar tercatat rapi ke ledger per kategori dan periode.',
                'wizard_url' => route('cooperative.members.opening-balance.show', $member, false),
            ];
        }

        // User tanpa permission wizard: tulis ledger legacy sebagai fallback
        // (untuk operator/admin lama yang belum bermigrasi ke wizard).
        $openingBalanceService->sync($member, $amount);

        return null;
    }
}
