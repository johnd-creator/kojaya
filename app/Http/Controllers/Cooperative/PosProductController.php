<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosProductRequest;
use App\Http\Requests\Cooperative\StorePosStockAdjustmentRequest;
use App\Http\Requests\Cooperative\UpdatePosProductRequest;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\PosProductImageService;
use App\Services\Cooperative\PosStockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PosProduct::query()->with('category');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('pos_category_id', $request->input('category_id'));
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock', '<=', 'minimum_stock');
        }

        if ($request->boolean('discontinued')) {
            $query->where('is_discontinued', true);
        }

        return Inertia::render('Cooperative/Inventory/Products/Index', [
            'products' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'categories' => PosCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'category_id', 'low_stock', 'discontinued']),
        ]);
    }

    public function store(
        StorePosProductRequest $request,
        PosProductImageService $imageService,
    ): RedirectResponse {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $imageService->storeImage(
                new PosProduct(['sku' => $data['sku'], 'name' => $data['name']]),
                $request->file('image'),
            );
            unset($data['image']);
        }

        PosProduct::query()->create($data);

        return back()->with('success', 'POS product created successfully.');
    }

    public function show(PosProduct $product): Response
    {
        $product->load(['category', 'stockMovements' => fn ($query) => $query->orderByDesc('created_at')->limit(100)]);

        return Inertia::render('Cooperative/Inventory/Products/Show', [
            'product' => $product,
        ]);
    }

    public function update(
        UpdatePosProductRequest $request,
        PosProduct $product,
        PosProductImageService $imageService,
    ): RedirectResponse {
        $data = $request->validated();

        if ($request->boolean('remove_image')) {
            $imageService->deleteImage($product->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $imageService->deleteImage($product->image_path);
            $data['image_path'] = $imageService->storeImage($product, $request->file('image'));
        }

        unset($data['image'], $data['remove_image']);

        $product->update($data);

        return back()->with('success', 'POS product updated successfully.');
    }

    public function destroy(PosProduct $product, PosProductImageService $imageService): RedirectResponse
    {
        if ($product->stockMovements()->exists()) {
            return back()->with('error', 'Cannot delete product with stock movements.');
        }

        $imageService->deleteImage($product->image_path);
        $product->delete();

        return back()->with('success', 'POS product deleted successfully.');
    }

    public function adjustStock(
        StorePosStockAdjustmentRequest $request,
        PosProduct $product,
        PosStockAdjustmentService $service,
    ): RedirectResponse {
        $service->adjust(
            $product,
            $request->validated('movement_type'),
            (int) $request->validated('quantity'),
            $request->validated('notes'),
        );

        return back()->with('success', 'POS stock adjusted successfully.');
    }
}
