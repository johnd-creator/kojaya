<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ApprovePurchaseRequest;
use App\Http\Requests\Procurement\RejectPurchaseRequest;
use App\Http\Requests\Procurement\StorePurchaseRequest;
use App\Models\ApprovalLog;
use App\Models\BudgetLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\SparePart;
use App\Services\Procurement\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseRequestController extends Controller
{
    public function __construct(private readonly ProcurementService $procurement) {}

    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->can('view_pr_all') || $request->user()?->can('create_pr') || $request->user()?->can('approve_pr'),
            403,
            'Unauthorized to view Purchase Requests'
        );

        $prs = PurchaseRequest::query()
            ->forUser()
            ->withCount('items')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (PurchaseRequest $pr) => [
                'id' => $pr->id,
                'title' => $pr->title,
                'status' => $pr->status,
                'total_amount' => (float) $pr->total_amount,
                'submitted_at' => optional($pr->submitted_at)->toISOString(),
                'items_count' => $pr->items_count,
            ]);

        return Inertia::render('Procurement/PurchaseRequests/Index', [
            'requests' => $prs,
            'flashBudgetDetails' => session('budgetDetails'),
            'canCreate' => $request->user()->can('create_pr'),
        ]);
    }

    public function create(Request $request)
    {
        // Permission check
        if (! $request->user()->can('create_pr')) {
            abort(403, 'Unauthorized to create Purchase Request');
        }

        $user = $request->user();
        $glAccounts = BudgetLine::query()
            ->whereHas('budget', fn ($q) => $q->where('organization_id', $user->organization_id))
            ->select('gl_account')
            ->distinct()
            ->orderBy('gl_account')
            ->pluck('gl_account')
            ->values()
            ->all();
        if (count($glAccounts) === 0) {
            $glAccounts = ['6101', '6201', '6301'];
        }

        $spareParts = SparePart::query()
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'unit')
            ->orderBy('name')
            ->get();

        return Inertia::render('Procurement/PurchaseRequests/Create', [
            'glAccounts' => $glAccounts,
            'spareParts' => $spareParts,
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('create_pr'), 403, 'Unauthorized to create Purchase Request');

        $user = $request->user();
        $data = $request->validated();

        $pr = PurchaseRequest::create([
            'organization_id' => $user->organization_id,
            'unit_id' => $user->organization_id,
            'requester_id' => $user->id,
            'title' => $data['title'],
            'cost_center' => $data['cost_center'] ?? null,
            'status' => 'DRAFT',
            'total_amount' => 0,
        ]);

        $total = 0.0;
        foreach ($data['items'] as $item) {
            $amount = (float) $item['qty'] * (float) $item['price'];
            PurchaseRequestItem::create([
                'purchase_request_id' => $pr->id,
                'spare_part_id' => $item['spare_part_id'] ?? null,
                'description' => $item['description'],
                'gl_account' => $item['gl_account'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'amount' => $amount,
            ]);
            $total += $amount;
        }
        $pr->total_amount = $total;
        $pr->save();

        return redirect()->route('procurement.prs.show', $pr);
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        $pr = $purchaseRequest->load('items.sparePart'); // Eager load sparePart

        // Authorization: Requester OR has view_pr_all permission
        if ($pr->requester_id !== $user->id && ! $user->can('view_pr_all')) {
            abort(403, 'Unauthorized to view this Purchase Request');
        }

        $logs = ApprovalLog::query()
            ->where('subject_type', 'PR')
            ->where('subject_id', $pr->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ApprovalLog $l) => [
                'from_status' => $l->from_status,
                'to_status' => $l->to_status,
                'approved_by' => $l->approved_by,
                'note' => $l->note,
                'created_at' => optional($l->created_at)->toISOString(),
            ]);

        return Inertia::render('Procurement/PurchaseRequests/Show', [
            'pr' => [
                'id' => $pr->id,
                'title' => $pr->title,
                'status' => $pr->status,
                'cost_center' => $pr->cost_center,
                'total_amount' => (float) $pr->total_amount,
                'submitted_at' => optional($pr->submitted_at)->toISOString(),
                'items' => $pr->items->map(fn (PurchaseRequestItem $it) => [
                    'id' => $it->id,
                    'spare_part_id' => $it->spare_part_id,
                    'description' => $it->description,
                    'gl_account' => $it->gl_account,
                    'qty' => (float) $it->qty,
                    'price' => (float) $it->price,
                    'amount' => (float) $it->amount,
                ])->all(),
            ],
            'approvalLogs' => $logs,
            'flashBudgetDetails' => session('budgetDetails'),
            'canApprove' => $user->can('approve_pr'),
            'canCreatePo' => $user->can('create_po'),
        ]);
    }

    public function submit(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        // Permission check
        if (auth()->user()->id !== $purchaseRequest->requester_id) {
            abort(403, 'Only requester can submit PR');
        }

        $result = $this->procurement->submitPr($purchaseRequest->load('items'));
        if (! $result['ok']) {
            return back()
                ->with('budgetDetails', $result['details'] ?? null)
                ->withErrors(['budget' => 'Budget tidak mencukupi untuk submit PR.']);
        }

        return back();
    }

    public function approve(ApprovePurchaseRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if (! $request->user()->can('approve_pr')) {
            abort(403, 'Unauthorized to approve PR');
        }

        $result = $this->procurement->approvePr($purchaseRequest, $request->user(), (int) $request->validated('level'));
        if (! $result['ok']) {
            return back()->withErrors(['approval' => 'Tidak boleh approve untuk level ini.']);
        }

        return back();
    }

    public function reject(RejectPurchaseRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if (! $request->user()->can('approve_pr')) {
            abort(403, 'Unauthorized to reject PR');
        }

        $from = $purchaseRequest->status;
        $purchaseRequest->status = 'REJECTED';
        $purchaseRequest->save();
        ApprovalLog::create([
            'subject_type' => 'PR',
            'subject_id' => $purchaseRequest->id,
            'from_status' => $from,
            'to_status' => 'REJECTED',
            'approved_by' => $request->user()->id,
            'note' => $request->validated('note'),
        ]);

        return back();
    }
}
