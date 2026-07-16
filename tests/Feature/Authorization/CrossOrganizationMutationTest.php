<?php

namespace Tests\Feature\Authorization;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Loan;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrossOrganizationMutationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_direct_member_reads_and_mutations_are_rejected_cross_organization(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = $this->scopedActor($organizationA->id, [
            'view_cooperative_member',
            'manage_cooperative_member',
            'update_cooperative_member_pii',
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organizationB->id,
        ]);
        Sanctum::actingAs($actor, [
            'cooperative.member.read',
            'cooperative.member.write',
        ]);

        $this->getJson("/api/v1/members/{$member->id}")->assertForbidden();
        $this->putJson("/api/v1/members/{$member->id}", [
            'nama_anggota' => 'Cross organization member',
            'name' => 'Cross organization member',
            'jenis_anggota' => 'AB',
            'jenis_kelamin' => 'L',
            'kategori' => 'KOP',
            'autodebet' => 'MANUAL',
        ])->assertForbidden();
        $this->patchJson("/api/v1/members/{$member->id}/sensitive-data", [])->assertForbidden();
        $this->postJson("/api/v1/members/{$member->id}/activate")->assertForbidden();
        $this->postJson("/api/v1/members/{$member->id}/resign", [])->assertForbidden();

        $this->assertSame('ACTIVE', $member->fresh()->status);

        config()->set('security.ability_cutover_phase', 'remove');
        config()->set('security.legacy_ability_fallback_enabled', true);
        config()->set('security.legacy_ability_fallback_expires_at', Carbon::now()->addDay()->toISOString());
        Sanctum::actingAs($actor, ['cooperative:read']);

        $this->getJson("/api/v1/members/{$member->id}")->assertForbidden();
    }

    public function test_member_validation_cannot_mutate_a_cross_organization_member(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = $this->scopedActor($organizationA->id, ['verify_cooperative_member']);
        $member = CooperativeMember::factory()->pending()->create([
            'organization_id' => $organizationB->id,
        ]);

        $this->actingAs($actor)
            ->post(route('cooperative.members.reject', $member), ['notes' => 'Cross organization rejection'])
            ->assertForbidden();

        $this->assertSame('PENDING', $member->fresh()->status);
        $this->assertSame('PENDING', $member->fresh()->validation_status);
    }

    public function test_direct_loan_read_and_approval_are_rejected_without_state_change(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = $this->scopedActor($organizationA->id, [
            'view_cooperative_loan',
            'approve_cooperative_loan',
            'manage_cooperative_loan',
        ]);
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $organizationB->id]);
        $loan = Loan::factory()->create([
            'organization_id' => $organizationB->id,
            'cooperative_member_id' => $member->id,
        ]);
        Sanctum::actingAs($actor, [
            'cooperative.loan.read',
            'cooperative.loan.approve',
            'cooperative.loan.write',
        ]);

        $this->getJson("/api/v1/loans/{$loan->id}")->assertForbidden();
        $this->postJson("/api/v1/loans/{$loan->id}/approve", [])->assertForbidden();

        $this->assertSame($loan->status->value ?? $loan->status, $loan->fresh()->status->value ?? $loan->fresh()->status);
    }

    public function test_direct_payment_approval_is_rejected_without_creating_ledger_state(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = $this->scopedActor($organizationA->id, ['manage_cooperative_payment']);
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $organizationB->id]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'CROSS-ORG-'.fake()->unique()->numerify('####'),
            'name' => 'Cross organization test',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => '2026-07',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'cooperative_contribution_type_id' => $type->id,
            'user_id' => $actor->id,
            'amount' => 50000,
            'payment_method' => 'CASH',
            'paid_at' => now()->toDateString(),
            'status' => 'PENDING',
        ]);
        Sanctum::actingAs($actor, ['cooperative.payment.record']);

        $this->postJson("/api/v1/dues/payments/{$payment->id}/approve")->assertForbidden();

        $this->assertSame('PENDING', $payment->fresh()->status);
        $this->assertSame(0, $payment->fresh()->ledgerEntries()->count());
    }

    public function test_mixed_organization_payment_batch_is_rejected_atomically(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $actor = $this->scopedActor($organizationA->id, ['manage_cooperative_payment']);
        $type = CooperativeContributionType::query()->create([
            'code' => 'BATCH-ORG-'.fake()->unique()->numerify('####'),
            'name' => 'Batch organization test',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoiceA = $this->invoiceFor($organizationA->id, $type->id);
        $invoiceB = $this->invoiceFor($organizationB->id, $type->id);
        Sanctum::actingAs($actor, ['cooperative.payment.record']);

        $this->postJson('/api/v1/dues/payments/batch', [
            'invoice_ids' => [$invoiceA->id, $invoiceB->id],
            'payment_method' => 'CASH',
            'paid_at' => '2026-07-15',
        ])->assertForbidden();

        $this->assertSame('UNPAID', $invoiceA->fresh()->status);
        $this->assertSame('UNPAID', $invoiceB->fresh()->status);
        $this->assertSame(0, CooperativePayment::query()->count());
    }

    public function test_loan_index_uses_resource_allowlist(): void
    {
        $organization = Organization::factory()->create();
        $actor = $this->scopedActor($organization->id, ['view_cooperative_loan', 'manage_cooperative_loan']);
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $organization->id]);
        Loan::factory()->create([
            'organization_id' => $organization->id,
            'cooperative_member_id' => $member->id,
        ]);
        Sanctum::actingAs($actor, ['cooperative.loan.read']);

        $response = $this->getJson('/api/v1/loans')->assertOk();

        $response->assertJsonStructure(['data', 'links', 'meta']);
        $response->assertJsonMissingPath('data.0.organization_id');
        $response->assertJsonMissingPath('data.0.user_id');
        $loanKeys = array_keys($response->json('data.0'));
        sort($loanKeys);
        $expectedLoanKeys = [
            'admin_fee',
            'approval_stage',
            'approved_at',
            'approved_by',
            'applied_at',
            'disbursed_at',
            'first_due_date',
            'id',
            'installment_amount',
            'installments',
            'interest_rate',
            'late_fee_per_day',
            'loan_type',
            'loan_type_id',
            'manager_reviewed_at',
            'manager_reviewed_by',
            'member',
            'member_id',
            'notes',
            'outstanding_amount',
            'principal_amount',
            'purpose',
            'reference_no',
            'rejected_at',
            'rejection_reason',
            'status',
            'term_months',
            'total_amount',
            'total_interest_amount',
        ];
        sort($expectedLoanKeys);
        $this->assertSame($expectedLoanKeys, $loanKeys);

        $detailResponse = $this->getJson('/api/v1/loans/'.Loan::query()->firstOrFail()->id)->assertOk();
        $detailKeys = array_keys($detailResponse->json('data'));
        sort($detailKeys);
        $expectedDetailKeys = [...$expectedLoanKeys, 'approval_logs', 'payments'];
        sort($expectedDetailKeys);
        $this->assertSame($expectedDetailKeys, $detailKeys);
    }

    public function test_invoice_and_payment_api_routes_use_exact_resource_contracts(): void
    {
        $organization = Organization::factory()->create();
        $actor = $this->scopedActor($organization->id, [
            'view_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
        ]);
        $member = CooperativeMember::factory()->active()->create([
            'organization_id' => $organization->id,
        ]);
        $type = CooperativeContributionType::query()->create([
            'code' => 'CONTRACT-'.fake()->unique()->numerify('####'),
            'name' => 'Contract contribution',
            'category' => 'WAJIB',
            'default_amount' => 50000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = $this->invoiceForMember($member, $type->id, '2026-07');
        Sanctum::actingAs($actor, [
            'cooperative.dues.read',
            'cooperative.payment.record',
            'cooperative:write',
        ]);

        $invoiceResponse = $this->getJson('/api/v1/dues/invoices')->assertOk();
        $this->assertSame(['data', 'links', 'meta', 'success'], array_keys($invoiceResponse->json()));
        $invoiceKeys = array_keys($invoiceResponse->json('data.0'));
        sort($invoiceKeys);
        $expectedInvoiceKeys = ['amount', 'contribution_type', 'due_date', 'id', 'paid_amount', 'period', 'remaining_amount', 'status'];
        sort($expectedInvoiceKeys);
        $this->assertSame($expectedInvoiceKeys, $invoiceKeys);
        $invoiceResponse->assertJsonMissingPath('data.0.gateway_payload');
        $invoiceResponse->assertJsonMissingPath('data.0.proof_path');

        $paymentResponse = $this->postJson('/api/v1/dues/payments', [
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'amount' => 50000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-07-15',
        ])->assertCreated();
        $this->assertSame(['data', 'success'], array_keys($paymentResponse->json()));
        $paymentKeys = array_keys($paymentResponse->json('data'));
        sort($paymentKeys);
        $expectedPaymentKeys = [
            'amount',
            'approved_at',
            'approved_by',
            'contribution_type',
            'id',
            'invoice',
            'invoice_id',
            'member',
            'member_id',
            'paid_at',
            'payment_method',
            'receipt_issued_at',
            'receipt_no',
            'reference_no',
            'status',
        ];
        sort($expectedPaymentKeys);
        $this->assertSame($expectedPaymentKeys, $paymentKeys);
        $paymentResponse->assertJsonMissingPath('data.gateway_payload');
        $paymentResponse->assertJsonMissingPath('data.proof_path');

        $approver = $this->scopedActor($organization->id, [
            'view_cooperative_member',
            'manage_cooperative_dues',
            'manage_cooperative_payment',
        ]);
        Sanctum::actingAs($approver, [
            'cooperative.dues.read',
            'cooperative.payment.record',
            'cooperative:write',
        ]);
        $approveResponse = $this->postJson('/api/v1/dues/payments/'.$paymentResponse->json('data.id').'/approve')
            ->assertOk();
        $this->assertSame(['data', 'success'], array_keys($approveResponse->json()));
        $approvedKeys = array_keys($approveResponse->json('data'));
        sort($approvedKeys);
        $this->assertSame($expectedPaymentKeys, $approvedKeys);

        $secondInvoice = $this->invoiceForMember($member, $type->id, '2026-08');
        $batchResponse = $this->postJson('/api/v1/dues/payments/batch', [
            'invoice_ids' => [$secondInvoice->id],
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-07-15',
        ])->assertCreated();
        $this->assertSame(['data', 'success'], array_keys($batchResponse->json()));
        $batchKeys = array_keys($batchResponse->json('data'));
        sort($batchKeys);
        $this->assertSame(['payments', 'processed_count', 'total_amount'], $batchKeys);
        $this->assertNotNull($batchResponse->json('data.payments.0.id'));
        $batchResponse->assertJsonMissingPath('data.payments.0.gateway_payload');
        $batchResponse->assertJsonMissingPath('data.payments.0.proof_path');
    }

    /** @param list<string> $permissions */
    private function scopedActor(string $organizationId, array $permissions): User
    {
        $role = Role::create([
            'name' => 'Scoped Test Role '.fake()->unique()->numerify('#####'),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($permissions);
        $actor = User::factory()->create(['organization_id' => $organizationId]);
        $actor->assignRole($role);

        return $actor;
    }

    private function invoiceFor(string $organizationId, int|string $typeId): CooperativeDuesInvoice
    {
        $member = CooperativeMember::factory()->active()->create(['organization_id' => $organizationId]);

        return CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $typeId,
            'period' => '2026-07',
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
    }

    private function invoiceForMember(CooperativeMember $member, int|string $typeId, string $period): CooperativeDuesInvoice
    {
        return CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $typeId,
            'period' => $period,
            'amount' => 50000,
            'paid_amount' => 0,
            'status' => 'UNPAID',
        ]);
    }
}
