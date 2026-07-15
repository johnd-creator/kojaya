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
        $actor = $this->scopedActor($organization->id, ['view_cooperative_loan']);
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
}
