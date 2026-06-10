<?php

namespace App\Http\Controllers\Cooperative;

use App\Exports\AnggotaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\User;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\CooperativeMemberService;
use App\Services\Cooperative\CooperativeMemberUserProvisioningService;
use App\Services\Cooperative\CooperativeOpeningBalanceService;
use App\Services\Cooperative\DuesGenerationService;
use App\Services\Cooperative\SavingsSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CooperativeMemberController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CooperativeMember::class);

        $query = CooperativeMember::query()
            ->with(['organization', 'employee', 'user'])
            ->withSum('ledgerEntries as saving_balance', 'credit')
            ->withSum('ledgerEntries as credit_balance', 'debit');

        if (! $this->canViewAllMembers($request)) {
            $query->where('user_id', $request->user()?->id);
        }

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

        return Inertia::render('Cooperative/Members/Index', [
            'members' => $query->orderBy('no_anggota')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'jenis_anggota', 'kategori', 'validation_status']),
            'options' => $this->options(),
            'stats' => Inertia::defer(fn () => [
                'active' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
                'inactive' => CooperativeMember::query()->whereIn('status', ['INACTIVE', 'RESIGNED'])->count(),
                'alb' => CooperativeMember::query()->where('jenis_anggota', 'ALB')->count(),
                'pending_validation' => CooperativeMember::query()
                    ->whereIn('validation_status', ['PENDING', 'PENDING_VALIDATION'])
                    ->count(),
                'rejected' => CooperativeMember::query()->where('validation_status', 'REJECTED')->count(),
            ], 'member-stats'),
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
        CooperativeOpeningBalanceService $openingBalanceService,
        DuesGenerationService $duesGenerationService,
    ): RedirectResponse {
        $this->authorize('create', CooperativeMember::class);

        $data = $this->memberPayload($request);

        $member = CooperativeMember::query()->create([
            ...$data,
            'organization_id' => $headOfficeResolver->resolve()->id,
        ]);

        $userProvisioningService->provision($member, $request->validated('member_login_password'));
        $openingBalanceService->sync($member, $request->validated('opening_saving_balance'));
        $duesGenerationService->ensureOneTimeInvoice($member);

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member created successfully.');
    }

    public function show(CooperativeMember $member, SavingsSummaryService $savingsSummary): Response
    {
        $this->authorize('view', $member);

        $member->load(['organization', 'employee', 'user.roles', 'documents', 'invoices.contributionType', 'payments', 'ledgerEntries'])
            ->loadSum('ledgerEntries as saving_balance', 'credit');

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
        CooperativeOpeningBalanceService $openingBalanceService,
    ): RedirectResponse {
        $this->authorize('update', $member);

        $member->update([
            ...$this->memberPayload($request, member: $member),
            'organization_id' => $headOfficeResolver->resolve()->id,
        ]);

        $userProvisioningService->provision($member->refresh(), $request->validated('member_login_password'));
        $openingBalanceService->sync($member->refresh(), $request->validated('opening_saving_balance'));

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member updated successfully.');
    }

    public function activate(
        CooperativeMember $member,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        DuesGenerationService $duesGenerationService,
    ): RedirectResponse {
        $this->authorize('activate', $member);

        $member->update([
            'status' => 'ACTIVE',
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'tanggal_aktif' => $member->tanggal_aktif ?: now()->toDateString(),
            'resigned_at' => null,
        ]);

        $userProvisioningService->provision($member->refresh());
        $duesGenerationService->ensureOneTimeInvoice($member->refresh());

        return back()->with('success', 'Cooperative member activated successfully.');
    }

    public function deactivate(CooperativeMember $member): RedirectResponse
    {
        $this->authorize('update', $member);

        if ($member->status !== 'ACTIVE') {
            return back()->with('error', 'Hanya anggota aktif yang dapat dinonaktifkan.');
        }

        $member->forceFill([
            'status' => 'INACTIVE',
            'resigned_at' => null,
        ])->save();

        return back()->with('success', 'Anggota berhasil dinonaktifkan.');
    }

    public function resign(CooperativeMember $member, CooperativeMemberService $memberService): RedirectResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member);

        return back()->with('success', 'Cooperative member resigned successfully.');
    }

    public function destroy(CooperativeMember $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $member->delete();

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Anggota berhasil dihapus.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', CooperativeMember::class);

        return Excel::download(
            new AnggotaExport($request->only(['search', 'status', 'jenis_anggota', 'kategori'])),
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
        $noAnggota = $data['no_anggota'] ?? $member?->no_anggota ?? $this->nextNoAnggota();

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

    private function nextNoAnggota(): string
    {
        $existing = CooperativeMember::query()
            ->withTrashed()
            ->pluck('no_anggota')
            ->filter(fn ($value) => is_string($value) && ctype_digit($value))
            ->map(fn ($value) => (int) $value);

        $candidate = ($existing->max() ?? 0) + 1;

        do {
            $noAnggota = str_pad((string) $candidate, 3, '0', STR_PAD_LEFT);
            $candidate++;
        } while (CooperativeMember::query()->withTrashed()->where('no_anggota', $noAnggota)->exists());

        return $noAnggota;
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
