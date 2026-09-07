<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosCategoryRequest;
use App\Http\Requests\Cooperative\UpdatePosCategoryRequest;
use App\Models\PosCategory;
use App\Services\Cooperative\PosCategoryAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosCategoryController extends Controller
{
    public function index(Request $request, PosCategoryAccessService $categoryAccess): Response
    {
        return Inertia::render('Cooperative/Inventory/Categories/Index', [
            'categories' => $categoryAccess->scopeVisibleTo(
                PosCategory::query()->withCount('products'),
                $request->user()
            )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StorePosCategoryRequest $request, PosCategoryAccessService $categoryAccess): RedirectResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $categoryAccess->assertCanCreate($request->user(), $request->input('organization_id'));

        PosCategory::query()->create($data);

        return back()->with('success', 'POS category created successfully.');
    }

    public function update(UpdatePosCategoryRequest $request, PosCategory|string|int $category, PosCategoryAccessService $categoryAccess): RedirectResponse
    {
        $categoryModel = $categoryAccess->resolveVisible($category, $request->user());
        $data = $request->validated();
        unset($data['organization_id']);

        $categoryModel->update($data);

        return back()->with('success', 'POS category updated successfully.');
    }

    public function destroy(Request $request, PosCategory|string|int $category, PosCategoryAccessService $categoryAccess): RedirectResponse
    {
        $categoryModel = $categoryAccess->resolveVisible($category, $request->user());

        if ($categoryModel->products()->exists()) {
            return back()->with('error', 'Cannot delete category with existing products.');
        }

        $categoryModel->delete();

        return back()->with('success', 'POS category deleted successfully.');
    }
}
