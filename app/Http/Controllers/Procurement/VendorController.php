<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::query()
            ->forUser()
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn (Vendor $v) => [
                'id' => $v->id,
                'code' => $v->code,
                'name' => $v->name,
                'status' => $v->status,
                'rating' => $v->rating,
            ]);

        return Inertia::render('Procurement/Vendors/Index', [
            'vendors' => $vendors,
        ]);
    }
}
