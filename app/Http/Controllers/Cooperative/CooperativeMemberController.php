<?php

namespace App\Http\Controllers\Cooperative;

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
use App\Services\Cooperative\MemberNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
                $query->where('member_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Members/Index', [
            'members' => $query->orderByDesc('created_at')->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'stats' => Inertia::defer(fn () => [
                'active' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
                'pending' => CooperativeMember::query()->where('status', 'PENDING')->count(),
            ], 'member-stats'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CooperativeMember::class);

        return Inertia::render('Cooperative/Members/Create', [
            'employees' => Employee::query()->select('id', 'first_name', 'last_name', 'employee_code')->orderBy('first_name')->get(),
            'users' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreCooperativeMemberRequest $request,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        MemberNumberGenerator $memberNumberGenerator,
        CooperativeMemberUserProvisioningService $userProvisioningService,
        CooperativeOpeningBalanceService $openingBalanceService,
    ): RedirectResponse {
        $this->authorize('create', CooperativeMember::class);

        $member = CooperativeMember::query()->create([
            ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
            'organization_id' => $headOfficeResolver->resolve()->id,
            'member_no' => $memberNumberGenerator->generate(),
            'joined_at' => $request->input('joined_at') ?: now()->toDateString(),
            'status' => $request->input('status', 'PENDING'),
        ]);

        $userProvisioningService->provision($member, $request->validated('member_login_password'));
        $openingBalanceService->sync($member, $request->validated('opening_saving_balance'));

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member created successfully.');
    }

    public function show(CooperativeMember $member): Response
    {
        $this->authorize('view', $member);

        $member->load(['organization', 'employee', 'user.roles', 'documents', 'invoices.contributionType', 'payments', 'ledgerEntries'])
            ->loadSum('ledgerEntries as saving_balance', 'credit');

        return Inertia::render('Cooperative/Members/Show', [
            'member' => $member,
            'openingSavingBalance' => $member->ledgerEntries->firstWhere('entry_type', 'OPENING_BALANCE')?->credit,
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
            ...$request->safe()->except(['member_login_password', 'opening_saving_balance']),
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
    ): RedirectResponse {
        $this->authorize('activate', $member);

        $member->update([
            'status' => 'ACTIVE',
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'resigned_at' => null,
        ]);

        $userProvisioningService->provision($member->refresh());

        return back()->with('success', 'Cooperative member activated successfully.');
    }

    public function resign(CooperativeMember $member, CooperativeMemberService $memberService): RedirectResponse
    {
        $this->authorize('resign', $member);

        $memberService->resign($member);

        return back()->with('success', 'Cooperative member resigned successfully.');
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
}
