<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Models\RewardRedemption;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RewardRedemptionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RewardRedemption::query()->with(['reward', 'member']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Cooperative/Redemptions/Index', [
            'redemptions' => $query->latest('redeemed_at')->paginate(10)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }
}
