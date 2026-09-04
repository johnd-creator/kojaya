<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiPageSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\RedeemRewardRequest;
use App\Models\CooperativeMember;
use App\Models\Reward;
use App\Services\Cooperative\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardApiController extends Controller
{
    use ResolvesApiPageSize;

    public function index(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request);

        $query = Reward::query()
            ->where('organization_id', $member->organization_id)
            ->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('min_points')) {
            $query->where('points_required', '>=', $request->integer('min_points'));
        }

        return response()->json($query->orderBy('points_required')->paginate($this->apiPageSize($request)));
    }

    public function redeem(
        RedeemRewardRequest $request,
        string $reward,
        PointService $pointService
    ): JsonResponse {
        $member = $this->resolveMember($request);

        /** @var Reward $rewardModel */
        $rewardModel = Reward::query()
            ->where('organization_id', $member->organization_id)
            ->findOrFail($reward);

        $redemption = $pointService->redeem(
            member: $member,
            reward: $rewardModel,
            quantity: (int) $request->validated('quantity'),
            deliveryAddress: $request->validated('delivery_address'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Penukaran reward berhasil. Silakan tunggu konfirmasi admin.',
            'data' => $redemption->load(['reward']),
        ], 201);
    }

    private function resolveMember(Request $request): CooperativeMember
    {
        $user = $request->user();

        abort_unless($user && $user->cooperativeMember, 403);

        return $user->cooperativeMember;
    }
}
