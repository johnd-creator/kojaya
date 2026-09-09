<?php

namespace Tests\Feature\MemberPortal;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Organization;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberFinancialActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Anggota']);
    }

    public function test_member_activity_history_paginates_beyond_two_hundred_records(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $member->user()->firstOrFail()->assignRole('Anggota');

        for ($index = 0; $index < 201; $index++) {
            CooperativePayment::query()->create([
                'cooperative_member_id' => $member->id,
                'amount' => 1000 + $index,
                'payment_method' => 'TRANSFER',
                'paid_at' => now()->subDays($index)->toDateString(),
                'status' => 'APPROVED',
            ]);
        }

        $this->actingAs($member->user)
            ->get(route('member.transactions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('transactions.total', 201)
                ->where('transactions.last_page', 17)
                ->where('summary.total_activities', 201)
                ->where('summary.payment_count', 201)
                ->where('summary.total_amount', 221100)
            );

        $this->actingAs($member->user)
            ->get(route('member.transactions', [
                'date_from' => now()->subDays(10)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('transactions.total', 11)
                ->where('summary.total_activities', 11)
            );
    }

    public function test_member_activity_history_is_scoped_to_authenticated_member(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $member->user()->firstOrFail()->assignRole('Anggota');
        $otherMember = CooperativeMember::factory()->active()->create();

        CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'amount' => 5000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);
        CooperativePayment::query()->create([
            'cooperative_member_id' => $otherMember->id,
            'amount' => 9000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        $this->actingAs($member->user)
            ->get(route('member.transactions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('transactions.total', 1)
                ->where('summary.total_amount', 5000)
            );
    }

    public function test_member_activity_excludes_foreign_parent_transactions_from_rows_and_summaries(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $member->user()->firstOrFail()->assignRole('Anggota');
        $foreignOrganization = Organization::factory()->create();
        $ownProduct = PosProduct::factory()->create(['organization_id' => $member->organization_id]);
        $foreignProduct = PosProduct::factory()->create(['organization_id' => $foreignOrganization->id]);

        $ownTransaction = PosTransaction::query()->create([
            'organization_id' => $member->organization_id,
            'transaction_no' => 'POS-ACTIVITY-OWN-12345',
            'cooperative_member_id' => $member->id,
            'subtotal' => 12345,
            'discount_amount' => 0,
            'total_amount' => 12345,
            'status' => 'COMPLETED',
            'sold_at' => now()->subMinute(),
        ]);
        PosTransactionItem::query()->create([
            'pos_transaction_id' => $ownTransaction->id,
            'pos_product_id' => $ownProduct->id,
            'quantity' => 2,
            'unit_price' => 6172.5,
            'line_total' => 12345,
        ]);

        $foreignTransaction = PosTransaction::query()->create([
            'organization_id' => $foreignOrganization->id,
            'transaction_no' => 'POS-ACTIVITY-FOREIGN-987654',
            'cooperative_member_id' => $member->id,
            'subtotal' => 987654,
            'discount_amount' => 0,
            'total_amount' => 987654,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);
        PosTransactionItem::query()->create([
            'pos_transaction_id' => $foreignTransaction->id,
            'pos_product_id' => $foreignProduct->id,
            'quantity' => 9,
            'unit_price' => 109739.3333,
            'line_total' => 987654,
        ]);

        $this->actingAs($member->user)
            ->get(route('member.transactions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('transactions.total', 1)
                ->where('summary.total_activities', 1)
                ->where('summary.pos_count', 1)
                ->where('summary.total_amount', 12345)
                ->where('summary.total_transactions', 1)
                ->where('summary.total_items', 2)
                ->where('transactions.data.0.subtitle', 'POS-ACTIVITY-OWN-12345')
            );
    }

    public function test_member_activity_returns_no_pos_history_for_member_without_organization(): void
    {
        $member = CooperativeMember::factory()->active()->create();
        $member->user()->firstOrFail()->assignRole('Anggota');
        PosTransaction::query()->create([
            'organization_id' => $member->organization_id,
            'transaction_no' => 'POS-ACTIVITY-NULL-ORG',
            'cooperative_member_id' => $member->id,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'status' => 'COMPLETED',
            'sold_at' => now(),
        ]);
        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });
        DB::table('cooperative_members')->where('id', $member->id)->update(['organization_id' => null]);

        $this->actingAs($member->user)
            ->get(route('member.transactions'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('transactions.total', 0)
                ->where('summary.pos_count', 0)
                ->where('summary.total_transactions', 0)
                ->where('summary.total_items', 0)
                ->where('summary.total_amount', 0)
            );
    }
}
