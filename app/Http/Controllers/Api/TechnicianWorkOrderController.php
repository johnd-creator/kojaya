<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTechnicianChecklistRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianWorkOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $workOrders = WorkOrder::with(['asset', 'organization'])
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['OPEN', 'IN_PROGRESS'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workOrders,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::with(['asset', 'organization', 'checklists', 'parts.sparePart'])
            ->findOrFail($id);

        $this->authorizeTechnicianWorkOrder($request, $workOrder);

        return response()->json([
            'success' => true,
            'data' => $workOrder,
        ]);
    }

    public function start(Request $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);

        $this->authorizeTechnicianWorkOrder($request, $workOrder);

        if ($workOrder->status === 'OPEN') {
            $workOrder->update(['status' => 'IN_PROGRESS']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work order started',
            'data' => $workOrder,
        ]);
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);

        $this->authorizeTechnicianWorkOrder($request, $workOrder);

        // Verify all checklists are done
        $pendingChecklists = $workOrder->checklists()->where('is_checked', false)->count();

        if ($pendingChecklists > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot complete. {$pendingChecklists} checklist items are pending.",
            ], 422);
        }

        $workOrder->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work order completed',
            'data' => $workOrder,
        ]);
    }

    public function updateChecklist(UpdateTechnicianChecklistRequest $request, string $workOrderId, string $checklistId): JsonResponse
    {
        $checklist = WorkOrderChecklist::where('work_order_id', $workOrderId)
            ->findOrFail($checklistId);

        $this->authorizeTechnicianWorkOrder($request, $checklist->workOrder);

        $validated = $request->validated();

        $checklist->update([
            'is_checked' => $validated['is_checked'],
            'notes' => $validated['notes'] ?? $checklist->notes,
            'checked_by' => $request->user()->id,
            'checked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $checklist,
        ]);
    }

    private function authorizeTechnicianWorkOrder(Request $request, WorkOrder $workOrder): void
    {
        abort_unless((string) $workOrder->assigned_to === (string) $request->user()?->id, 403);
    }
}
