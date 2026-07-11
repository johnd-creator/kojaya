<?php

namespace App\Http\Controllers\Api;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TechnicianWorkOrderAttachmentRequest;
use App\Http\Requests\Api\TechnicianWorkOrderCompleteRequest;
use App\Http\Requests\Api\TechnicianWorkOrderEscalateRequest;
use App\Http\Requests\Api\TechnicianWorkOrderPartRequest;
use App\Http\Requests\Api\TechnicianWorkOrderReopenRequest;
use App\Http\Requests\Api\TechnicianWorkOrderSyncRequest as TechnicianWorkOrderSyncPayloadRequest;
use App\Http\Requests\UpdateTechnicianChecklistRequest;
use App\Models\SparePart;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderChecklist;
use App\Models\WorkOrderPart;
use App\Models\WorkOrderSyncRequest;
use App\Models\WorkOrderTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnicianWorkOrderController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WorkOrder::with(['asset', 'organization'])
            ->where('assigned_to', $user->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when(! $request->filled('status'), fn ($query) => $query->whereIn('status', ['OPEN', 'IN_PROGRESS']))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('scheduled_date'), fn ($query) => $query->whereDate('scheduled_date', $request->date('scheduled_date')))
            ->orderByRaw("case priority when 'EMERGENCY' then 4 when 'HIGH' then 3 when 'MEDIUM' then 2 else 1 end desc")
            ->orderBy('scheduled_date')
            ->orderBy('created_at');

        $workOrders = $query->paginate($this->apiPageSize($request));

        return response()->json([
            'success' => true,
            'data' => $workOrders->items(),
            'meta' => [
                'current_page' => $workOrders->currentPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
                'last_page' => $workOrders->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::with(['asset', 'organization', 'checklists', 'parts.sparePart', 'attachments', 'timelines.actor:id,name'])
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
            $fromStatus = $workOrder->status;
            $workOrder->update([
                'status' => 'IN_PROGRESS',
                'started_at' => now(),
                'start_latitude' => $request->input('latitude'),
                'start_longitude' => $request->input('longitude'),
                'start_accuracy' => $request->input('accuracy'),
            ]);
            $this->recordTimeline($workOrder, $request, 'started', $fromStatus, 'IN_PROGRESS', [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'accuracy' => $request->input('accuracy'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work order started',
            'data' => $workOrder,
        ]);
    }

    public function complete(TechnicianWorkOrderCompleteRequest $request, string $id): JsonResponse
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

        $validated = $request->validated();
        $fromStatus = $workOrder->status;

        $workOrder->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
            'completion_latitude' => $validated['latitude'],
            'completion_longitude' => $validated['longitude'],
            'completion_accuracy' => $validated['accuracy'] ?? null,
            'completion_notes' => $validated['notes'] ?? null,
        ]);
        $this->recordTimeline($workOrder, $request, 'completed', $fromStatus, 'COMPLETED', [
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'notes' => $validated['notes'] ?? null,
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
        $this->recordTimeline($checklist->workOrder, $request, 'checklist_updated', $checklist->workOrder->status, $checklist->workOrder->status, [
            'checklist_id' => $checklist->id,
            'is_checked' => $checklist->is_checked,
            'notes' => $checklist->notes,
        ]);

        return response()->json([
            'success' => true,
            'data' => $checklist,
        ]);
    }

    public function storeAttachment(TechnicianWorkOrderAttachmentRequest $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);
        $this->authorizeTechnicianWorkOrder($request, $workOrder);

        $validated = $request->validated();
        $file = $request->file('file');
        $path = $file->store('work-orders/'.$workOrder->id.'/attachments', 'public');

        $attachment = WorkOrderAttachment::query()->create([
            'work_order_id' => $workOrder->id,
            'uploaded_by' => $request->user()->id,
            'type' => $validated['type'],
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'accuracy' => $validated['accuracy'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->recordTimeline($workOrder, $request, 'attachment_uploaded', $workOrder->status, $workOrder->status, [
            'attachment_id' => $attachment->id,
            'type' => $attachment->type,
        ]);

        return response()->json(['success' => true, 'data' => $attachment], 201);
    }

    public function storePart(TechnicianWorkOrderPartRequest $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);
        $this->authorizeTechnicianWorkOrder($request, $workOrder);

        $part = $this->createPart($workOrder, $request->user()->id, $request->validated());
        $this->recordTimeline($workOrder, $request, 'part_recorded', $workOrder->status, $workOrder->status, [
            'work_order_part_id' => $part->id,
            'spare_part_id' => $part->spare_part_id,
            'warehouse_id' => $part->warehouse_id,
            'quantity_used' => $part->quantity_used,
        ]);

        return response()->json(['success' => true, 'data' => $part->load('sparePart', 'warehouse')], 201);
    }

    public function sync(TechnicianWorkOrderSyncPayloadRequest $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);
        $this->authorizeTechnicianWorkOrder($request, $workOrder);
        $validated = $request->validated();

        $existing = WorkOrderSyncRequest::query()
            ->where('work_order_id', $workOrder->id)
            ->where('user_id', $request->user()->id)
            ->where('idempotency_key', $validated['idempotency_key'])
            ->first();

        if ($existing) {
            return response()->json($existing->response_payload);
        }

        $responsePayload = DB::transaction(function () use ($request, $workOrder, $validated): array {
            $updatedChecklists = [];
            foreach ($validated['checklists'] ?? [] as $checklistData) {
                $checklist = WorkOrderChecklist::query()
                    ->where('work_order_id', $workOrder->id)
                    ->findOrFail($checklistData['id']);
                $checklist->update([
                    'is_checked' => $checklistData['is_checked'],
                    'notes' => $checklistData['notes'] ?? $checklist->notes,
                    'checked_by' => $request->user()->id,
                    'checked_at' => now(),
                ]);
                $updatedChecklists[] = $checklist->id;
            }

            $createdParts = [];
            foreach ($validated['parts'] ?? [] as $partData) {
                $createdParts[] = $this->createPart($workOrder, $request->user()->id, $partData)->id;
            }

            if (isset($validated['completion'])) {
                $completion = $validated['completion'];
                $pendingChecklists = $workOrder->checklists()->where('is_checked', false)->count();
                if ($pendingChecklists > 0) {
                    abort(response()->json([
                        'success' => false,
                        'message' => "Cannot complete. {$pendingChecklists} checklist items are pending.",
                    ], 422));
                }

                $fromStatus = $workOrder->status;
                $workOrder->update([
                    'status' => 'COMPLETED',
                    'completed_at' => now(),
                    'completion_latitude' => $completion['latitude'],
                    'completion_longitude' => $completion['longitude'],
                    'completion_accuracy' => $completion['accuracy'] ?? null,
                    'completion_notes' => $completion['notes'] ?? null,
                ]);
                $this->recordTimeline($workOrder, $request, 'completed', $fromStatus, 'COMPLETED', $completion);
            }

            $this->recordTimeline($workOrder, $request, 'offline_sync_processed', $workOrder->getOriginal('status'), $workOrder->status, [
                'idempotency_key' => $validated['idempotency_key'],
                'checklists' => $updatedChecklists,
                'parts' => $createdParts,
                'completed' => isset($validated['completion']),
            ]);

            return [
                'success' => true,
                'data' => [
                    'idempotency_key' => $validated['idempotency_key'],
                    'updated_checklists' => $updatedChecklists,
                    'created_parts' => $createdParts,
                    'work_order' => $workOrder->refresh(),
                ],
            ];
        });

        WorkOrderSyncRequest::query()->create([
            'work_order_id' => $workOrder->id,
            'user_id' => $request->user()->id,
            'idempotency_key' => $validated['idempotency_key'],
            'request_payload' => $validated,
            'response_payload' => $responsePayload,
            'processed_at' => now(),
        ]);

        return response()->json($responsePayload);
    }

    public function timeline(Request $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);
        $this->authorizeTechnicianWorkOrderOrSupervisor($request, $workOrder);

        return response()->json([
            'success' => true,
            'data' => $workOrder->timelines()
                ->with('actor:id,name')
                ->orderBy('occurred_at')
                ->get(),
        ]);
    }

    public function escalate(TechnicianWorkOrderEscalateRequest $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);
        $this->authorizeTechnicianWorkOrder($request, $workOrder);
        $validated = $request->validated();

        $workOrder->update([
            'escalated_at' => now(),
            'escalated_by' => $request->user()->id,
            'escalation_type' => $validated['type'],
            'escalation_reason' => $validated['reason'],
            'reassignment_requested_to' => $validated['reassignment_requested_to'] ?? null,
        ]);
        $this->recordTimeline($workOrder, $request, 'escalated', $workOrder->status, $workOrder->status, [
            'type' => $validated['type'],
            'reason' => $validated['reason'],
            'reassignment_requested_to' => $validated['reassignment_requested_to'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $workOrder->refresh()]);
    }

    public function reopen(TechnicianWorkOrderReopenRequest $request, string $id): JsonResponse
    {
        $workOrder = WorkOrder::findOrFail($id);
        $this->authorizeSupervisor($request);
        $validated = $request->validated();

        if (! in_array($workOrder->status, ['COMPLETED', 'CLOSED'], true)) {
            return response()->json(['success' => false, 'message' => 'Only completed or closed work orders can be reopened.'], 409);
        }

        $fromStatus = $workOrder->status;
        $workOrder->update([
            'status' => 'IN_PROGRESS',
            'reopened_at' => now(),
            'reopened_by' => $request->user()->id,
            'reopen_reason' => $validated['reason'],
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);
        $this->recordTimeline($workOrder, $request, 'reopened', $fromStatus, 'IN_PROGRESS', [
            'reason' => $validated['reason'],
        ]);

        return response()->json(['success' => true, 'data' => $workOrder->refresh()]);
    }

    private function authorizeTechnicianWorkOrder(Request $request, WorkOrder $workOrder): void
    {
        abort_unless((string) $workOrder->assigned_to === (string) $request->user()?->id, 403);
    }

    private function authorizeTechnicianWorkOrderOrSupervisor(Request $request, WorkOrder $workOrder): void
    {
        if ((string) $workOrder->assigned_to === (string) $request->user()?->id) {
            return;
        }

        $this->authorizeSupervisor($request);
    }

    private function authorizeSupervisor(Request $request): void
    {
        abort_unless($request->user()?->tokenCan('work-orders:review') || $request->user()?->can('view_work_order_all'), 403);
    }

    /**
     * @param  array<string, mixed>  $partData
     */
    private function createPart(WorkOrder $workOrder, int $userId, array $partData): WorkOrderPart
    {
        $sparePart = SparePart::query()
            ->where('id', $partData['spare_part_id'])
            ->where('organization_id', $workOrder->organization_id)
            ->firstOrFail();

        return WorkOrderPart::query()->create([
            'work_order_id' => $workOrder->id,
            'spare_part_id' => $sparePart->id,
            'warehouse_id' => $partData['warehouse_id'],
            'quantity_used' => $partData['quantity_used'],
            'notes' => $partData['notes'] ?? null,
            'used_by' => $userId,
            'used_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function recordTimeline(WorkOrder $workOrder, Request $request, string $eventType, ?string $fromStatus, ?string $toStatus, ?array $payload = null): void
    {
        WorkOrderTimeline::query()->create([
            'work_order_id' => $workOrder->id,
            'actor_id' => $request->user()?->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
