<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\GenerateDuesRequest;
use App\Models\CooperativeDuesInvoice;
use App\Services\Cooperative\DuesGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CooperativeDuesApiController extends Controller
{
    public function invoices(Request $request): JsonResponse
    {
        $this->authorizeCooperativeAccess($request);

        $query = CooperativeDuesInvoice::query()->with(['member', 'contributionType']);

        if (! $request->user()?->can('manage_cooperative_dues') && $request->user()?->can('view_cooperative_member')) {
            $query->whereHas('member', fn ($query) => $query->where('user_id', $request->user()->id));
        }

        $query
            ->when($request->filled('member_id'), fn ($query) => $query->where('cooperative_member_id', $request->input('member_id')))
            ->when($request->filled('member_search'), function ($query) use ($request): void {
                $search = $request->string('member_search')->toString();
                $query->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('member_no', 'like', "%{$search}%")
                        ->orWhere('no_anggota', 'like', "%{$search}%")
                        ->orWhere('nama_anggota', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->input('period')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('contribution_type_id'), fn ($query) => $query->where('cooperative_contribution_type_id', $request->input('contribution_type_id')))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('contributionType', fn ($typeQuery) => $typeQuery->where('category', $request->input('category'))));

        return response()->json($query->orderByDesc('period')->paginate($request->integer('per_page', 15)));
    }

    public function generate(GenerateDuesRequest $request, DuesGenerationService $service): JsonResponse
    {
        $this->authorizeCooperativeAccess($request, managementOnly: true);

        return response()->json([
            'created' => $service->generateForPeriod($request->validated('period')),
        ], 201);
    }

    private function authorizeCooperativeAccess(Request $request, bool $managementOnly = false): void
    {
        $user = $request->user();

        abort_unless($user, 401);

        if ($managementOnly) {
            abort_unless($user->can('manage_cooperative_dues'), 403);
        } else {
            abort_unless(
                $user->can('manage_cooperative_dues') || $user->can('view_cooperative_member'),
                403,
            );
        }
    }
}
