<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Exports\AnggotaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\LinkCooperativeMemberAccountRequest;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberSensitiveDataRequest;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberPageDataService;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Services\Cooperative\DuesGenerationService;
use App\Services\Cooperative\MemberNumberGenerator;
use App\Services\Cooperative\MemberStatusTransitionService;
use App\Services\Cooperative\SavingsSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CooperativeMemberController extends Controller
{
    public function index(
        Request $request,
        OrganizationScopedQueryService $scopeService,
        CooperativeMemberPageDataService $memberPageData,
    ): Response {
        $this->authorize('viewAny', CooperativeMember::class);

        $scopedQuery = CooperativeMember::query()
            ->with('organization')
            ->withSum('ledgerEntries as saving_balance', 'credit')
            ->withSum('ledgerEntries as credit_balance', 'debit');

        $scopeService->scopeVisibleTo($scopedQuery, $request->user());

        $query = $scopedQuery;

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('no_anggota', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%")
                    ->orWhere('nama_anggota', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%");

                $identityIndex = CooperativeMember::blindIndexFor('identity_number', $search);
                $npwpIndex = CooperativeMember::blindIndexFor('npwp', $search);
                $query->when($identityIndex, fn ($query) => $query->orWhere('identity_number_bidx', $identityIndex))
                    ->when($npwpIndex, fn ($query) => $query->orWhere('npwp_bidx', $npwpIndex));
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'INACTIVE') {
                $query->whereIn('status', ['INACTIVE', 'RESIGNED']);
            } else {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->filled('validation_status')) {
            $query->where('validation_status', $request->input('validation_status'));
        }

        foreach (['jenis_anggota', 'kategori'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $statsQuery = CooperativeMember::query();
        $scopeService->scopeVisibleTo($statsQuery, $request->user());

        return Inertia::render('Cooperative/Members/Index', [
            'members' => $query->orderBy('no_anggota')->paginate(15)->through(
                fn (CooperativeMember $member): array => $memberPageData->list($member),
            )->withQueryString(),
            'filters' => $request->only(['search', 'status', 'jenis_anggota', 'kategori', 'validation_status']),
            'options' => $this->options(),
            'stats' => Inertia::defer(function () use ($statsQuery): array {
                $clone = fn () => clone $statsQuery;

                return [
                    'active' => (clone $clone())->where('status', 'ACTIVE')->count(),
                    'inactive' => (clone $clone())->whereIn('status', ['INACTIVE', 'RESIGNED'])->count(),
                    'alb' => (clone $clone())->where('jenis_anggota', 'ALB')->count(),
                    'pending_validation' => (clone $clone())
                        ->whereIn('validation_status', ['PENDING', 'PENDING_VALIDATION'])
                        ->count(),
                    'rejected' => (clone $clone())->where('validation_status', 'REJECTED')->count(),
                ];
            }, 'member-stats'),
        ]);
    }

    public function create(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $this->authorize('create', CooperativeMember::class);

        $employees = Employee::query()->select('id', 'first_name', 'last_name', 'employee_code');
        $users = User::query()->select('id', 'name', 'email');
        $scopeService->scopeVisibleTo($employees, $request->user());
        $scopeService->scopeVisibleTo($users, $request->user());

        return Inertia::render('Cooperative/Members/Create', [
            'employees' => $employees->orderBy('first_name')->get(),
            'users' => $users->orderBy('name')->get(),
            'options' => $this->options(),
        ]);
    }

    public function store(
        StoreCooperativeMemberRequest $request,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        DuesGenerationService $duesGenerationService,
    ): RedirectResponse {
        $this->authorize('create', CooperativeMember::class);

        $data = $this->memberPayload($request);

        $member = DB::transaction(function () use ($data, $headOfficeResolver, $request): CooperativeMember {
            $member = CooperativeMember::query()->create([
                ...$data,
                'organization_id' => $request->user()->organization_id ?? $headOfficeResolver->resolve()->id,
                'status' => CooperativeMember::VALIDATION_PENDING,
                'validation_status' => CooperativeMember::VALIDATION_PENDING,
            ]);

            return $member->refresh();
        });
        $openingSavingBalance = $request->validated('opening_saving_balance');

        if ($this->shouldUseOpeningBalanceWizard($openingSavingBalance)) {
            $duesGenerationService->ensureOneTimeInvoice($member);

            return redirect()
                ->route('cooperative.members.opening-balance.show', $member)
                ->with(
                    'info',
                    'Anggota baru dibuat. Lengkapi saldo awal melalui Wizard Saldo Awal agar POKOK/WAJIB historis tercatat dengan benar.',
                );
        }

        $duesGenerationService->ensureOneTimeInvoice($member);

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member created successfully.');
    }

    public function show(
        Request $request,
        CooperativeMember $member,
        SavingsSummaryService $savingsSummary,
        AuditLogService $audit,
        CooperativeMemberPageDataService $memberPageData,
    ): Response {
        $this->authorize('view', $member);

        $member->load(['organization', 'ledgerEntries'])
            ->loadSum('ledgerEntries as saving_balance', 'credit');

        if ($request->user()?->can(\App\Enums\PermissionEnum::COOPERATIVE_MEMBER_PII_VIEW->value)) {
            $audit->log('member.pii.viewed', 'cooperative.member', $member);
        }

        return Inertia::render('Cooperative/Members/Show', [
            'member' => $memberPageData->detail($member, $request->user()),
            'openingSavingBalance' => $member->ledgerEntries->firstWhere('entry_type', 'OPENING_BALANCE')?->credit,
            'savingsSummary' => $savingsSummary->summary($member),
            'recentSavingsEntries' => $savingsSummary->ledgerQuery($member)
                ->latest('posted_at')
                ->latest('id')
                ->limit(10)
                ->get()
                ->map(fn ($entry): array => $memberPageData->ledgerEntry($entry))
                ->all(),
        ]);
    }

    public function edit(
        Request $request,
        CooperativeMember $member,
        OrganizationScopedQueryService $scopeService,
        CooperativeMemberPageDataService $memberPageData,
    ): Response {
        $this->authorize('update', $member);

        $member->load('ledgerEntries');

        $employees = Employee::query()->select('id', 'first_name', 'last_name', 'employee_code');
        $users = User::query()->select('id', 'name', 'email');
        $scopeService->scopeVisibleTo($employees, $request->user());
        $scopeService->scopeVisibleTo($users, $request->user());

        return Inertia::render('Cooperative/Members/Edit', [
            'member' => $memberPageData->edit($member, $request->user()),
            'employees' => $employees->orderBy('first_name')->get(),
            'users' => $users->orderBy('name')->get(),
            'openingSavingBalance' => $member->ledgerEntries->firstWhere('entry_type', 'OPENING_BALANCE')?->credit,
            'options' => $this->options(),
        ]);
    }

    public function update(
        UpdateCooperativeMemberRequest $request,
        CooperativeMember $member,
        AuditLogService $audit,
    ): RedirectResponse {
        $this->authorize('update', $member);
        $before = $member->only(['name', 'email', 'phone', 'status', 'validation_status']);

        $member = DB::transaction(function () use ($request, $member): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update([
                ...$this->profilePayload($request),
            ]);

            return $member->refresh();
        });

        $audit->log('member.profile.updated', 'cooperative.member', $member, [
            'old' => $before,
            'new' => $member->only(['name', 'email', 'phone', 'status', 'validation_status']),
            'reason' => 'Cooperative member profile updated.',
        ]);

        $openingSavingBalance = $request->validated('opening_saving_balance');

        if ($this->shouldUseOpeningBalanceWizard($openingSavingBalance, $member)) {
            return redirect()
                ->route('cooperative.members.opening-balance.show', $member)
                ->with(
                    'info',
                    'Perubahan tersimpan. Lengkapi saldo awal melalui Wizard Saldo Awal agar POKOK/WAJIB historis tercatat dengan benar.',
                );
        }

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member updated successfully.');
    }

    public function updateSensitiveData(
        UpdateCooperativeMemberSensitiveDataRequest $request,
        CooperativeMember $member,
        AuditLogService $audit,
    ): RedirectResponse {
        $this->authorize('updateSensitiveData', $member);

        $fields = array_keys($request->validated());
        DB::transaction(function () use ($request, $member): void {
            CooperativeMember::query()->lockForUpdate()->findOrFail($member->id)->update($request->validated());
        });

        $audit->log('member.pii.updated', 'cooperative.member', $member->refresh(), [
            'new' => ['fields' => $fields],
            'reason' => 'Cooperative member sensitive data updated through dedicated action.',
        ]);

        return back()->with('success', 'Data sensitif anggota berhasil diperbarui.');
    }

    public function linkAccount(
        LinkCooperativeMemberAccountRequest $request,
        CooperativeMember $member,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        AuditLogService $audit,
    ): RedirectResponse {
        $this->authorize('update', $member);
        $previousUserId = $member->user_id;

        $member = DB::transaction(function () use ($request, $member, $userProvisioningService): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->forceFill(['user_id' => $request->validated('user_id')])->save();
            $userProvisioningService->provision($member->refresh());

            return $member->refresh();
        });

        if ($previousUserId !== null && (int) $previousUserId !== (int) $member->user_id) {
            User::query()->find($previousUserId)?->removeRole('Anggota');
        }

        $audit->log('member.account.linked', 'cooperative.member', $member, [
            'old' => ['user_id' => $previousUserId],
            'new' => ['user_id' => $member->user_id],
            'reason' => $request->validated('reason'),
        ]);

        return back()->with('success', 'Akun anggota berhasil ditautkan.');
    }

    /**
     * Tentukan apakah wizard saldo awal baru harus diprioritaskan
     * dibandingkan jalur legacy opening_saving_balance.
     *
     * Kapan wizard diprioritaskan:
     * - User mengisi nominal saldo awal > 0.
     * - Semua input dialihkan ke wizard; endpoint member tidak boleh lagi
     *   menulis ledger opening balance secara langsung.
     */
    private function shouldUseOpeningBalanceWizard(mixed $openingSavingBalance, ?CooperativeMember $member = null): bool
    {
        $amount = is_numeric($openingSavingBalance) ? (float) $openingSavingBalance : 0.0;

        if ($amount <= 0) {
            return false;
        }

        return true;
    }

    public function activate(
        CooperativeMember $member,
        DuesGenerationService $duesGenerationService,
        MemberStatusTransitionService $transitions,
    ): RedirectResponse {
        $this->authorize('activate', $member);

        $updateData = [
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'tanggal_aktif' => $member->tanggal_aktif ?: now()->toDateString(),
            'resigned_at' => null,
        ];

        if (str_starts_with($member->no_anggota ?? '', 'TMP')) {
            $noAnggota = app(MemberNumberGenerator::class)->generate();
            $updateData['no_anggota'] = $noAnggota;
            $updateData['member_no'] = $noAnggota;
        }

        $member = DB::transaction(function () use ($member, $updateData, $transitions): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->forceFill($updateData)->save();

            return $transitions->activate($member->refresh(), request()->user());
        });
        $duesGenerationService->ensureOneTimeInvoice($member->refresh());

        return back()->with('success', 'Cooperative member activated successfully.');
    }

    public function deactivate(CooperativeMember $member, MemberStatusTransitionService $transitions): RedirectResponse
    {
        $this->authorize('update', $member);

        if ($member->status !== 'ACTIVE') {
            return back()->with('error', 'Hanya anggota aktif yang dapat dinonaktifkan.');
        }

        $transitions->deactivate($member, request()->user());

        return back()->with('success', 'Anggota berhasil dinonaktifkan.');
    }

    public function resign(CooperativeMember $member, CooperativeMemberService $memberService): RedirectResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member, request()->user());

        return back()->with('success', 'Cooperative member resigned successfully.');
    }

    public function destroy(CooperativeMember $member, MemberStatusTransitionService $transitions): RedirectResponse
    {
        $this->authorize('delete', $member);

        $transitions->deleteAccess($member, request()->user(), 'Member deleted by admin.');

        $member->delete();

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Anggota berhasil dihapus.');
    }

    public function export(
        Request $request,
        OrganizationScopedQueryService $scopeService,
        AuditLogService $audit,
    ): BinaryFileResponse {
        $this->authorize('export', CooperativeMember::class);
        $visibility = $scopeService->visibilityFor($request->user());

        $audit->log('member.pii.exported', 'cooperative.member', null, [
            'new' => [
                'filters' => $request->only(['search', 'status', 'jenis_anggota', 'kategori']),
                'scope' => $visibility->global ? 'global' : 'organization',
                'organization_id' => $visibility->organizationId,
            ],
            'reason' => 'Cooperative member export requested.',
        ]);

        return Excel::download(
            new AnggotaExport(
                $request->only(['search', 'status', 'jenis_anggota', 'kategori']),
                $visibility,
            ),
            'daftar-anggota.xlsx'
        );
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

    private function memberPayload(
        StoreCooperativeMemberRequest|UpdateCooperativeMemberRequest $request,
        ?CooperativeMember $member = null,
    ): array {
        $data = $request->safe()->except(['member_login_password', 'opening_saving_balance']);
        if (! $request->user()?->can(\App\Enums\PermissionEnum::COOPERATIVE_MEMBER_PII_WRITE->value)) {
            unset(
                $data['identity_number'],
                $data['npwp'],
                $data['no_rekening'],
                $data['nama_bank'],
                $data['nama_pemilik_rekening'],
                $data['address'],
                $data['notes'],
            );
        }
        $input = $data['no_anggota'] ?? null;
        $noAnggota = filled($input) ? $input : ($member?->no_anggota ?? app(MemberNumberGenerator::class)->generate());

        return [
            ...$data,
            'no_anggota' => $noAnggota,
            'member_no' => $noAnggota,
            'name' => $data['name'],
            'no_telp' => $data['no_telp'] ?? $data['phone'] ?? null,
            'phone' => $data['no_telp'] ?? $data['phone'] ?? null,
            'joined_at' => $data['tanggal_aktif'],
            'no_rekening' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function profilePayload(UpdateCooperativeMemberRequest $request): array
    {
        return $request->safe()->only([
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
        ]);
    }

    private function options(): array
    {
        return [
            'statuses' => [
                ['value' => 'ACTIVE', 'label' => 'AKTIF'],
                ['value' => 'INACTIVE', 'label' => 'NON-AKTIF'],
            ],
            'validationStatuses' => [
                ['value' => 'PENDING', 'label' => 'Menunggu Validasi'],
                ['value' => 'PENDING_VALIDATION', 'label' => 'Menunggu Pengurus'],
                ['value' => 'REVISION', 'label' => 'Perlu Revisi'],
                ['value' => 'REJECTED', 'label' => 'Ditolak'],
                ['value' => 'ACTIVE', 'label' => 'Disetujui'],
            ],
            'jenisAnggota' => [
                ['value' => 'AB', 'label' => 'Anggota Biasa'],
                ['value' => 'ALB', 'label' => 'Anggota Luar Biasa'],
            ],
            'jenisKelamin' => [
                ['value' => 'L', 'label' => 'Laki-laki'],
                ['value' => 'P', 'label' => 'Perempuan'],
            ],
            'kategori' => [
                ['value' => 'IP', 'label' => 'Indonesia Power'],
                ['value' => 'CDB', 'label' => 'Cogindo DayaBersama'],
                ['value' => 'KOP', 'label' => 'Koperasi'],
            ],
            'autodebet' => [
                ['value' => 'BNI', 'label' => 'BNI'],
                ['value' => 'BRI', 'label' => 'BRI'],
                ['value' => 'MANUAL', 'label' => 'Manual'],
            ],
        ];
    }
}
