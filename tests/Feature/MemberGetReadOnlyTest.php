<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberGetReadOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        CooperativeContributionType::factory()->pokok()->create(['code' => 'POKOK']);
        CooperativeContributionType::factory()->wajib(100000)->create(['code' => 'WAJIB']);
    }

    public function test_member_dashboard_get_does_not_create_invoices(): void
    {
        [$user] = $this->makeActiveMember();

        $this->actingAs($user)->get(route('member.dashboard'))->assertOk();

        $this->assertSame(0, CooperativeDuesInvoice::count());
    }

    public function test_member_savings_get_does_not_create_invoices(): void
    {
        [$user] = $this->makeActiveMember();

        $this->actingAs($user)->get(route('member.savings'))->assertOk();

        $this->assertSame(0, CooperativeDuesInvoice::count());
    }

    public function test_dashboard_and_savings_gets_do_not_mutate_invoice_count_or_totals(): void
    {
        [$user, $member] = $this->makeActiveMember();

        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->first();

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'period' => now()->format('Y-m'),
            'amount' => 200000,
            'paid_amount' => 0,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'UNPAID',
        ]);

        $countBefore = CooperativeDuesInvoice::count();
        $sumBefore = (float) CooperativeDuesInvoice::sum('amount');

        $this->actingAs($user)->get(route('member.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('member.savings'))->assertOk();

        $this->assertSame($countBefore, CooperativeDuesInvoice::count());
        $this->assertSame($sumBefore, (float) CooperativeDuesInvoice::sum('amount'));
    }

    /**
     * @return array{0: User, 1: CooperativeMember}
     */
    private function makeActiveMember(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Anggota');

        $member = CooperativeMember::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'status' => 'ACTIVE',
        ]);

        return [$user, $member];
    }
}
