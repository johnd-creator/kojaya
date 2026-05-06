<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosTransactionRequest;
use App\Models\PosProduct;
use App\Services\Cooperative\PosTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosApiController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('access_cooperative_pos'), 403);

        return response()->json([
            'data' => PosProduct::query()
                ->with('category')
                ->where('is_active', true)
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = $request->string('search')->toString();
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StorePosTransactionRequest $request, PosTransactionService $service): JsonResponse
    {
        abort_unless($request->user()?->can('access_cooperative_pos'), 403);

        return response()->json([
            'data' => $service->create($request->validated(), $request->user()),
        ], 201);
    }
}
