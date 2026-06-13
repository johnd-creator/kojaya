<?php

namespace App\Http\Controllers\Cooperative;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cooperative\ClosePosDayRequest;
use App\Services\Cooperative\PosDailyClosingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PosDailyClosingController extends Controller
{
    public function __construct(private PosDailyClosingService $service) {}

    public function index(): Response
    {
        $date = request()->input('date', now()->toDateString());

        return Inertia::render('Cooperative/Pos/Closings/Index', [
            'date' => $date,
            'summary' => $this->service->summaryForDate($date),
            'payment_summary' => $this->service->paymentSummaryForDate($date),
            'member_credit_outstanding' => $this->service->memberCreditOutstanding(),
            'is_locked' => $this->service->isLocked($date),
        ]);
    }

    public function close(ClosePosDayRequest $request): RedirectResponse
    {
        $date = $request->validated()['date'];
        $this->service->closeDay($date, $request->user());

        return to_route('cooperative.pos.closings.index', ['date' => $date])
            ->with('success', "Closing harian {$date} berhasil.");
    }
}
