<?php

namespace Tests\Feature\MemberPortal;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\User;
use App\Services\Cooperative\CooperativePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberDuesAfterVoidTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoices_still_listed_after_admin_voids_a_payment(): void
    {
        [$user, $member] = $this->memberUser();
        $admin = User::factory()->create();

        $wajib = CooperativeContributionType::query()->create([
            'code' => 'WAJIB',
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $pokok = CooperativeContributionType::query()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok',
            'category' => 'POKOK',
            'default_amount' => 500000,
            'frequency' => 'ONCE',
            'is_active' => true,
        ]);

        $wajibInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $wajib->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);
        $pokokInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'period' => now()->format('Y-m'),
            'amount' => 500000,
            'paid_amount' => 0,
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'UNPAID',
        ]);

        $service = app(CooperativePaymentService::class);

        // Admin marks both invoices paid.
        $wajibPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $wajibInvoice->id,
            'amount' => 100000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        $service->approve($wajibPayment, $admin);

        $pokokPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $pokokInvoice->id,
            'amount' => 500000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        $service->approve($pokokPayment, $admin);

        // Sanity: both invoices are now PAID.
        $this->assertSame('PAID', $wajibInvoice->fresh()->status);
        $this->assertSame('PAID', $pokokInvoice->fresh()->status);

        // Admin rolls back (voids) the wajib payment.
        $service->voidDuesInvoicePayments($wajibInvoice->fresh(), $admin);

        // Wajib is unpaid again, pokok stays paid.
        $this->assertSame('UNPAID', $wajibInvoice->fresh()->status);
        $this->assertSame('PAID', $pokokInvoice->fresh()->status);

        Sanctum::actingAs($user, ['member:read']);

        // The member-facing invoices list must still contain BOTH invoices.
        $this->getJson('/api/v1/member/dues/invoices')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'UNPAID');

        // The dashboard summary must reflect the single pending invoice.
        $this->getJson('/api/v1/member/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.pending_invoices', 1);

        // The voided (rolled-back) payment must NOT clutter the member's own
        // payment history; the still-valid pokok payment remains visible.
        $this->getJson('/api/v1/member/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'APPROVED')
            ->assertJsonMissing(['data.0.cooperative_dues_invoice_id' => $wajibInvoice->id]);
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\CooperativeMember}
     */
    private function memberUser(): array
    {
        Role::firstOrCreate(['name' => 'Anggota']);
        $user = User::factory()->create();
        $user->assignRole('Anggota');
        $member = CooperativeMember::factory()->active()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return [$user, $member];
    }
}
