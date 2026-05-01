<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\EFakturExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::query()->forUser()->with(['client', 'unit']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('invoice_no', 'like', "%{$search}%");
        }

        $invoices = $query->latest()->paginate(10);

        return inertia('Invoice/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'client_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get();

        return inertia('Invoice/Create', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        $user = Auth::user();
        $taxRate = $request->input('tax_rate', 0.11);

        $invoice = new Invoice($request->except('tax_rate'));
        $invoice->organization_id = $user->organization_id;
        $invoice->unit_id = $user->organization_id;
        $invoice->tax_amount = round($request->amount * $taxRate, 2);
        $invoice->total_amount = round($request->amount + $invoice->tax_amount, 2);
        $invoice->save();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        $invoice->load(['client', 'unit', 'organization']);

        return inertia('Invoice/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function exportEfakturCsv(Invoice $invoice, EFakturExportService $service)
    {
        $this->authorizeAccess($invoice);
        $invoice->load(['client']);
        $csv = $service->generateCsv($invoice);
        $filename = 'efaktur_'.$invoice->invoice_no.'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        $invoice->load(['client', 'unit']);

        return inertia('Invoice/Edit', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        if ($invoice->status === 'PAID') {
            return back()->with('error', 'Cannot update a paid invoice.');
        }

        $taxRate = $request->input('tax_rate', 0.11);

        $invoice->fill($request->except('tax_rate'));
        $invoice->tax_amount = round($request->amount * $taxRate, 2);
        $invoice->total_amount = round($request->amount + $invoice->tax_amount, 2);

        if ($request->has('status')) {
            $invoice->status = $request->status;
        }

        $invoice->save();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        if ($invoice->status === 'PAID') {
            return back()->with('error', 'Cannot delete a paid invoice.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Submit invoice for approval.
     */
    public function submitForApproval(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        if ($invoice->status !== 'DRAFT') {
            return back()->with('error', 'Only draft invoices can be submitted.');
        }

        $invoice->status = 'PENDING';
        $invoice->save();

        return back()->with('success', 'Invoice submitted for approval.');
    }

    /**
     * Approve invoice.
     */
    public function approve(Invoice $invoice)
    {
        if (! Auth::user()->hasAnyRole(['Finance Pusat', 'Admin Pusat', 'System Admin'])) {
            abort(403, 'You do not have permission to approve invoices.');
        }

        if ($invoice->status !== 'PENDING') {
            return back()->with('error', 'Only pending invoices can be approved.');
        }

        $invoice->status = 'APPROVED';
        $invoice->save();

        return back()->with('success', 'Invoice approved successfully.');
    }

    /**
     * Reject invoice.
     */
    public function reject(Invoice $invoice)
    {
        if (! Auth::user()->hasAnyRole(['Finance Pusat', 'Admin Pusat', 'System Admin'])) {
            abort(403, 'You do not have permission to reject invoices.');
        }

        if ($invoice->status !== 'PENDING') {
            return back()->with('error', 'Only pending invoices can be rejected.');
        }

        $invoice->status = 'DRAFT';
        $invoice->save();

        return back()->with('success', 'Invoice rejected and returned to draft.');
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Invoice $invoice)
    {
        $this->authorizeAccess($invoice);

        if (! in_array($invoice->status, ['APPROVED', 'OVERDUE'])) {
            return back()->with('error', 'Only approved or overdue invoices can be marked as paid.');
        }

        $invoice->status = 'PAID';
        $invoice->save();

        return back()->with('success', 'Invoice marked as paid.');
    }

    /**
     * Check if user can access the invoice.
     */
    protected function authorizeAccess(Invoice $invoice): void
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['System Admin', 'Admin Pusat', 'Finance Pusat'])) {
            return;
        }

        if ($invoice->organization_id !== $user->organization_id) {
            abort(403, 'You do not have permission to access this invoice.');
        }
    }
}
