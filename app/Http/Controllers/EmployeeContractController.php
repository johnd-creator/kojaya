<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeContractController extends Controller
{
    public function index(Employee $employee): Response
    {
        $contracts = $employee->contracts()
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Employee/Contracts', [
            'employee' => $employee->load('organization'),
            'contracts' => $contracts,
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'type' => 'required|in:PKWT,PKWTT',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:ACTIVE,EXPIRED,TERMINATED',
        ]);

        $employee->contracts()->create($validated);

        return redirect()->route('employees.contracts.index', $employee)
            ->with('success', 'Contract added successfully.');
    }

    public function update(Request $request, Employee $employee, EmployeeContract $contract)
    {
        $validated = $request->validate([
            'type' => 'required|in:PKWT,PKWTT',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:ACTIVE,EXPIRED,TERMINATED',
        ]);

        $contract->update($validated);

        return redirect()->route('employees.contracts.index', $employee)
            ->with('success', 'Contract updated successfully.');
    }
}
