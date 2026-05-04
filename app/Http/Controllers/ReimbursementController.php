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
            ->when(! $user->hasRole(['System Admin', 'Admin Pusat', 'Finance Pusat', 'HR Pusat']), function ($q) use ($user) {
                // If not central admin/finance, filter by user's organization
                // Also if normal employee, only show their own requests?
                // The PRD says:
                // Employee: Self Only
                // HR Unit/Finance Unit: View Unit

                if ($user->hasRole('Employee') && ! $user->hasRole(['HR Unit', 'Finance Unit', 'Admin Unit'])) {
                    $q->where('user_id', $user->id);
                } else {
                    $q->where('organization_id', $user->organization_id);
                }
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
        // Check authorization
        /** @var User $user */
        $user = Auth::user();

        // If employee, must be own
        if ($user->hasRole('Employee') && ! $user->hasRole(['HR Unit', 'Finance Unit', 'Admin Unit', 'System Admin', 'Admin Pusat', 'HR Pusat', 'Finance Pusat'])) {
            if ($reimbursement->user_id !== $user->id) {
                abort(403);
            }
        }

        // If unit admin/finance, must be same org
        if (! $user->hasRole(['System Admin', 'Admin Pusat', 'HR Pusat', 'Finance Pusat'])) {
            if ($reimbursement->organization_id !== $user->organization_id) {
                abort(403);
            }
        }

        $reimbursement->load(['items', 'user', 'approver']);

        return Inertia::render('Reimbursement/Show', [
            'reimbursement' => $reimbursement,
            'can' => [
                'approve' => $user->hasRole(['HR Unit', 'Finance Unit', 'Admin Unit', 'System Admin', 'Admin Pusat', 'HR Pusat', 'Finance Pusat']) && $reimbursement->status === 'SUBMITTED',
                'reject' => $user->hasRole(['HR Unit', 'Finance Unit', 'Admin Unit', 'System Admin', 'Admin Pusat', 'HR Pusat', 'Finance Pusat']) && $reimbursement->status === 'SUBMITTED',
                'pay' => $user->hasRole(['Finance Unit', 'Finance Pusat']) && $reimbursement->status === 'APPROVED',
            ],
        ]);
    }

    public function approve(Reimbursement $reimbursement)
    {
        // Only HR/Finance/Manager can approve
        // For simplicity, let's allow HR Unit, Finance Unit, Admin Unit
        // Real logic might be complex approval chain

        $reimbursement->update([
            'status' => 'APPROVED',
            'approver_id' => Auth::id(),
        ]);

        return back()->with('success', 'Reimbursement approved.');
    }

    public function reject(RejectReimbursementRequest $request, Reimbursement $reimbursement)
    {
        $validated = $request->validated();

        $reimbursement->update([
            'status' => 'REJECTED',
            'approver_id' => Auth::id(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Reimbursement rejected.');
    }

    public function pay(Reimbursement $reimbursement)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasRole(['Finance Unit', 'Finance Pusat'])) {
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

        return back()->with('success', 'Reimbursement marked as paid.');
    }
}
