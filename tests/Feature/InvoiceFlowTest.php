<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use DatabaseMigrations;

    protected Organization $orgA;

    protected Organization $orgB;

    protected Client $clientA;

    protected Client $clientB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['code' => 'UNIT-A', 'level' => 'L2', 'type' => 'BRANCH']);
        $this->orgB = Organization::factory()->create(['code' => 'UNIT-B', 'level' => 'L2', 'type' => 'BRANCH']);

        $this->clientA = Client::factory()->create(['organization_id' => $this->orgA->id]);
        $this->clientB = Client::factory()->create(['organization_id' => $this->orgB->id]);

        // Invoices in each org
        Invoice::factory()->count(3)->create([
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'client_id' => $this->clientA->id,
        ]);

        Invoice::factory()->count(2)->create([
            'organization_id' => $this->orgB->id,
            'unit_id' => $this->orgB->id,
            'client_id' => $this->clientB->id,
        ]);
    }

    #[Test]
    public function finance_pusat_can_see_all_invoices(): void
    {
        Role::firstOrCreate(['name' => 'Finance Pusat', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Pusat');

        $this->actingAs($user);

        $invoices = Invoice::query()->forUser()->get();

        $this->assertCount(5, $invoices);
    }

    #[Test]
    public function admin_pusat_can_see_all_invoices(): void
    {
        Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Admin Pusat');

        $this->actingAs($user);

        $invoices = Invoice::query()->forUser()->get();

        $this->assertCount(5, $invoices);
    }

    #[Test]
    public function finance_unit_only_sees_own_organization_invoices(): void
    {
        Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Unit');

        $this->actingAs($user);

        $invoices = Invoice::query()->forUser()->get();

        $this->assertCount(3, $invoices);
        $invoices->each(fn ($invoice) => $this->assertEquals($this->orgA->id, $invoice->organization_id));
    }

    #[Test]
    public function admin_unit_only_sees_own_organization_invoices(): void
    {
        Role::firstOrCreate(['name' => 'Admin Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgB->id]);
        $user->assignRole('Admin Unit');

        $this->actingAs($user);

        $invoices = Invoice::query()->forUser()->get();

        $this->assertCount(2, $invoices);
        $invoices->each(fn ($invoice) => $this->assertEquals($this->orgB->id, $invoice->organization_id));
    }

    #[Test]
    public function invoice_calculates_tax_correctly(): void
    {
        $invoice = new Invoice([
            'amount' => 1000000,
            'tax_amount' => 0,
            'total_amount' => 0,
        ]);

        $expectedTax = $invoice->calculateTax(0.11);
        $invoice->tax_amount = $expectedTax;
        $expectedTotal = $invoice->calculateTotal();

        $this->assertEquals(110000, $expectedTax);
        $this->assertEquals(1110000, $expectedTotal);
    }

    #[Test]
    public function invoice_status_can_be_updated_from_draft_to_pending(): void
    {
        Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Unit');

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'status' => 'DRAFT',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('invoices.submit-for-approval', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'PENDING',
        ]);
    }

    #[Test]
    public function finance_pusat_can_approve_pending_invoice(): void
    {
        Role::firstOrCreate(['name' => 'Finance Pusat', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Pusat');

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('invoices.approve', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'APPROVED',
        ]);
    }

    #[Test]
    public function finance_unit_cannot_approve_invoice(): void
    {
        Role::firstOrCreate(['name' => 'Finance Unit', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Unit');

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'status' => 'PENDING',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('invoices.approve', $invoice));

        $response->assertStatus(403);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'PENDING',
        ]);
    }

    #[Test]
    public function finance_pusat_can_mark_approved_invoice_as_paid(): void
    {
        Role::firstOrCreate(['name' => 'Finance Pusat', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Pusat');

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'status' => 'APPROVED',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('invoices.mark-as-paid', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'PAID',
        ]);
    }

    #[Test]
    public function marking_paid_invoice_twice_is_a_no_op(): void
    {
        Role::firstOrCreate(['name' => 'Finance Pusat', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Finance Pusat');

        $invoice = Invoice::factory()->create([
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'status' => 'APPROVED',
        ]);

        $this->actingAs($user);

        $this->post(route('invoices.mark-as-paid', $invoice))->assertRedirect();
        $this->post(route('invoices.mark-as-paid', $invoice->fresh()))->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'PAID',
        ]);
    }

    #[Test]
    public function for_organization_scope_filters_by_given_org(): void
    {
        Role::firstOrCreate(['name' => 'Admin Pusat', 'guard_name' => 'web']);

        $user = User::factory()->create(['organization_id' => $this->orgA->id]);
        $user->assignRole('Admin Pusat');

        $this->actingAs($user);

        $invoices = Invoice::query()->forOrganization($this->orgB->id)->get();

        $this->assertCount(2, $invoices);
        $invoices->each(fn ($invoice) => $this->assertEquals($this->orgB->id, $invoice->organization_id));
    }

    #[Test]
    public function guest_sees_no_invoices(): void
    {
        $invoices = Invoice::query()->forUser()->get();

        $this->assertCount(0, $invoices);
    }
}
