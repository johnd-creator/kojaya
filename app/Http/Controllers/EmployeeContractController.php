<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertEmployeeContractRequest;
use App\Models\Employee;
use App\Models\EmployeeContract;
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

    public function store(UpsertEmployeeContractRequest $request, Employee $employee)
    {
        $employee->contracts()->create($request->validated());

        return redirect()->route('employees.contracts.index', $employee)
            ->with('success', 'Contract added successfully.');
    }

    public function update(UpsertEmployeeContractRequest $request, Employee $employee, EmployeeContract $contract)
    {
        $contract->update($request->validated());

        return redirect()->route('employees.contracts.index', $employee)
            ->with('success', 'Contract updated successfully.');
    }
}
