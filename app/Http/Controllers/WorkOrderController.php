<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkOrderRequest;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $request->query('organization_id');
        $status = $request->query('status');

        $query = WorkOrder::with(['asset', 'organization', 'assignedTo']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $workOrders = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('WorkOrders/Index', [
            'workOrders' => $workOrders,
        ]);
    }

    public function create()
    {
        $assets = \App\Models\Asset::with('organization')->orderBy('code')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();
        $users = \App\Models\User::orderBy('name')->get();

        return Inertia::render('WorkOrders/Create', [
            'assets' => $assets,
            'organizations' => $organizations,
            'users' => $users,
        ]);
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load([
            'asset',
            'organization',
            'assignedTo',
            'parts.sparePart',
            'checklists.checkedBy',
        ]);

        return Inertia::render('WorkOrders/Show', [
            'workOrder' => $workOrder,
        ]);
    }

    public function store(StoreWorkOrderRequest $request)
    {
        $validated = $request->validated();

        $validated['status'] = 'OPEN';

        WorkOrder::create($validated);

        return redirect()->route('work-orders.index')->with('success', 'Work Order created successfully.');
    }
}
