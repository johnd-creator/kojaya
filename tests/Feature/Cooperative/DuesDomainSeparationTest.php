<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DuesDomainSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_dues_page_generates_and_lists_only_pokok_and_wajib_invoices(): void
    {
        Carbon::setTestNow('2026-07-02 09:00:00');

        $admin = User::factory()->create();
        $admin->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create();

        $wajib = $this->contributionType('WAJIB', 'Simpanan Wajib', 'WAJIB', 'MONTHLY');
        $pokok = $this->contributionType('POKOK', 'Simpanan Pokok', 'POKOK', 'ONCE');
        $sukarela = $this->contributionType('SUKARELA', 'Simpanan Sukarela', 'SUKARELA', 'MONTHLY');
        $posCharge = $this->contributionType('POS-CREDIT', 'Tagihan Belanja POS', 'POS', 'MONTHLY');

        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $sukarela->id,
            'period' => '2026-07',
            'amount' => 75000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $posCharge->id,
            'period' => '2026-07',
            'amount' => 125000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);

        $this->actingAs($admin)
            ->get(route('cooperative.dues.index', ['period' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cooperative/Dues/Index')
                ->where('stats.total_invoices', 2)
                ->has('invoices.data', 2)
                ->has('contributionTypes', 2)
                ->where('contributionTypes.0.code', 'POKOK')
                ->where('contributionTypes.1.code', 'WAJIB')
                ->where('categories', ['POKOK', 'WAJIB'])
            );

        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $wajib->id,
            'period' => '2026-07',
        ]);
        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'period' => '2026-07',
        ]);
        $this->assertSame(1, $sukarela->invoices()->count());
        $this->assertSame(1, $posCharge->invoices()->count());
    }

    private function contributionType(string $code, string $name, string $category, string $frequency): CooperativeContributionType
    {
        return CooperativeContributionType::query()->create([
            'code' => $code,
            'name' => $name,
            'category' => $category,
            'default_amount' => 100000,
            'frequency' => $frequency,
            'is_active' => true,
        ]);
    }
}
