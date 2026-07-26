<?php

namespace Tests\Feature\MemberPortal;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
