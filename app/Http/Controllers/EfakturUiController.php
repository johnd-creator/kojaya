<?php

namespace App\Http\Controllers;

use App\Models\EfakturBatch;
use App\Models\EfakturSubmission;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EfakturUiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizePermission('manage_efaktur');

        $invoices = Invoice::query()
            ->with(['client'])
            ->whereIn('status', ['APPROVED', 'PAID'])
            ->latest('invoice_date')
            ->paginate(10, ['*'], 'invoices_page');

        $batches = EfakturBatch::query()
            ->withCount('items')
            ->latest()
            ->paginate(10, ['*'], 'batches_page');

        return Inertia::render('Finance/Efaktur/Index', [
            'invoices' => $invoices,
            'batches' => $batches,
        ]);
    }

    public function submitPage(): Response
    {
        $this->authorizePermission('manage_efaktur');

        return Inertia::render('Finance/Efaktur/Submit', [
            'eligibleInvoices' => Invoice::query()
                ->with('client')
                ->whereIn('status', ['APPROVED', 'PAID'])
                ->latest('invoice_date')
                ->get(),
        ]);
    }

    public function status(): Response
    {
        $this->authorizePermission('manage_efaktur');

        return Inertia::render('Finance/Efaktur/Status', [
            'submissions' => EfakturSubmission::query()
                ->with(['invoice.client'])
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }
}
