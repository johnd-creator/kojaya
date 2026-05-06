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
