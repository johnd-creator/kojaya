<?php

namespace Tests\Feature\Security;

use App\Exceptions\OrganizationScopeException;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\MemberPaymentIntent;
use App\Models\Organization;
use App\Models\PosMemberCreditPayment;
use App\Models\User;
use App\Services\Cooperative\MemberCreditService;
use App\Services\Integrations\MemberPaymentSettlementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionMethod;
use Tests\TestCase;

class PosMemberCreditOrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private MemberCreditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->service = app(MemberCreditService::class);
    }

    // ==========================================
    // GROUP 1: HTTP TEST MATRIX (1 - 14)
    // ==========================================

    public function test_1_same_org_cashier_can_open_member_credit_page(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $response = $this->actingAs($cashierA)->get(route('cooperative.pos.credit.create', $memberA->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cooperative/Pos/Credit/Pay')
            ->where('member.id', $memberA->id)
            ->where('outstanding_balance', 50000)
        );
    }

    public function test_2_same_org_cashier_can_record_payment(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 100000);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.credit.store', $memberA->id), [
            'amount' => 25000,
            'reference_no' => 'REF-SAME-ORG-01',
            'notes' => 'Pembayaran kredit normal',
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(75000.0, (float) $memberA->fresh()->outstanding_balance);

        $payment = PosMemberCreditPayment::query()->where('cooperative_member_id', $memberA->id)->firstOrFail();
        $this->assertSame('25000.00', $payment->amount);
        $this->assertSame($cashierA->id, $payment->received_by);
        $this->assertSame('REF-SAME-ORG-01', $payment->reference_no);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();
        $this->assertSame($orgA->id, $ledger->organization_id);
        $this->assertSame($memberA->id, $ledger->cooperative_member_id);
        $this->assertSame('POS_MEMBER_CREDIT_PAYMENT', $ledger->entry_type);
        $this->assertSame('POS', $ledger->ledger_scope);
        $this->assertSame(0.0, (float) $ledger->debit);
        $this->assertSame(25000.0, (float) $ledger->credit);
    }

    public function test_3_org_a_cashier_cannot_open_org_b_member_credit_page(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 50000);

        $response = $this->actingAs($cashierA)->get(route('cooperative.pos.credit.create', $memberB->id));

        $response->assertForbidden();
    }

    public function test_4_org_a_cashier_cannot_post_payment_to_org_b_member(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 50000);

        $response = $this->actingAs($cashierA)->post(route('cooperative.pos.credit.store', $memberB->id), [
            'amount' => 10000,
            'reference_no' => 'REF-ATTACK-01',
        ]);

        $response->assertForbidden();
    }

    public function test_5_foreign_denial_does_not_change_outstanding_balance(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 80000);

        $this->actingAs($cashierA)->post(route('cooperative.pos.credit.store', $memberB->id), [
            'amount' => 20000,
        ])->assertForbidden();

        $this->assertSame(80000.0, (float) $memberB->fresh()->outstanding_balance);
    }

    public function test_6_foreign_denial_creates_no_pos_member_credit_payment(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 50000);

        $this->actingAs($cashierA)->post(route('cooperative.pos.credit.store', $memberB->id), [
            'amount' => 10000,
        ])->assertForbidden();

        $this->assertSame(0, PosMemberCreditPayment::count());
    }

    public function test_7_foreign_denial_creates_no_cooperative_ledger_entry(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 50000);

        $this->actingAs($cashierA)->post(route('cooperative.pos.credit.store', $memberB->id), [
            'amount' => 10000,
        ])->assertForbidden();

        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    public function test_8_null_org_ordinary_actor_cannot_open_or_pay(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $nullOrgActor = User::factory()->create(['organization_id' => null]);
        $nullOrgActor->givePermissionTo('access_cooperative_pos');

        $this->actingAs($nullOrgActor)
            ->get(route('cooperative.pos.credit.create', $memberA->id))
            ->assertForbidden();

        $this->actingAs($nullOrgActor)
            ->post(route('cooperative.pos.credit.store', $memberA->id), [
                'amount' => 10000,
            ])
            ->assertForbidden();

        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(0, PosMemberCreditPayment::count());
        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    public function test_9_existing_null_org_member_cannot_be_opened_or_paid_by_unit_actor(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);

        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $nullOrgMember = $this->createMember($orgA, outstandingBalance: 50000);
        $nullOrgMember->forceFill(['organization_id' => null])->saveQuietly();

        $this->assertDatabaseHas('cooperative_members', [
            'id' => $nullOrgMember->id,
            'organization_id' => null,
        ]);

        $this->actingAs($cashierA)
            ->get(route('cooperative.pos.credit.create', $nullOrgMember->id))
            ->assertForbidden();

        $this->actingAs($cashierA)
            ->post(route('cooperative.pos.credit.store', $nullOrgMember->id), [
                'amount' => 10000,
            ])
            ->assertForbidden();

        $this->assertSame(50000.0, (float) $nullOrgMember->fresh()->outstanding_balance);
        $this->assertSame(0, PosMemberCreditPayment::count());
        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    public function test_10_view_cooperative_all_without_access_cooperative_pos_cannot_operate(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $globalViewer = User::factory()->create(['organization_id' => $orgA->id]);
        $globalViewer->givePermissionTo('view_cooperative_all');

        $this->actingAs($globalViewer)
            ->get(route('cooperative.pos.credit.create', $memberA->id))
            ->assertForbidden();

        $this->actingAs($globalViewer)
            ->post(route('cooperative.pos.credit.store', $memberA->id), [
                'amount' => 10000,
            ])
            ->assertForbidden();

        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(0, PosMemberCreditPayment::count());
        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    public function test_11_global_actor_with_access_cooperative_pos_and_view_cooperative_all_may_pay_foreign_member(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalActor = $this->createGlobalOperator($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 60000);

        $response = $this->actingAs($globalActor)->post(route('cooperative.pos.credit.store', $memberB->id), [
            'amount' => 20000,
            'reference_no' => 'REF-GLOBAL-01',
        ]);

        $response->assertRedirect();
        $this->assertSame(40000.0, (float) $memberB->fresh()->outstanding_balance);
    }

    public function test_12_global_actor_payment_stamps_ledger_to_target_member_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalActor = $this->createGlobalOperator($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 100000);

        $this->actingAs($globalActor)->post(route('cooperative.pos.credit.store', $memberB->id), [
            'amount' => 30000,
            'reference_no' => 'REF-STAMP-TARGET',
        ])->assertRedirect();

        $payment = PosMemberCreditPayment::query()->where('cooperative_member_id', $memberB->id)->firstOrFail();
        $this->assertSame($globalActor->id, $payment->received_by);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        // Must be stamped to Member B organization, NOT actor's home Organization A!
        $this->assertSame($orgB->id, $ledger->organization_id);
        $this->assertNotSame($orgA->id, $ledger->organization_id);
        $this->assertSame($memberB->id, $ledger->cooperative_member_id);
    }

    public function test_13_foreign_member_payment_history_is_not_exposed_on_get(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $cashierB = $this->createCashier($orgB);
        $memberB = $this->createMember($orgB, outstandingBalance: 70000);

        PosMemberCreditPayment::query()->create([
            'cooperative_member_id' => $memberB->id,
            'received_by' => $cashierB->id,
            'reference_no' => 'SECRET-B-HIST',
            'amount' => '30000.00',
            'paid_at' => now()->toDateString(),
            'notes' => 'Secret note of Member B',
        ]);

        $response = $this->actingAs($cashierA)->get(route('cooperative.pos.credit.create', $memberB->id));

        $response->assertForbidden();
    }

    public function test_14_same_org_payment_history_remains_visible(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 70000);

        PosMemberCreditPayment::query()->create([
            'cooperative_member_id' => $memberA->id,
            'received_by' => $cashierA->id,
            'reference_no' => 'HIST-A-01',
            'amount' => '30000.00',
            'paid_at' => now()->toDateString(),
            'notes' => 'History payment 1',
        ]);

        $response = $this->actingAs($cashierA)->get(route('cooperative.pos.credit.create', $memberA->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cooperative/Pos/Credit/Pay')
            ->where('member.id', $memberA->id)
            ->has('member.credit_payments', 1)
            ->where('member.credit_payments.0.reference_no', 'HIST-A-01')
        );
    }

    // ==========================================
    // GROUP 2: DIRECT SERVICE TEST MATRIX (15 - 24)
    // ==========================================

    public function test_15_same_org_direct_record_payment_succeeds(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 40000);

        $payment = $this->service->recordPayment(
            member: $memberA,
            amount: 15000,
            receiver: $cashierA,
            referenceNo: 'DIR-01',
        );

        $this->assertInstanceOf(PosMemberCreditPayment::class, $payment);
        $this->assertSame('15000.00', $payment->amount);
        $this->assertSame(25000.0, (float) $memberA->fresh()->outstanding_balance);
    }

    public function test_16_foreign_member_direct_service_call_is_denied(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 40000);

        $this->expectException(AuthorizationException::class);

        $this->service->recordPayment(
            member: $memberB,
            amount: 10000,
            receiver: $cashierA,
        );
    }

    public function test_17_null_org_member_direct_service_call_is_denied(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);

        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $nullMember = $this->createMember($orgA, outstandingBalance: 40000);
        $nullMember->forceFill(['organization_id' => null])->saveQuietly();

        $this->expectException(OrganizationScopeException::class);

        $this->service->recordPayment(
            member: $nullMember,
            amount: 10000,
            receiver: $cashierA,
        );
    }

    public function test_18_null_actor_is_impossible_by_signature_and_fails_closed(): void
    {
        $refMethod = new ReflectionMethod(MemberCreditService::class, 'recordPayment');
        $params = $refMethod->getParameters();

        $receiverParam = null;
        foreach ($params as $param) {
            if ($param->getName() === 'receiver') {
                $receiverParam = $param;
                break;
            }
        }

        $this->assertNotNull($receiverParam, 'Param receiver must exist');
        $this->assertSame(User::class, $receiverParam->getType()?->getName());
        $this->assertFalse($receiverParam->allowsNull(), 'Param receiver must NOT allow null');
    }

    public function test_19_global_actor_can_directly_operate_foreign_member_through_canonical_global_visibility(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalActor = $this->createGlobalOperator($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 50000);

        $payment = $this->service->recordPayment(
            member: $memberB,
            amount: 20000,
            receiver: $globalActor,
            referenceNo: 'DIR-GLOBAL-01',
        );

        $this->assertInstanceOf(PosMemberCreditPayment::class, $payment);
        $this->assertSame(30000.0, (float) $memberB->fresh()->outstanding_balance);
    }

    public function test_20_global_actor_target_ledger_uses_member_organization(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $globalActor = $this->createGlobalOperator($orgA);
        $memberB = $this->createMember($orgB, outstandingBalance: 50000);

        $payment = $this->service->recordPayment(
            member: $memberB,
            amount: 20000,
            receiver: $globalActor,
        );

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame($orgB->id, $ledger->organization_id);
    }

    public function test_21_service_refetches_and_locks_authoritative_member(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        CooperativeMember::query()->whereKey($memberA->id)->update([
            'outstanding_balance' => '30000.00',
        ]);

        $payment = $this->service->recordPayment(
            member: $memberA,
            amount: 10000,
            receiver: $cashierA,
        );

        $this->assertSame('20000.00', $memberA->fresh()->outstanding_balance);
    }

    public function test_22_stale_member_object_cannot_bypass_current_organization_ownership(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $member = $this->createMember($orgA, outstandingBalance: 50000);

        CooperativeMember::query()->whereKey($member->id)->update([
            'organization_id' => $orgB->id,
        ]);

        $this->expectException(AuthorizationException::class);

        $this->service->recordPayment(
            member: $member,
            amount: 10000,
            receiver: $cashierA,
        );
    }

    public function test_23_stale_outstanding_balance_object_cannot_bypass_current_locked_balance(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $member = $this->createMember($orgA, outstandingBalance: 100000);

        CooperativeMember::query()->whereKey($member->id)->update([
            'outstanding_balance' => '20000.00',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->recordPayment(
            member: $member,
            amount: 50000,
            receiver: $cashierA,
        );
    }

    public function test_24_existing_member_whose_organization_changed_to_foreign_before_authoritative_mutation_is_denied_with_zero_side_effects(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $member = $this->createMember($orgA, outstandingBalance: 50000);

        CooperativeMember::query()->whereKey($member->id)->update([
            'organization_id' => $orgB->id,
        ]);

        try {
            $this->service->recordPayment(
                member: $member,
                amount: 10000,
                receiver: $cashierA,
            );
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException) {
            // Expected
        }

        $this->assertSame(50000.0, (float) $member->fresh()->outstanding_balance);
        $this->assertSame(0, PosMemberCreditPayment::count());
        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    // ==========================================
    // GROUP 3: BALANCE / FINANCIAL TEST MATRIX (25 - 37)
    // ==========================================

    public function test_25_partial_payment_reduces_outstanding_exactly(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 75000);

        $this->service->recordPayment($memberA, 25000, $cashierA);

        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
    }

    public function test_26_full_payment_produces_exactly_zero_outstanding(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $this->service->recordPayment($memberA, 50000, $cashierA);

        $this->assertSame(0.0, (float) $memberA->fresh()->outstanding_balance);
    }

    public function test_27_payment_greater_than_current_locked_outstanding_is_rejected(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 30000);

        $this->expectException(ValidationException::class);

        $this->service->recordPayment($memberA, 30001, $cashierA);
    }

    public function test_28_zero_amount_rejected(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 30000);

        $this->expectException(ValidationException::class);

        $this->service->recordPayment($memberA, 0, $cashierA);
    }

    public function test_29_negative_amount_rejected(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 30000);

        $this->expectException(ValidationException::class);

        $this->service->recordPayment($memberA, -5000, $cashierA);
    }

    public function test_30_failed_payment_does_not_alter_balance(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 30000);

        try {
            $this->service->recordPayment($memberA, 40000, $cashierA);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            // Expected
        }

        $this->assertSame(30000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(0, PosMemberCreditPayment::count());
        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    public function test_31_successful_payment_creates_exactly_one_payment_row(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $this->assertSame(0, PosMemberCreditPayment::count());
        $this->service->recordPayment($memberA, 20000, $cashierA);
        $this->assertSame(1, PosMemberCreditPayment::count());
    }

    public function test_32_successful_payment_creates_exactly_one_matching_ledger_row(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $this->assertSame(0, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
        $this->service->recordPayment($memberA, 20000, $cashierA);
        $this->assertSame(1, CooperativeLedgerEntry::where('entry_type', 'POS_MEMBER_CREDIT_PAYMENT')->count());
    }

    public function test_33_ledger_organization_id_equals_member_organization_id(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $payment = $this->service->recordPayment($memberA, 20000, $cashierA);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame($memberA->organization_id, $ledger->organization_id);
    }

    public function test_34_ledger_source_id_equals_payment_id(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $payment = $this->service->recordPayment($memberA, 20000, $cashierA);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame($payment->id, $ledger->source_id);
    }

    public function test_35_ledger_source_type_equals_pos_member_credit_payment_class(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $payment = $this->service->recordPayment($memberA, 20000, $cashierA);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame(PosMemberCreditPayment::class, $ledger->source_type);
    }

    public function test_36_ledger_amount_semantics_match_payment_amount(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $payment = $this->service->recordPayment($memberA, 20000, $cashierA);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame(0.0, (float) $ledger->debit);
        $this->assertSame(20000.0, (float) $ledger->credit);
        $this->assertSame('20000.00', $payment->amount);
    }

    public function test_37_repeated_sequential_payments_cannot_make_outstanding_negative(): void
    {
        [$orgA] = $this->createOrganizations();
        $cashierA = $this->createCashier($orgA);
        $memberA = $this->createMember($orgA, outstandingBalance: 10000);

        // Payment 1: 6,000 -> remaining 4,000
        $this->service->recordPayment($memberA, 6000, $cashierA);
        $this->assertSame(4000.0, (float) $memberA->fresh()->outstanding_balance);

        // Payment 2: 6,000 -> exceeds 4,000 -> must fail!
        try {
            $this->service->recordPayment($memberA, 6000, $cashierA);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException) {
            // Expected
        }

        $this->assertSame(4000.0, (float) $memberA->fresh()->outstanding_balance);
    }

    // ==========================================
    // SETTLEMENT WORKFLOW TESTS (38 - 51)
    // ==========================================

    public function test_38_settlement_payment_records_with_gateway_reference_and_ledger_stamping(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 80000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 30000,
            'gateway_status' => 'PAID',
            'gateway_reference' => 'GW-INTENT-POS-01',
        ]);

        $payment = $this->service->recordSettlementPayment(
            intent: $intent,
            notes: 'Settlement test',
        );

        $this->assertSame($memberA->id, $payment->cooperative_member_id);
        $this->assertSame('30000.00', (string) $payment->amount);
        $this->assertSame('GW-INTENT-POS-01', $payment->reference_no);
        $this->assertNull($payment->received_by);

        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);

        $ledger = CooperativeLedgerEntry::query()
            ->where('source_type', PosMemberCreditPayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame($orgA->id, $ledger->organization_id);
        $this->assertSame($memberA->id, $ledger->cooperative_member_id);
        $this->assertSame(30000.0, (float) $ledger->credit);

        $freshIntent = $intent->fresh();
        $this->assertSame('SETTLED', $freshIntent->settlement_status);
        $this->assertNotNull($freshIntent->settled_at);
        $this->assertSame('pos_member_credit_payment:'.$payment->id, $freshIntent->settled_by_service);
    }

    public function test_39_settlement_payment_fails_closed_on_null_org_member(): void
    {
        [$orgA] = $this->createOrganizations();

        Schema::table('cooperative_members', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->change();
        });

        $nullOrgMember = $this->createMember($orgA, outstandingBalance: 50000);
        $nullOrgMember->forceFill(['organization_id' => null])->saveQuietly();

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $nullOrgMember->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $nullOrgMember->id,
            'amount' => 10000,
            'gateway_status' => 'PAID',
        ]);

        $this->expectException(OrganizationScopeException::class);

        $this->service->recordSettlementPayment(intent: $intent);
    }

    public function test_40_unpaid_or_pending_intent_cannot_settle_member_credit(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 20000,
            'gateway_status' => 'PENDING',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(0, PosMemberCreditPayment::count());
            $this->assertSame(0, CooperativeLedgerEntry::count());
        }
    }

    public function test_41_wrong_payable_type_cannot_settle_member_credit(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_LOAN_INSTALLMENT,
            'payable_id' => $memberA->id,
            'amount' => 20000,
            'gateway_status' => 'PAID',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(0, PosMemberCreditPayment::count());
            $this->assertSame(0, CooperativeLedgerEntry::count());
        }
    }

    public function test_42_intent_for_member_a_cannot_settle_member_b(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);
        $memberB = $this->createMember($orgB, outstandingBalance: 40000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 20000,
            'gateway_status' => 'PAID',
            'gateway_reference' => 'GW-MEMBER-A',
        ]);

        $payment = $this->service->recordSettlementPayment(intent: $intent);

        $this->assertSame($memberA->id, $payment->cooperative_member_id);
        $this->assertSame(30000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(40000.0, (float) $memberB->fresh()->outstanding_balance);
    }

    public function test_43_payable_id_mismatch_is_denied(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => 999999,
            'amount' => 20000,
            'gateway_status' => 'PAID',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(0, PosMemberCreditPayment::count());
            $this->assertSame(0, CooperativeLedgerEntry::count());
        }
    }

    public function test_44_cooperative_member_id_mismatch_or_nonexistent_is_denied(): void
    {
        [$orgA, $orgB] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);
        $memberB = $this->createMember($orgB, outstandingBalance: 40000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberB->id,
            'amount' => 20000,
            'gateway_status' => 'PAID',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(40000.0, (float) $memberB->fresh()->outstanding_balance);
            $this->assertSame(0, PosMemberCreditPayment::count());
            $this->assertSame(0, CooperativeLedgerEntry::count());
        }
    }

    public function test_45_settlement_api_derives_amount_exclusively_from_intent(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 60000);

        $reflection = new ReflectionMethod($this->service, 'recordSettlementPayment');
        $paramNames = array_map(fn ($p) => $p->getName(), $reflection->getParameters());
        $this->assertNotContains('amount', $paramNames);
        $this->assertNotContains('member', $paramNames);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 15000,
            'gateway_status' => 'PAID',
        ]);

        $payment = $this->service->recordSettlementPayment(intent: $intent);

        $this->assertSame('15000.00', (string) $payment->amount);
        $this->assertSame(45000.0, (float) $memberA->fresh()->outstanding_balance);
    }

    public function test_46_settlement_api_derives_gateway_reference_exclusively_from_intent(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 60000);

        $reflection = new ReflectionMethod($this->service, 'recordSettlementPayment');
        $paramNames = array_map(fn ($p) => $p->getName(), $reflection->getParameters());
        $this->assertNotContains('referenceNo', $paramNames);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 15000,
            'gateway_status' => 'PAID',
            'gateway_reference' => 'AUTH-REF-EXCLUSIVE-999',
        ]);

        $payment = $this->service->recordSettlementPayment(intent: $intent);

        $this->assertSame('AUTH-REF-EXCLUSIVE-999', $payment->reference_no);
    }

    public function test_47_already_settled_intent_cannot_create_another_payment(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 20000,
            'gateway_status' => 'PAID',
            'settlement_status' => 'SETTLED',
            'settled_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(0, PosMemberCreditPayment::count());
            $this->assertSame(0, CooperativeLedgerEntry::count());
        }
    }

    public function test_48_reusing_same_intent_cannot_create_duplicate_payment_or_ledger_effects(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 20000,
            'gateway_status' => 'PAID',
        ]);

        $this->service->recordSettlementPayment(intent: $intent);
        $this->assertSame(30000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(1, PosMemberCreditPayment::count());
        $this->assertSame(1, CooperativeLedgerEntry::count());

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(30000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(1, PosMemberCreditPayment::count());
            $this->assertSame(1, CooperativeLedgerEntry::count());
        }
    }

    public function test_49_failed_settlement_leaves_outstanding_balance_unchanged_with_zero_records(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 50000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 20000,
            'gateway_status' => 'DENIED',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->recordSettlementPayment(intent: $intent);
        } finally {
            $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
            $this->assertSame(0, PosMemberCreditPayment::count());
            $this->assertSame(0, CooperativeLedgerEntry::count());
        }
    }

    public function test_50_production_settlement_service_coordinator_settles_pos_credit(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 75000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 25000,
            'gateway_status' => 'PAID',
            'gateway_reference' => 'GW-PROD-COORD-01',
        ]);

        $settlementService = app(MemberPaymentSettlementService::class);
        $settledIntent = $settlementService->settle($intent);

        $this->assertSame('SETTLED', $settledIntent->settlement_status);
        $this->assertNotNull($settledIntent->settled_at);
        $this->assertStringStartsWith('pos_member_credit_payment:', $settledIntent->settled_by_service);

        $payment = PosMemberCreditPayment::query()->firstOrFail();
        $this->assertSame($memberA->id, $payment->cooperative_member_id);
        $this->assertSame('25000.00', (string) $payment->amount);
        $this->assertSame('GW-PROD-COORD-01', $payment->reference_no);

        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);

        $ledger = CooperativeLedgerEntry::query()->where('source_id', $payment->id)->firstOrFail();
        $this->assertSame($orgA->id, $ledger->organization_id);
        $this->assertSame($memberA->id, $ledger->cooperative_member_id);
        $this->assertSame(25000.0, (float) $ledger->credit);
    }

    public function test_51_production_settlement_service_coordinator_idempotency_on_repeated_settle(): void
    {
        [$orgA] = $this->createOrganizations();
        $memberA = $this->createMember($orgA, outstandingBalance: 75000);

        $intent = MemberPaymentIntent::factory()->create([
            'cooperative_member_id' => $memberA->id,
            'payable_type' => MemberPaymentIntent::PAYABLE_POS_CREDIT,
            'payable_id' => $memberA->id,
            'amount' => 25000,
            'gateway_status' => 'PAID',
            'gateway_reference' => 'GW-PROD-IDEMP-01',
        ]);

        $settlementService = app(MemberPaymentSettlementService::class);

        // First settle call
        $firstResult = $settlementService->settle($intent);
        $this->assertSame('SETTLED', $firstResult->settlement_status);
        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(1, PosMemberCreditPayment::count());
        $this->assertSame(1, CooperativeLedgerEntry::count());

        // Second settle call (idempotent replay)
        $secondResult = $settlementService->settle($firstResult);
        $this->assertSame('SETTLED', $secondResult->settlement_status);

        // Verifikasi tidak ada double charge atau duplikasi record
        $this->assertSame(50000.0, (float) $memberA->fresh()->outstanding_balance);
        $this->assertSame(1, PosMemberCreditPayment::count());
        $this->assertSame(1, CooperativeLedgerEntry::count());
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * @return array{0: Organization, 1: Organization}
     */
    private function createOrganizations(): array
    {
        return [
            Organization::factory()->create(['name' => 'Koperasi Unit A']),
            Organization::factory()->create(['name' => 'Koperasi Unit B']),
        ];
    }

    private function createCashier(Organization $org, array $perms = ['access_cooperative_pos'], string $name = 'Kasir Test'): User
    {
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name' => $name,
        ]);
        $user->givePermissionTo($perms);

        return $user;
    }

    private function createGlobalOperator(Organization $homeOrg): User
    {
        $user = User::factory()->create([
            'organization_id' => $homeOrg->id,
            'name' => 'Global POS Operator',
        ]);
        $user->givePermissionTo(['access_cooperative_pos', 'view_cooperative_all']);

        return $user;
    }

    private function createMember(Organization $org, float $outstandingBalance = 0, string $name = 'Member Test'): CooperativeMember
    {
        return CooperativeMember::factory()->create([
            'organization_id' => $org->id,
            'name' => $name,
            'status' => 'ACTIVE',
            'validation_status' => CooperativeMember::VALIDATION_ACTIVE,
            'credit_limit' => 500000,
            'outstanding_balance' => $outstandingBalance,
        ]);
    }
}
