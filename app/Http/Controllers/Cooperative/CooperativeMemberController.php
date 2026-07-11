<?php

namespace App\Http\Controllers\Cooperative;

use App\Contracts\OrganizationScopedQueryService;
use App\Exports\AnggotaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Services\Cooperative\DuesGenerationService;
use App\Services\Cooperative\MemberAccessRevocationService;
use App\Services\Cooperative\MemberNumberGenerator;
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
    public function index(Request $request, OrganizationScopedQueryService $scopeService): Response
    {
        $this->authorize('viewAny', CooperativeMember::class);

        $scopedQuery = CooperativeMember::query()
            ->with(['organization', 'employee', 'user'])
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
                    ->orWhere('identity_number', 'like', "%{$search}%")
                    ->orWhere('npwp', 'like', "%{$search}%")
                    ->orWhere('no_telp', 'like', "%{$search}%");
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
            'members' => $query->orderBy('no_anggota')->paginate(15)->withQueryString(),
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

    public function create(): Response
    {
        $this->authorize('create', CooperativeMember::class);

        return Inertia::render('Cooperative/Members/Create', [
            'employees' => Employee::query()->select('id', 'first_name', 'last_name', 'employee_code')->orderBy('first_name')->get(),
            'users' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
            'options' => $this->options(),
        ]);
    }

    public function store(
        StoreCooperativeMemberRequest $request,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        DuesGenerationService $duesGenerationService,
    ): RedirectResponse {
        $this->authorize('create', CooperativeMember::class);

        $data = $this->memberPayload($request);

        $member = DB::transaction(function () use ($data, $headOfficeResolver, $userProvisioningService, $request): CooperativeMember {
            $member = CooperativeMember::query()->create([
                ...$data,
                'organization_id' => $headOfficeResolver->resolve()->id,
            ]);

            $userProvisioningService->provision($member, $request->validated('member_login_password'));

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

    public function show(CooperativeMember $member, SavingsSummaryService $savingsSummary, AuditLogService $audit): Response
    {
        $this->authorize('view', $member);

        $member->load(['organization', 'employee', 'user.roles', 'documents', 'invoices.contributionType', 'payments', 'ledgerEntries'])
            ->loadSum('ledgerEntries as saving_balance', 'credit');

        $audit->log('member.pii.viewed', 'cooperative.member', $member);

        return Inertia::render('Cooperative/Members/Show', [
            'member' => $member,
            'openingSavingBalance' => $member->ledgerEntries->firstWhere('entry_type', 'OPENING_BALANCE')?->credit,
            'savingsSummary' => $savingsSummary->summary($member),
            'recentSavingsEntries' => $savingsSummary->ledgerQuery($member)
                ->latest('posted_at')
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }

    public function edit(CooperativeMember $member): Response
    {
        $this->authorize('update', $member);

        $member->load('ledgerEntries');

        return Inertia::render('Cooperative/Members/Edit', [
            'member' => $member,
            'employees' => Employee::query()->select('id', 'first_name', 'last_name', 'employee_code')->orderBy('first_name')->get(),
            'users' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
            'openingSavingBalance' => $member->ledgerEntries->firstWhere('entry_type', 'OPENING_BALANCE')?->credit,
            'options' => $this->options(),
        ]);
    }

    public function update(
        UpdateCooperativeMemberRequest $request,
        CooperativeMember $member,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        CooperativeMemberUserProvisioningService $userProvisioningService,
    ): RedirectResponse {
        $this->authorize('update', $member);

        $member = DB::transaction(function () use ($request, $member, $headOfficeResolver, $userProvisioningService): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update([
                ...$this->memberPayload($request, member: $member),
                'organization_id' => $headOfficeResolver->resolve()->id,
            ]);

            $userProvisioningService->provision($member->refresh(), $request->validated('member_login_password'));

            return $member->refresh();
        });

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
        CooperativeMemberUserProvisioningService $userProvisioningService,
        DuesGenerationService $duesGenerationService,
    ): RedirectResponse {
        $this->authorize('activate', $member);

        $updateData = [
            'status' => 'ACTIVE',
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'tanggal_aktif' => $member->tanggal_aktif ?: now()->toDateString(),
            'resigned_at' => null,
        ];

        if (str_starts_with($member->no_anggota ?? '', 'TMP')) {
            $noAnggota = app(MemberNumberGenerator::class)->generate();
            $updateData['no_anggota'] = $noAnggota;
            $updateData['member_no'] = $noAnggota;
        }

        $member = DB::transaction(function () use ($member, $updateData, $userProvisioningService): CooperativeMember {
            $member = CooperativeMember::query()->lockForUpdate()->findOrFail($member->id);
            $member->update($updateData);
            $userProvisioningService->provision($member->refresh());

            return $member->refresh();
        });
        $duesGenerationService->ensureOneTimeInvoice($member->refresh());

        return back()->with('success', 'Cooperative member activated successfully.');
    }

    public function deactivate(CooperativeMember $member, MemberAccessRevocationService $revocationService): RedirectResponse
    {
        $this->authorize('update', $member);

        if ($member->status !== 'ACTIVE') {
            return back()->with('error', 'Hanya anggota aktif yang dapat dinonaktifkan.');
        }

        $member->forceFill([
            'status' => 'INACTIVE',
            'resigned_at' => null,
        ])->save();

        $revocationService->revokeFor($member->refresh(), 'deactivated', request()->user());

        return back()->with('success', 'Anggota berhasil dinonaktifkan.');
    }

    public function resign(CooperativeMember $member, CooperativeMemberService $memberService): RedirectResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member);

        return back()->with('success', 'Cooperative member resigned successfully.');
    }

    public function destroy(CooperativeMember $member, MemberAccessRevocationService $revocationService): RedirectResponse
    {
        $this->authorize('delete', $member);

        $revocationService->revokeFor($member, 'deleted', request()->user());

        $member->delete();

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Anggota berhasil dihapus.');
    }

    public function export(Request $request, OrganizationScopedQueryService $scopeService): BinaryFileResponse
    {
        $this->authorize('export', CooperativeMember::class);

        return Excel::download(
            new AnggotaExport(
                $request->only(['search', 'status', 'jenis_anggota', 'kategori']),
                $scopeService->scopeOrganizationIdFor($request->user()),
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
            'no_rekening' => $data['autodebet'] === 'MANUAL' ? null : ($data['no_rekening'] ?? null),
        ];
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
