<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreCooperativeMemberRequest;
use App\Http\Requests\Cooperative\UpdateCooperativeMemberRequest;
use App\Models\CooperativeMember;
use App\Models\Employee;
use App\Models\User;
use App\Services\Cooperative\CooperativeHeadOfficeResolver;
use App\Services\Cooperative\MemberNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CooperativeMemberController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CooperativeMember::query()
            ->with(['organization', 'employee', 'user'])
            ->withSum('ledgerEntries as saving_balance', 'credit')
            ->withSum('ledgerEntries as credit_balance', 'debit');

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
            'stats' => [
                'active' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
                'pending' => CooperativeMember::query()->where('status', 'PENDING')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cooperative/Members/Create', [
            'employees' => Employee::query()->select('id', 'first_name', 'last_name', 'employee_code')->orderBy('first_name')->get(),
            'users' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreCooperativeMemberRequest $request,
        CooperativeHeadOfficeResolver $headOfficeResolver,
        MemberNumberGenerator $memberNumberGenerator,
    ): RedirectResponse {
        CooperativeMember::query()->create([
            ...$request->validated(),
            'organization_id' => $headOfficeResolver->resolve()->id,
            'member_no' => $memberNumberGenerator->generate(),
            'joined_at' => $request->input('joined_at') ?: now()->toDateString(),
            'status' => $request->input('status', 'PENDING'),
        ]);

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member created successfully.');
    }

    public function show(CooperativeMember $member): Response
    {
        $member->load(['organization', 'employee', 'user', 'documents', 'invoices.contributionType', 'payments', 'ledgerEntries']);

        return Inertia::render('Cooperative/Members/Show', [
            'member' => $member,
        ]);
    }

    public function edit(CooperativeMember $member): Response
    {
        return Inertia::render('Cooperative/Members/Edit', [
            'member' => $member,
            'employees' => Employee::query()->select('id', 'first_name', 'last_name', 'employee_code')->orderBy('first_name')->get(),
            'users' => User::query()->select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateCooperativeMemberRequest $request,
        CooperativeMember $member,
        CooperativeHeadOfficeResolver $headOfficeResolver,
    ): RedirectResponse {
        $member->update([
            ...$request->validated(),
            'organization_id' => $headOfficeResolver->resolve()->id,
        ]);

        return redirect()->route('cooperative.members.index')
            ->with('success', 'Cooperative member updated successfully.');
    }

    public function activate(CooperativeMember $member): RedirectResponse
    {
        $member->update([
            'status' => 'ACTIVE',
            'joined_at' => $member->joined_at ?: now()->toDateString(),
            'resigned_at' => null,
        ]);

        return back()->with('success', 'Cooperative member activated successfully.');
    }

    public function resign(CooperativeMember $member): RedirectResponse
    {
        $member->update([
            'status' => 'RESIGNED',
            'resigned_at' => now()->toDateString(),
        ]);

        return back()->with('success', 'Cooperative member resigned successfully.');
    }
}
