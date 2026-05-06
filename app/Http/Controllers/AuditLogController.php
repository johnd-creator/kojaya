<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view_audit_logs');

        $query = AuditLog::with('user');

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('module')) {
            $query->forModule($request->module);
        }

        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->forDateRange($request->date_from, $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return Response::json([
            'data' => AuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $this->authorizePermission('view_audit_logs');

        $log = AuditLog::with('user')->findOrFail($id);

        return Response::json(new AuditLogResource($log));
    }

    public function history(string $subjectType, string $subjectId): JsonResponse
    {
        $this->authorizePermission('view_audit_logs');

        $logs = AuditLog::with('user')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderBy('created_at', 'desc')
            ->get();

        return Response::json(AuditLogResource::collection($logs));
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorizePermission('export_audit_logs');

        $query = AuditLog::query();

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('module')) {
            $query->forModule($request->module);
        }

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->forDateRange($request->date_from, $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        return Response::json([
            'data' => AuditLogResource::collection($logs),
            'exported_at' => now()->toIso8601String(),
        ]);
    }
}
