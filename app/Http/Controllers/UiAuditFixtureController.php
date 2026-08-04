<?php

namespace App\Http\Controllers;

use App\Models\CooperativeMember;
use App\Models\Loan;
use App\Models\MemberStoreAccount;
use App\Models\PosProduct;
use App\Models\PosStockCount;
use App\Models\PosTransaction;
use App\Models\RewardRedemption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class UiAuditFixtureController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(in_array((string) config('app.env'), ['testing', 'playwright'], true), 404);

        return response()->json([
            'member-positive' => CooperativeMember::query()->where('member_no', 'AUD-001')->value('id'),
            'member-pending-review' => CooperativeMember::query()->where('member_no', 'AUD-009')->value('id'),
            'member-revision' => CooperativeMember::query()->where('member_no', 'AUD-010')->value('id'),
            'members-no-account' => CooperativeMember::query()->where('member_no', 'AUD-007')->value('id'),
            'store-credit-positive' => MemberStoreAccount::query()->whereHas('member', fn (Builder $query): Builder => $query->where('member_no', 'AUD-001'))->value('id'),
            'store-credit-negative' => MemberStoreAccount::query()->whereHas('member', fn (Builder $query): Builder => $query->where('member_no', 'AUD-003'))->value('id'),
            'store-credit-suspended' => MemberStoreAccount::query()->whereHas('member', fn (Builder $query): Builder => $query->where('member_no', 'AUD-004'))->value('id'),
            'store-credit-empty-ledger' => MemberStoreAccount::query()->whereHas('member', fn (Builder $query): Builder => $query->where('member_no', 'AUD-005'))->value('id'),
            'loan-applied' => Loan::query()->where('reference_no', 'UI-AUDIT-LOAN-001')->value('id'),
            'product-primary' => PosProduct::query()->where('sku', 'UI-AUD-001')->value('id'),
            'pos-transaction-completed' => PosTransaction::query()->where('transaction_no', 'UI-AUDIT-POS-001')->value('id'),
            'stock-count-draft' => PosStockCount::query()->where('count_no', 'UI-AUDIT-COUNT-001')->value('id'),
            'redemption-pending' => RewardRedemption::query()->whereKey('00000000-0000-0000-0000-000000000023')->value('id'),
        ]);
    }
}
