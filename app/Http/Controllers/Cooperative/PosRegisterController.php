<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosTransactionRequest;
use App\Models\CooperativeMember;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosRegisterController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Cooperative/Pos/Register', [
            'products' => PosProduct::query()->with('category')->where('is_active', true)->orderBy('name')->get(),
            'categories' => PosCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'members' => CooperativeMember::query()->active()->orderBy('name')->get(['id', 'member_no', 'name']),
        ]);
    }

    public function store(StorePosTransactionRequest $request, PosTransactionService $service): RedirectResponse|JsonResponse
    {
        $transaction = $service->create($request->validated(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['data' => $transaction], 201);
        }

        return back()->with('success', "POS transaction {$transaction->transaction_no} completed.");
    }
}
