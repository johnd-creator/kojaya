<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\CooperativeMember;
use App\Services\Cooperative\PointService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PointController extends Controller
{
    public function index(Request $request, PointService $pointService): Response
    {
        $query = CooperativeMember::query()->with(['user']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($memberQuery) use ($search): void {
                $memberQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('member_no', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('name')
            ->paginate(10)
            ->through(function (CooperativeMember $member) use ($pointService): array {
                $summary = $pointService->balanceSummary($member);

                return [
                    'id' => $member->id,
                    'member_no' => $member->member_no,
                    'name' => $member->name,
                    'status' => $member->status,
                    ...$summary,
                ];
            })
            ->withQueryString();

        return Inertia::render('Cooperative/Points/Index', [
            'members' => $members,
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'active_members' => CooperativeMember::query()->where('status', 'ACTIVE')->count(),
                'total_balance' => CooperativeMember::query()->get()->sum(
                    fn (CooperativeMember $member): int => $pointService->balanceSummary($member)['total_points']
                ),
            ],
        ]);
    }
}
