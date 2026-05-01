<?php

namespace App\Http\Controllers;

use App\Models\EmployeeTransfer;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeTransferController extends Controller
{
    public function index(Request $request): Response
    {
        $query = EmployeeTransfer::query()
            ->forUser()
            ->with(['employee', 'fromOrganization', 'toOrganization', 'requestedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('organization_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_organization_id', $request->input('organization_id'))
                    ->orWhere('to_organization_id', $request->input('organization_id'));
            });
        }

        $transfers = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('EmployeeTransfer/Index', [
            'transfers' => $transfers,
            'organizations' => Organization::orderBy('name')->get(),
            'filters' => $request->only(['status', 'organization_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('EmployeeTransfer/Create', [
            'employees' => \App\Models\Employee::forUser()
                ->where('status', 'ACTIVE')
                ->with(['organization'])
                ->get(['id', 'employee_code', 'first_name', 'last_name', 'organization_id']),
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'to_organization_id' => 'required|uuid|exists:organizations,id|different:from_organization_id',
            'effective_date' => 'required|date|after:today',
            'reason' => 'nullable|string|max:500',
        ]);

        $employee = \App\Models\Employee::forUser()->findOrFail($validated['employee_id']);

        $transfer = EmployeeTransfer::create([
            'employee_id' => $employee->id,
            'from_organization_id' => $employee->organization_id,
            'to_organization_id' => $validated['to_organization_id'],
            'effective_date' => $validated['effective_date'],
            'reason' => $validated['reason'],
            'status' => 'PENDING',
            'requested_by' => Auth::id(),
        ]);

        return redirect()->route('employee-transfers.index')
            ->with('success', 'Transfer request created successfully.');
    }

    public function show(EmployeeTransfer $transfer): Response
    {
        $transfer->load(['employee', 'fromOrganization', 'toOrganization', 'requestedBy', 'approvedBy']);

        return Inertia::render('EmployeeTransfer/Show', [
            'transfer' => $transfer,
        ]);
    }

    public function approve(Request $request, EmployeeTransfer $transfer)
    {
        if (! $transfer->isPending()) {
            return back()->with('error', 'This transfer has already been processed.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $transfer->approve(Auth::user(), $validated['notes'] ?? null);

        $transfer->employee->update([
            'organization_id' => $transfer->to_organization_id,
        ]);

        return back()->with('success', 'Transfer approved and employee has been moved to the new organization.');
    }

    public function reject(Request $request, EmployeeTransfer $transfer)
    {
        if (! $transfer->isPending()) {
            return back()->with('error', 'This transfer has already been processed.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $transfer->reject(Auth::user(), $validated['notes'] ?? null);

        return back()->with('success', 'Transfer request rejected.');
    }
}
