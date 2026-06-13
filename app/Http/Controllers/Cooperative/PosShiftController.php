<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ClosePosCashierShiftRequest;
use App\Http\Requests\Cooperative\OpenPosCashierShiftRequest;
use App\Models\PosCashierShift;
use App\Models\PosInventoryLocation;
use App\Services\Cooperative\PosCashierShiftService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosShiftController extends Controller
{
    public function __construct(private PosCashierShiftService $service) {}

    public function index(): Response
    {
        $openShift = $this->service->getOpenShift(request()->user());

        $shifts = PosCashierShift::query()
            ->with(['cashier', 'location'])
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Cooperative/Pos/Shifts/Index', [
            'openShift' => $openShift,
            'shifts' => $shifts,
            'locations' => PosInventoryLocation::query()->where('is_active', true)->get(),
        ]);
    }

    public function open(OpenPosCashierShiftRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->service->openShift(
            $request->user(),
            (float) ($data['opening_cash'] ?? 0),
            $data['pos_inventory_location_id'] ?? null,
            $data['notes'] ?? null,
        );

        return to_route('cooperative.pos.shifts.index')->with('success', 'Shift dibuka.');
    }

    public function close(ClosePosCashierShiftRequest $request, PosCashierShift $shift): RedirectResponse
    {
        $this->authorize('manage_pos_products');
        $data = $request->validated();
        $this->service->closeShift(
            $shift,
            (float) $data['closing_cash'],
            $data['notes'] ?? null,
        );

        return to_route('cooperative.pos.shifts.index')->with('success', 'Shift ditutup.');
    }
}
