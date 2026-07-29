<?php

namespace Tests\Feature\Cooperative;

use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PaymentSortBulkTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_payments_index_defaults_to_paid_at_desc_sort(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo('manage_cooperative_payment');

        $this->actingAs($user)
            ->get(route('cooperative.payments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cooperative/Payments/Index')
                ->where('filters.sort_field', 'paid_at')
                ->where('filters.sort_direction', 'desc')
            );
    }

    public function test_payments_sort_respects_whitelist(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo('manage_cooperative_payment');
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'organization_id' => $organization->id,
        ]);

        CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now()->subDay(), 'cooperative_member_id' => $member->id]);
        CooperativePayment::query()->create(['status' => 'APPROVED', 'amount' => 200, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);
        CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 300, 'payment_method' => 'CASH', 'paid_at' => now()->subDays(2), 'cooperative_member_id' => $member->id]);

        $user->givePermissionTo('manage_cooperative_payment');

        // Valid sort field — status ascending
        $response = $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['sort_field' => 'status', 'sort_direction' => 'asc']))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where('filters.sort_field', 'status')
            ->where('filters.sort_direction', 'asc')
        );
    }

    public function test_payments_sort_rejects_invalid_field(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo('manage_cooperative_payment');

        $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['sort_field' => 'member.name', 'sort_direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort_field', 'paid_at')
                ->where('filters.sort_direction', 'asc')
            );
    }

    public function test_payments_sort_rejects_invalid_direction(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo('manage_cooperative_payment');

        $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['sort_field' => 'amount', 'sort_direction' => 'invalid']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.sort_field', 'amount')
                ->where('filters.sort_direction', 'desc')
            );
    }

    public function test_bulk_approve_requires_manage_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [1]])
            ->assertForbidden();
    }

    public function test_bulk_approve_requires_admin_koperasi_role(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage_cooperative_payment');

        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE']);
        $user->forceFill(['organization_id' => $member->organization_id])->save();
        $payment = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [$payment->id]])
            ->assertForbidden();
    }

    public function test_bulk_approve_works_for_admin_koperasi(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');

        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE']);
        $user->forceFill(['organization_id' => $member->organization_id])->save();
        $payment = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [$payment->id]])
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('APPROVED', $payment->fresh()->status);
    }

    public function test_bulk_approve_rejects_unauthenticated(): void
    {
        $this->post(route('cooperative.payments.bulk-approve'), ['ids' => [1]])
            ->assertRedirect(route('login'));
    }

    public function test_bulk_approve_validates_ids_required(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage_cooperative_payment');

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_bulk_approve_validates_ids_exist(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage_cooperative_payment');

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [99999]])
            ->assertSessionHasErrors('ids.0');
    }

    public function test_bulk_approve_approves_pending_payments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE']);
        $user->forceFill(['organization_id' => $member->organization_id])->save();

        $p1 = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);
        $p2 = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 200, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [$p1->id, $p2->id]])
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('APPROVED', $p1->fresh()->status);
        $this->assertSame('APPROVED', $p2->fresh()->status);
    }

    public function test_bulk_approve_skips_non_pending_payments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE']);
        $user->forceFill(['organization_id' => $member->organization_id])->save();

        $pending = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);
        $approved = CooperativePayment::query()->create(['status' => 'APPROVED', 'amount' => 200, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [$pending->id, $approved->id]])
            ->assertSessionHas('success');

        // Pending should be approved, already-approved should remain APPROVED
        $this->assertSame('APPROVED', $pending->fresh()->status);
        $this->assertSame('APPROVED', $approved->fresh()->status);
    }

    public function test_payments_sort_order_by_amount_asc(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->create(['status' => 'ACTIVE']);
        $user->forceFill(['organization_id' => $member->organization_id])->save();

        $p1 = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 300, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);
        $p2 = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);
        $p3 = CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 200, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);

        $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['sort_field' => 'amount', 'sort_direction' => 'asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payments.data.0.id', $p2->id)
                ->where('payments.data.1.id', $p3->id)
                ->where('payments.data.2.id', $p1->id)
                ->where('filters.sort_field', 'amount')
                ->where('filters.sort_direction', 'asc')
            );
    }

    public function test_payments_filter_by_status(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->givePermissionTo('manage_cooperative_payment');
        $member = CooperativeMember::factory()->create([
            'status' => 'ACTIVE',
            'organization_id' => $organization->id,
        ]);

        CooperativePayment::query()->create(['status' => 'PENDING', 'amount' => 100, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);
        CooperativePayment::query()->create(['status' => 'APPROVED', 'amount' => 200, 'payment_method' => 'CASH', 'paid_at' => now(), 'cooperative_member_id' => $member->id]);

        $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['status' => 'PENDING']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', 'PENDING')
                ->component('Cooperative/Payments/Index')
            );
    }

    public function test_admin_payment_list_excludes_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        $otherMember = CooperativeMember::factory()->active()->create(['organization_id' => $otherOrganization->id]);
        $visiblePayment = CooperativePayment::query()->create([
            'status' => 'PENDING',
            'amount' => 100,
            'payment_method' => 'CASH',
            'paid_at' => now(),
            'cooperative_member_id' => $member->id,
        ]);
        CooperativePayment::query()->create([
            'status' => 'PENDING',
            'amount' => 200,
            'payment_method' => 'CASH',
            'paid_at' => now(),
            'cooperative_member_id' => $otherMember->id,
        ]);

        $this->actingAs($user)
            ->get(route('cooperative.payments.index', ['status' => 'PENDING']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('payments.total', 1)
                ->where('payments.data.0.id', $visiblePayment->id)
            );
    }

    public function test_admin_cannot_approve_payment_from_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        $otherMember = CooperativeMember::factory()->active()->create(['organization_id' => $otherOrganization->id]);
        $payment = CooperativePayment::query()->create([
            'status' => 'PENDING',
            'amount' => 200,
            'payment_method' => 'CASH',
            'paid_at' => now(),
            'cooperative_member_id' => $otherMember->id,
        ]);

        $this->actingAs($user)
            ->post(route('cooperative.payments.approve', $payment))
            ->assertForbidden();

        $this->assertSame('PENDING', $payment->fresh()->status);
    }

    public function test_bulk_approve_rejects_mixed_organization_ids(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole('Admin Koperasi');
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        $otherMember = CooperativeMember::factory()->active()->create(['organization_id' => $otherOrganization->id]);
        $payment = CooperativePayment::query()->create([
            'status' => 'PENDING',
            'amount' => 100,
            'payment_method' => 'CASH',
            'paid_at' => now(),
            'cooperative_member_id' => $member->id,
        ]);
        $otherPayment = CooperativePayment::query()->create([
            'status' => 'PENDING',
            'amount' => 200,
            'payment_method' => 'CASH',
            'paid_at' => now(),
            'cooperative_member_id' => $otherMember->id,
        ]);

        $this->actingAs($user)
            ->post(route('cooperative.payments.bulk-approve'), ['ids' => [$payment->id, $otherPayment->id]])
            ->assertForbidden();

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame('PENDING', $otherPayment->fresh()->status);
    }
}
