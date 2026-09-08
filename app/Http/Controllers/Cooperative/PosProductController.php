<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosProductRequest;
use App\Http\Requests\Cooperative\StorePosStockAdjustmentRequest;
use App\Http\Requests\Cooperative\UpdatePosProductRequest;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Services\Cooperative\PosCategoryAccessService;
use App\Services\Cooperative\PosProductAccessService;
use App\Services\Cooperative\PosProductImageService;
use App\Services\Cooperative\PosStockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosProductController extends Controller
{
    public function index(
        Request $request,
        PosProductAccessService $productAccess,
        PosCategoryAccessService $categoryAccess,
    ): Response {
        $query = $productAccess->scopeVisibleTo(PosProduct::query(), $request->user())->with('category');

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
            $categoryId = $request->input('category_id');
            if (! $categoryAccess->isVisibleId($categoryId, $request->user())) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('pos_category_id', $categoryId);
            }
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock', '<=', 'minimum_stock');
        }

        if ($request->boolean('discontinued')) {
            $query->where('is_discontinued', true);
        }

        return Inertia::render('Cooperative/Inventory/Products/Index', [
            'products' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'categories' => $categoryAccess->scopeVisibleTo(
                PosCategory::query()->where('is_active', true),
                $request->user()
            )->orderBy('name')->get(),
            'filters' => $request->only(['search', 'category_id', 'low_stock', 'discontinued']),
        ]);
    }

    public function store(
        StorePosProductRequest $request,
        PosProductImageService $imageService,
        PosProductAccessService $productAccess,
        PosCategoryAccessService $categoryAccess,
    ): RedirectResponse {
        $data = $request->validated();
        $targetOrgId = $productAccess->assertCanCreate($request->user());
        $data['organization_id'] = $targetOrgId;

        if (! empty($data['pos_category_id'])) {
            $categoryAccess->assertBelongsToOrganization((int) $data['pos_category_id'], $targetOrgId);
        }

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

    public function show(Request $request, PosProduct $product, PosProductAccessService $productAccess): Response
    {
        $productAccess->assertCanOperate($request->user(), $product);
        $product->load(['category', 'stockMovements' => fn ($query) => $query->orderByDesc('created_at')->limit(100)]);

        return Inertia::render('Cooperative/Inventory/Products/Show', [
            'product' => $product,
        ]);
    }

    public function update(
        UpdatePosProductRequest $request,
        PosProduct $product,
        PosProductImageService $imageService,
        PosProductAccessService $productAccess,
        PosCategoryAccessService $categoryAccess,
    ): RedirectResponse {
        $productAccess->assertCanOperate($request->user(), $product);
        $data = $request->validated();

        if (! empty($data['pos_category_id'])) {
            $categoryAccess->assertBelongsToOrganization((int) $data['pos_category_id'], (string) $product->organization_id);
        }

        unset($data['organization_id']);

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

    public function destroy(Request $request, PosProduct $product, PosProductImageService $imageService, PosProductAccessService $productAccess): RedirectResponse
    {
        $productAccess->assertCanOperate($request->user(), $product);
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
        PosProductAccessService $productAccess,
    ): RedirectResponse {
        $productAccess->assertCanOperate($request->user(), $product);
        $service->adjust(
            $product,
            $request->validated('movement_type'),
            (int) $request->validated('quantity'),
            $request->validated('notes'),
            $request->user(),
        );

        return back()->with('success', 'POS stock adjusted successfully.');
    }
}
