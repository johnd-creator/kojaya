<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StoreRewardRequest;
use App\Http\Requests\Cooperative\UpdateRewardRequest;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RewardController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Reward::query()->with('redemptions');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $rewards = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('Cooperative/Rewards/Index', [
            'rewards' => $rewards,
            'filters' => $request->only(['category', 'status']),
        ]);
    }

    public function store(StoreRewardRequest $request): RedirectResponse
    {
        Reward::query()->create([
            ...$request->validated(),
            'organization_id' => $request->validated('organization_id') ?? $request->user()?->organization_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function update(UpdateRewardRequest $request, Reward $reward): RedirectResponse
    {
        $reward->update($request->validated());

        return back()->with('success', 'Reward berhasil diperbarui.');
    }

    public function destroy(Reward $reward): RedirectResponse
    {
        $reward->delete();

        return back()->with('success', 'Reward berhasil dihapus.');
    }
}
