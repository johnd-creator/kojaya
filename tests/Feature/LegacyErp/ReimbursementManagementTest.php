<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Reimbursement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReimbursementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_employee_can_submit_reimbursement_with_multiple_items(): void
    {
        $organization = Organization::factory()->create();
        $employee = User::factory()->create(['organization_id' => $organization->id]);
        $employee->assignRole('Employee');

        $this->actingAs($employee)
            ->from(route('reimbursements.create'))
            ->post(route('reimbursements.store'), [
                'submission_date' => now()->toDateString(),
                'description' => 'Perjalanan dinas dan makan',
                'items' => [
                    [
                        'category' => 'TRANSPORT',
                        'description' => 'Taksi bandara',
                        'amount' => 150000,
                        'receipt_date' => now()->subDay()->toDateString(),
                    ],
                    [
                        'category' => 'MEAL',
                        'description' => 'Makan siang meeting',
                        'amount' => 85000,
                        'receipt_date' => now()->subDay()->toDateString(),
                    ],
                ],
            ])
            ->assertRedirect(route('reimbursements.index'));

        $reimbursement = Reimbursement::query()->first();

        $this->assertNotNull($reimbursement);
        $this->assertSame($organization->id, $reimbursement->organization_id);
        $this->assertSame($employee->id, $reimbursement->user_id);
        $this->assertSame('SUBMITTED', $reimbursement->status);
        $this->assertSame('235000.00', $reimbursement->total_amount);
        $this->assertCount(2, $reimbursement->items);
    }

    public function test_employee_cannot_view_other_employee_reimbursement(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $organization->id]);
        $owner->assignRole('Employee');
        $viewer = User::factory()->create(['organization_id' => $organization->id]);
        $viewer->assignRole('Employee');
        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'status' => 'SUBMITTED',
        ]);

        $this->actingAs($viewer)
            ->get(route('reimbursements.show', $reimbursement))
            ->assertForbidden();
    }

    public function test_reimbursement_reject_requires_reason(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Unit');
        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'SUBMITTED',
        ]);

        $this->actingAs($finance)
            ->from(route('reimbursements.show', $reimbursement))
            ->post(route('reimbursements.reject', $reimbursement), [
                'rejection_reason' => '',
            ])
            ->assertRedirect(route('reimbursements.show', $reimbursement))
            ->assertSessionHasErrors(['rejection_reason']);

        $this->assertSame('SUBMITTED', $reimbursement->fresh()->status);
    }

    public function test_finance_can_approve_and_pay_reimbursement(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Unit');
        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'SUBMITTED',
        ]);

        $this->actingAs($finance)
            ->from(route('reimbursements.show', $reimbursement))
            ->post(route('reimbursements.approve', $reimbursement))
            ->assertRedirect(route('reimbursements.show', $reimbursement));

        $reimbursement->refresh();
        $this->assertSame('APPROVED', $reimbursement->status);
        $this->assertSame($finance->id, $reimbursement->approver_id);

        $this->actingAs($finance)
            ->from(route('reimbursements.show', $reimbursement))
            ->post(route('reimbursements.pay', $reimbursement))
            ->assertRedirect(route('reimbursements.show', $reimbursement));

        $reimbursement->refresh();
        $this->assertSame('PAID', $reimbursement->status);
        $this->assertNotNull($reimbursement->payment_date);
    }

    public function test_finance_cannot_pay_reimbursement_that_is_not_approved(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Unit');
        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'SUBMITTED',
            'payment_date' => null,
        ]);

        $this->actingAs($finance)
            ->from(route('reimbursements.show', $reimbursement))
            ->post(route('reimbursements.pay', $reimbursement))
            ->assertRedirect(route('reimbursements.show', $reimbursement));

        $reimbursement->refresh();
        $this->assertSame('SUBMITTED', $reimbursement->status);
        $this->assertNull($reimbursement->payment_date);
    }

    public function test_paying_paid_reimbursement_twice_is_a_no_op(): void
    {
        $organization = Organization::factory()->create();
        $finance = User::factory()->create(['organization_id' => $organization->id]);
        $finance->assignRole('Finance Unit');
        $reimbursement = Reimbursement::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'APPROVED',
            'payment_date' => null,
        ]);

        $this->actingAs($finance)
            ->from(route('reimbursements.show', $reimbursement))
            ->post(route('reimbursements.pay', $reimbursement))
            ->assertRedirect(route('reimbursements.show', $reimbursement));

        $firstPaymentDate = $reimbursement->fresh()->payment_date;

        $this->actingAs($finance)
            ->from(route('reimbursements.show', $reimbursement))
            ->post(route('reimbursements.pay', $reimbursement->fresh()))
            ->assertRedirect(route('reimbursements.show', $reimbursement));

        $reimbursement->refresh();
        $this->assertSame('PAID', $reimbursement->status);
        $this->assertNotNull($firstPaymentDate);
        $this->assertTrue($reimbursement->payment_date->equalTo($firstPaymentDate));
    }
}
