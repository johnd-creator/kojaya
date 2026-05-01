<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\StorePosCategoryRequest;
use App\Http\Requests\Cooperative\UpdatePosCategoryRequest;
use App\Models\PosCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Cooperative/Inventory/Categories/Index', [
            'categories' => PosCategory::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StorePosCategoryRequest $request): RedirectResponse
    {
        PosCategory::query()->create($request->validated());

        return back()->with('success', 'POS category created successfully.');
    }

    public function update(UpdatePosCategoryRequest $request, PosCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('success', 'POS category updated successfully.');
    }

    public function destroy(PosCategory $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with existing products.');
        }

        $category->delete();

        return back()->with('success', 'POS category deleted successfully.');
    }
}
