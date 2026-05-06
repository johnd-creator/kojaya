<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectReimbursementRequest;
use App\Http\Requests\StoreReimbursementRequest;
use App\Models\Reimbursement;
use App\Models\ReimbursementItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReimbursementController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        $reimbursements = Reimbursement::query()
            ->when(! $user->can('manage_reimbursement'), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('Reimbursement/Index', [
            'reimbursements' => $reimbursements,
        ]);
    }

    public function create()
    {
        return Inertia::render('Reimbursement/Create');
    }

    public function store(StoreReimbursementRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $totalAmount = collect($validated['items'])->sum('amount');

            $reimbursement = Reimbursement::create([
                'organization_id' => Auth::user()->organization_id,
                'user_id' => Auth::id(),
                'submission_date' => $validated['submission_date'],
                'total_amount' => $totalAmount,
                'status' => 'SUBMITTED', // Auto submit? Or DRAFT? Let's say SUBMITTED for simplicity
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $path = null;
                if ($request->hasFile("items.{$index}.receipt_file")) {
                    $path = $request->file("items.{$index}.receipt_file")->store('reimbursements', 'public');
                }

                ReimbursementItem::create([
                    'reimbursement_id' => $reimbursement->id,
                    'category' => $itemData['category'],
                    'description' => $itemData['description'],
                    'amount' => $itemData['amount'],
                    'receipt_date' => $itemData['receipt_date'],
                    'receipt_file_path' => $path,
                ]);
            }
        });

        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement submitted successfully.');
    }

    public function show(Reimbursement $reimbursement)
    {
        $user = Auth::user();

        if (! $user->can('manage_reimbursement') && $reimbursement->user_id !== $user->id) {
            abort(403);
        }

        $reimbursement->load(['items', 'user', 'approver']);

        $approvalLogs = $reimbursement->approvalLogItems()
            ->map(fn ($log) => [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'approved_by' => $log->approved_by,
                'note' => $log->note,
                'created_at' => $log->created_at?->toISOString(),
            ]);

        return Inertia::render('Reimbursement/Show', [
            'reimbursement' => $reimbursement,
            'approvalLogs' => $approvalLogs,
            'can' => [
                'approve' => $user->can('approve_reimbursement') && $reimbursement->status === 'SUBMITTED',
                'reject' => $user->can('approve_reimbursement') && $reimbursement->status === 'SUBMITTED',
                'pay' => $user->can('manage_reimbursement') && $reimbursement->status === 'APPROVED',
            ],
        ]);
    }

    public function approve(Reimbursement $reimbursement)
    {
        $previousStatus = $reimbursement->getOriginal('status');

        $reimbursement->update([
            'status' => 'APPROVED',
            'approver_id' => Auth::id(),
        ]);

        $reimbursement->logApproval($previousStatus, 'APPROVED', Auth::user(), 'Reimbursement disetujui');

        return back()->with('success', 'Reimbursement approved.');
    }

    public function reject(RejectReimbursementRequest $request, Reimbursement $reimbursement)
    {
        $validated = $request->validated();
        $previousStatus = $reimbursement->status;

        $reimbursement->update([
            'status' => 'REJECTED',
            'approver_id' => Auth::id(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $reimbursement->logApproval($previousStatus, 'REJECTED', Auth::user(), $validated['rejection_reason']);

        return back()->with('success', 'Reimbursement rejected.');
    }

    public function pay(Reimbursement $reimbursement)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->can('manage_reimbursement')) {
            abort(403);
        }

        if ($reimbursement->status === 'PAID') {
            return back()->with('success', 'Reimbursement already marked as paid.');
        }

        if ($reimbursement->status !== 'APPROVED') {
            return back()->with('error', 'Only approved reimbursements can be marked as paid.');
        }

        $reimbursement->update([
            'status' => 'PAID',
            'payment_date' => now(),
        ]);

        $reimbursement->logApproval('APPROVED', 'PAID', Auth::user(), 'Pembayaran reimbursement selesai');

        return back()->with('success', 'Reimbursement marked as paid.');
    }
}
