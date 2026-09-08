<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Controllers\Controller;
use App\Models\CooperativeMember;
use App\Services\Cooperative\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointApiController extends Controller
{
    use ResolvesApiPageSize;

    public function balance(Request $request, PointService $pointService): JsonResponse
    {
        $member = $this->resolveMember($request);

        return response()->json([
            'success' => true,
            'data' => [
                'member_id' => $member->id,
                'member_name' => $member->name,
                ...$pointService->balanceSummary($member),
            ],
        ]);
    }

    public function history(Request $request, PointService $pointService): JsonResponse
    {
        $member = $this->resolveMember($request);
        $query = $pointService->historyQuery($member);

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('posted_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('posted_at', '<=', $request->input('end_date'));
        }

        $history = $query->paginate($this->apiPageSize($request))->through(fn ($transaction): array => [
            'id' => $transaction->id,
            'transaction_date' => $transaction->posted_at?->toISOString(),
            'transaction_type' => $transaction->transaction_type,
            'points' => abs((int) $transaction->points),
            'balance_before' => (int) $transaction->balance_before,
            'balance_after' => (int) $transaction->balance_after,
            'description' => $transaction->description,
            'reference_number' => $transaction->reference_number,
        ]);

        return response()->json($history);
    }

    private function resolveMember(Request $request): CooperativeMember
    {
        $user = $request->user();

        abort_unless($user && $user->cooperativeMember, 403);

        $member = $user->cooperativeMember;

        abort_if(blank($member->organization_id), 403, 'Member is not assigned to an organization.');

        return $member;
    }
}
