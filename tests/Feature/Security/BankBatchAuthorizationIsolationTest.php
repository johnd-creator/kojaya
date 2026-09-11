<?php

namespace Tests\Feature\Security;

use App\Models\BankTransferBatch;
use App\Models\BankTransferItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\BankStatementReconciler;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BankBatchAuthorizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $orgA;

    protected Organization $orgB;

    protected User $financeUnitA;

    protected User $financeUnitB;

    protected User $financePusat;

    protected User $noBankPermissionA;

    protected User $nullOrgFinance;

    protected User $nullOrgGlobalFinance;

    protected Client $clientA;

    protected Client $clientB;

    protected Invoice $invoiceA;

    protected Invoice $invoiceB;

    protected BankTransferBatch $batchA;

    protected BankTransferBatch $batchB;

    protected BankTransferItem $itemA;

    protected BankTransferItem $itemB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        // Organizations
        $this->orgA = Organization::factory()->create(['name' => 'Koperasi Unit Alpha']);
        $this->orgB = Organization::factory()->create(['name' => 'Koperasi Unit Beta']);

        // Users
        $this->financeUnitA = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->financeUnitA->assignRole('Finance Unit');

        $this->financeUnitB = User::factory()->create(['organization_id' => $this->orgB->id]);
        $this->financeUnitB->assignRole('Finance Unit');

        $this->financePusat = User::factory()->create(['organization_id' => $this->orgA->id]);
        $this->financePusat->assignRole('Finance Pusat');

        $this->noBankPermissionA = User::factory()->create(['organization_id' => $this->orgA->id]);

        $this->nullOrgFinance = User::factory()->create(['organization_id' => null]);
        $this->nullOrgFinance->givePermissionTo(['manage_bank_batch', 'manage_bank_reconciliation']);

        $this->nullOrgGlobalFinance = User::factory()->create(['organization_id' => null]);
        $this->nullOrgGlobalFinance->givePermissionTo([
            'manage_bank_batch',
            'view_bank_batch_all',
            'manage_bank_reconciliation',
            'view_invoice_all',
        ]);

        // Clients
        $this->clientA = Client::factory()->create([
            'name' => 'Client Alpha Ltd',
            'organization_id' => $this->orgA->id,
        ]);
        $this->clientB = Client::factory()->create([
            'name' => 'Client Beta Confidential Corp',
            'organization_id' => $this->orgB->id,
        ]);

        // Invoices
        $this->invoiceA = Invoice::create([
            'invoice_no' => 'INV-ORG-A-001',
            'client_id' => $this->clientA->id,
            'organization_id' => $this->orgA->id,
            'unit_id' => $this->orgA->id,
            'amount' => 1351351.35,
            'tax_amount' => 148648.65,
            'total_amount' => 1500000.00,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->invoiceB = Invoice::create([
            'invoice_no' => 'INV-ORG-B-002',
            'client_id' => $this->clientB->id,
            'organization_id' => $this->orgB->id,
            'unit_id' => $this->orgB->id,
            'amount' => 2477477.48,
            'tax_amount' => 272522.52,
            'total_amount' => 2750000.00,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        // Batches
        $this->batchA = BankTransferBatch::create([
            'organization_id' => $this->orgA->id,
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'BATCH-A-REF',
            'status' => 'DRAFT',
        ]);

        $this->batchB = BankTransferBatch::create([
            'organization_id' => $this->orgB->id,
            'bank_name' => 'MANDIRI',
            'account_number' => '999988887777',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'BATCH-B-REF',
            'status' => 'DRAFT',
        ]);

        // Items
        $this->itemA = BankTransferItem::create([
            'batch_id' => $this->batchA->id,
            'beneficiary_name' => 'Beneficiary Alice',
            'beneficiary_account' => '555566667777',
            'amount' => 1500000.00,
            'currency' => 'IDR',
            'reference' => 'INV-'.$this->invoiceA->id,
            'invoice_id' => $this->invoiceA->id,
            'status' => 'PENDING',
        ]);

        $this->itemB = BankTransferItem::create([
            'batch_id' => $this->batchB->id,
            'beneficiary_name' => 'Beneficiary Bob Secret',
            'beneficiary_account' => '444433332222',
            'amount' => 2750000.00,
            'currency' => 'IDR',
            'reference' => 'INV-'.$this->invoiceB->id,
            'invoice_id' => $this->invoiceB->id,
            'status' => 'PENDING',
        ]);
    }

    // 01 no-permission batch index blocked
    public function test_01_no_permission_batch_index_blocked(): void
    {
        $this->actingAs($this->noBankPermissionA)
            ->get(route('finance.bank-batches.index'))
            ->assertStatus(403);
    }

    // 02 Finance Unit sees own batch
    public function test_02_finance_unit_sees_own_batch(): void
    {
        $response = $this->actingAs($this->financeUnitA)->get(route('finance.bank-batches.index'));
        $response->assertOk();

        $batches = $response->viewData('page')['props']['batches'];
        $batchIds = collect($batches)->pluck('id')->all();

        $this->assertContains($this->batchA->id, $batchIds);
    }

    // 03 Finance Unit does not see foreign batch
    public function test_03_finance_unit_does_not_see_foreign_batch(): void
    {
        $response = $this->actingAs($this->financeUnitA)->get(route('finance.bank-batches.index'));
        $response->assertOk();

        $batches = $response->viewData('page')['props']['batches'];
        $batchIds = collect($batches)->pluck('id')->all();

        $this->assertNotContains($this->batchB->id, $batchIds);
    }

    // 04 Finance Pusat sees global batches
    public function test_04_finance_pusat_sees_global_batches(): void
    {
        $response = $this->actingAs($this->financePusat)->get(route('finance.bank-batches.index'));
        $response->assertOk();

        $batches = $response->viewData('page')['props']['batches'];
        $batchIds = collect($batches)->pluck('id')->all();

        $this->assertContains($this->batchA->id, $batchIds);
        $this->assertContains($this->batchB->id, $batchIds);
    }

    // 05 NULL-org non-global index denied
    public function test_05_null_org_non_global_index_denied(): void
    {
        $this->actingAs($this->nullOrgFinance)
            ->get(route('finance.bank-batches.index'))
            ->assertStatus(403);
    }

    // 06 Finance Unit exports own batch
    public function test_06_finance_unit_exports_own_batch(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchA->id]));

        $response->assertStatus(200);
        $this->assertStringContainsString('111122223333', $response->getContent());
        $this->assertStringContainsString('Beneficiary Alice', $response->getContent());
    }

    // 07 Finance Unit foreign export blocked
    public function test_07_finance_unit_foreign_export_blocked(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchB->id]));

        $response->assertStatus(404);
    }

    // 08 foreign existing vs nonexistent batch oracle collapsed
    public function test_08_foreign_existing_vs_nonexistent_batch_oracle_collapsed(): void
    {
        $respForeign = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchB->id]));

        $respNonexistent = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => '00000000-0000-0000-0000-000000000000']));

        $this->assertEquals(404, $respForeign->getStatusCode());
        $this->assertEquals(404, $respNonexistent->getStatusCode());
    }

    // 09 Finance Pusat global export allowed
    public function test_09_finance_pusat_global_export_allowed(): void
    {
        $response = $this->actingAs($this->financePusat)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchB->id]));

        $response->assertStatus(200);
        $this->assertStringContainsString('999988887777', $response->getContent());
        $this->assertStringContainsString('Beneficiary Bob Secret', $response->getContent());
    }

    // 10 foreign bank values absent on denial
    public function test_10_foreign_bank_values_absent_on_denial(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchB->id]));

        $content = $response->getContent();
        $this->assertStringNotContainsString('999988887777', $content);
        $this->assertStringNotContainsString('Beneficiary Bob Secret', $content);
        $this->assertStringNotContainsString('444433332222', $content);
        $this->assertStringNotContainsString('2750000.00', $content);
    }

    // 11 export response has no-store/private
    public function test_11_export_response_has_no_store_private(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchA->id]));

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
    }

    // 12 export response has nosniff
    public function test_12_export_response_has_nosniff(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-batches.export', ['batch' => $this->batchA->id]));

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    // 13 no-permission batch store blocked
    public function test_13_no_permission_batch_store_blocked(): void
    {
        $payload = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'items' => [
                [
                    'beneficiary_name' => 'Attacker',
                    'beneficiary_account' => '12345',
                    'amount' => 1000.00,
                ],
            ],
        ];

        $this->actingAs($this->noBankPermissionA)
            ->post(route('finance.bank-batches.store'), $payload)
            ->assertStatus(403);
    }

    // 14 unauthorized store side effects = 0
    public function test_14_unauthorized_store_side_effects_zero(): void
    {
        $batchCountBefore = BankTransferBatch::count();
        $itemCountBefore = BankTransferItem::count();

        $payload = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'items' => [
                [
                    'beneficiary_name' => 'Attacker',
                    'beneficiary_account' => '12345',
                    'amount' => 1000.00,
                ],
            ],
        ];

        $this->actingAs($this->noBankPermissionA)
            ->post(route('finance.bank-batches.store'), $payload);

        $this->assertEquals($batchCountBefore, BankTransferBatch::count());
        $this->assertEquals($itemCountBefore, BankTransferItem::count());
    }

    // 15 organization spoof blocked
    public function test_15_organization_spoof_blocked(): void
    {
        $payload = [
            'organization_id' => $this->orgB->id,
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'SPOOF-REF-01',
            'items' => [
                [
                    'beneficiary_name' => 'Legit User',
                    'beneficiary_account' => '12345',
                    'amount' => 1000.00,
                ],
            ],
        ];

        $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.store'), $payload)
            ->assertRedirect(route('finance.bank-batches.index'));

        $batch = BankTransferBatch::where('reference', 'SPOOF-REF-01')->first();
        $this->assertNotNull($batch);
        $this->assertEquals($this->orgA->id, $batch->organization_id);
    }

    // 16 own-org invoice attachment allowed
    public function test_16_own_org_invoice_attachment_allowed(): void
    {
        $payload = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'OWN-INV-REF',
            'items' => [
                [
                    'beneficiary_name' => 'Vendor Alice',
                    'beneficiary_account' => '12345',
                    'amount' => 1500000.00,
                    'invoice_id' => $this->invoiceA->id,
                ],
            ],
        ];

        $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.store'), $payload)
            ->assertRedirect(route('finance.bank-batches.index'));

        $batch = BankTransferBatch::where('reference', 'OWN-INV-REF')->first();
        $this->assertNotNull($batch);
        $this->assertEquals($this->invoiceA->id, $batch->items()->first()->invoice_id);
    }

    // 17 foreign invoice attachment blocked
    public function test_17_foreign_invoice_attachment_blocked(): void
    {
        $batchCountBefore = BankTransferBatch::count();
        $itemCountBefore = BankTransferItem::count();

        $payload = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'FOREIGN-INV-ATTEMPT',
            'items' => [
                [
                    'beneficiary_name' => 'Vendor Secret',
                    'beneficiary_account' => '12345',
                    'amount' => 2750000.00,
                    'invoice_id' => $this->invoiceB->id, // belongs to Org B
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.store'), $payload);

        $response->assertSessionHasErrors('items.0.invoice_id');
        $this->assertEquals($batchCountBefore, BankTransferBatch::count());
        $this->assertEquals($itemCountBefore, BankTransferItem::count());
    }

    // 18 cross-tenant invoice existence not leaked materially
    public function test_18_cross_tenant_invoice_existence_not_leaked_materially(): void
    {
        $payloadForeign = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'items' => [
                [
                    'beneficiary_name' => 'Vendor',
                    'beneficiary_account' => '12345',
                    'amount' => 100.00,
                    'invoice_id' => $this->invoiceB->id,
                ],
            ],
        ];

        $payloadNonexistent = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'items' => [
                [
                    'beneficiary_name' => 'Vendor',
                    'beneficiary_account' => '12345',
                    'amount' => 100.00,
                    'invoice_id' => '00000000-0000-0000-0000-000000000000',
                ],
            ],
        ];

        $respForeign = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.store'), $payloadForeign);

        $respNonexistent = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.store'), $payloadNonexistent);

        $respForeign->assertSessionHasErrors('items.0.invoice_id');
        $respNonexistent->assertSessionHasErrors('items.0.invoice_id');
    }

    // 19 NULL-org store returns 403
    public function test_19_null_org_store_returns_403(): void
    {
        $payload = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'items' => [
                [
                    'beneficiary_name' => 'NullOrg Tester',
                    'beneficiary_account' => '12345',
                    'amount' => 100.00,
                ],
            ],
        ];

        $this->actingAs($this->nullOrgFinance)
            ->post(route('finance.bank-batches.store'), $payload)
            ->assertStatus(403);
    }

    // 20 store is atomic on item persistence failure
    public function test_20_store_is_atomic_on_item_persistence_failure(): void
    {
        $batchCountBefore = BankTransferBatch::count();
        $itemCountBefore = BankTransferItem::count();

        // Second item has invalid currency / trigger failure
        $payload = [
            'bank_name' => 'BCA',
            'account_number' => '111122223333',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'ATOMIC-FAIL-TEST',
            'items' => [
                [
                    'beneficiary_name' => 'Item 1 Valid',
                    'beneficiary_account' => '1111',
                    'amount' => 100.00,
                    'currency' => 'IDR',
                ],
                [
                    'beneficiary_name' => 'Item 2 Invalid Currency',
                    'beneficiary_account' => '2222',
                    'amount' => 200.00,
                    'currency' => 'INVALID_CURRENCY_NOT_IDR',
                ],
            ],
        ];

        $response = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.store'), $payload);

        $response->assertSessionHasErrors('items.1.currency');
        $this->assertEquals($batchCountBefore, BankTransferBatch::count());
        $this->assertEquals($itemCountBefore, BankTransferItem::count());
    }

    // 21 Finance Unit reconciliation index own-org only
    public function test_21_finance_unit_reconciliation_index_own_org_only(): void
    {
        $response = $this->actingAs($this->financeUnitA)->get(route('finance.bank-reconciliation.index'));
        $response->assertOk();

        $batches = $response->viewData('page')['props']['batches']['data'];
        $batchIds = collect($batches)->pluck('id')->all();

        $this->assertContains($this->batchA->id, $batchIds);
        $this->assertNotContains($this->batchB->id, $batchIds);
    }

    // 22 Finance Unit foreign reconciliation show blocked
    public function test_22_finance_unit_foreign_reconciliation_show_blocked(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchB->id]));

        $response->assertStatus(404);
    }

    // 23 Finance Pusat global reconciliation show allowed
    public function test_23_finance_pusat_global_reconciliation_show_allowed(): void
    {
        $response = $this->actingAs($this->financePusat)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchB->id]));

        $response->assertStatus(200);
        $batchProps = $response->viewData('page')['props']['batch'];
        $this->assertEquals($this->batchB->id, $batchProps['id']);
    }

    // 24 foreign beneficiary data absent on blocked show
    public function test_24_foreign_beneficiary_data_absent(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchB->id]));

        $response->assertStatus(404);
        $this->assertStringNotContainsString('Beneficiary Bob Secret', $response->getContent());
    }

    // 25 foreign invoice data absent on blocked show
    public function test_25_foreign_invoice_data_absent(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchB->id]));

        $response->assertStatus(404);
        $this->assertStringNotContainsString('INV-ORG-B-002', $response->getContent());
    }

    // 26 foreign client data absent on blocked show
    public function test_26_foreign_client_data_absent(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchB->id]));

        $response->assertStatus(404);
        $this->assertStringNotContainsString('Client Beta Confidential Corp', $response->getContent());
    }

    // 27 foreign aggregate amount absent on blocked show
    public function test_27_foreign_aggregate_amount_absent(): void
    {
        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchB->id]));

        $response->assertStatus(404);
        $this->assertStringNotContainsString('2750000', $response->getContent());
    }

    // 28 Finance Unit reconciles own Invoice A
    public function test_28_finance_unit_reconciles_own_invoice_a(): void
    {
        $this->assertEquals('APPROVED', $this->invoiceA->status);

        $statement = "REFERENCE,AMOUNT\n";
        $statement .= "INV-{$this->invoiceA->id},1500000.00\n";

        $response = $this->actingAs($this->financeUnitA)->post(route('finance.bank-batches.reconcile'), [
            'statement_csv' => $statement,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Reconciled 1 payments.');

        $this->invoiceA->refresh();
        $this->assertEquals('PAID', $this->invoiceA->status);
    }

    // 29 Finance Unit cannot reconcile foreign Invoice B
    public function test_29_finance_unit_cannot_reconcile_foreign_invoice_b(): void
    {
        $this->assertEquals('APPROVED', $this->invoiceB->status);

        $statement = "REFERENCE,AMOUNT\n";
        $statement .= "INV-{$this->invoiceB->id},2750000.00\n";

        $response = $this->actingAs($this->financeUnitA)->post(route('finance.bank-batches.reconcile'), [
            'statement_csv' => $statement,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Reconciled 0 payments.');
    }

    // 30 Invoice B status unchanged
    public function test_30_invoice_b_status_unchanged(): void
    {
        $this->assertEquals('APPROVED', $this->invoiceB->status);

        $statement = "REFERENCE,AMOUNT\n";
        $statement .= "INV-{$this->invoiceB->id},2750000.00\n";

        $this->actingAs($this->financeUnitA)->post(route('finance.bank-batches.reconcile'), [
            'statement_csv' => $statement,
        ]);

        $this->invoiceB->refresh();
        $this->assertEquals('APPROVED', $this->invoiceB->status);
    }

    // 31 Invoice B updated_at unchanged
    public function test_31_invoice_b_updated_at_unchanged(): void
    {
        $updatedAtBefore = $this->invoiceB->updated_at;

        $statement = "REFERENCE,AMOUNT\n";
        $statement .= "INV-{$this->invoiceB->id},2750000.00\n";

        $this->actingAs($this->financeUnitA)->post(route('finance.bank-batches.reconcile'), [
            'statement_csv' => $statement,
        ]);

        $this->invoiceB->refresh();
        $this->assertEquals($updatedAtBefore->toDateTimeString(), $this->invoiceB->updated_at->toDateTimeString());
    }

    // 32 Finance Pusat may reconcile foreign Invoice with explicit global authority
    public function test_32_finance_pusat_may_reconcile_foreign_invoice_with_explicit_global_authority(): void
    {
        $this->assertEquals('APPROVED', $this->invoiceB->status);

        $statement = "REFERENCE,AMOUNT\n";
        $statement .= "INV-{$this->invoiceB->id},2750000.00\n";

        $response = $this->actingAs($this->financePusat)->post(route('finance.bank-batches.reconcile'), [
            'statement_csv' => $statement,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Reconciled 1 payments.');

        $this->invoiceB->refresh();
        $this->assertEquals('PAID', $this->invoiceB->status);
    }

    // 33 NULL-org non-global reconciliation denied
    public function test_33_null_org_non_global_reconciliation_denied(): void
    {
        $statement = "REFERENCE,AMOUNT\n";
        $statement .= "INV-{$this->invoiceA->id},1500000.00\n";

        $this->actingAs($this->nullOrgFinance)
            ->post(route('finance.bank-batches.reconcile'), ['statement_csv' => $statement])
            ->assertStatus(403);
    }

    // 34 foreign existing vs nonexistent invoice does not create useful oracle
    public function test_34_foreign_existing_vs_nonexistent_invoice_does_not_create_useful_oracle(): void
    {
        $statement1 = "REFERENCE,AMOUNT\nINV-{$this->invoiceB->id},2750000.00\n";
        $resp1 = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.reconcile'), ['statement_csv' => $statement1]);

        $statement2 = "REFERENCE,AMOUNT\nINV-00000000-0000-0000-0000-000000000000,2750000.00\n";
        $resp2 = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.reconcile'), ['statement_csv' => $statement2]);

        $resp1->assertSessionHas('success', 'Reconciled 0 payments.');
        $resp2->assertSessionHas('success', 'Reconciled 0 payments.');
    }

    // 35 no-permission reconcile blocked
    public function test_35_no_permission_reconcile_blocked(): void
    {
        $statement = "REFERENCE,AMOUNT\nINV-{$this->invoiceA->id},1500000.00\n";

        $this->actingAs($this->noBankPermissionA)
            ->post(route('finance.bank-batches.reconcile'), ['statement_csv' => $statement])
            ->assertStatus(403);
    }

    // 36 corrupt Batch A -> Invoice B relationship does not expose Invoice B
    public function test_36_corrupt_batch_a_to_invoice_b_relationship_does_not_expose_invoice_b(): void
    {
        // Manually create a corrupt legacy record (Batch in Org A pointing to Invoice in Org B)
        $corruptItem = BankTransferItem::create([
            'batch_id' => $this->batchA->id,
            'beneficiary_name' => 'Corrupt Item',
            'beneficiary_account' => '112233',
            'amount' => 50000.00,
            'currency' => 'IDR',
            'reference' => 'INV-'.$this->invoiceB->id,
            'invoice_id' => $this->invoiceB->id, // Foreign invoice!
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchA->id]));

        $response->assertOk();
        $batchData = $response->viewData('page')['props']['batch'];

        $itemData = collect($batchData['items'])->firstWhere('id', $corruptItem->id);
        $this->assertNotNull($itemData);
        // Tenant filter on eager load must filter out foreign invoice
        $this->assertNull($itemData['invoice'], 'Corrupt cross-tenant invoice must not be loaded.');
    }

    // 37 corrupt relationship does not expose Client B
    public function test_37_corrupt_relationship_does_not_expose_client_b(): void
    {
        BankTransferItem::create([
            'batch_id' => $this->batchA->id,
            'beneficiary_name' => 'Corrupt Item 2',
            'beneficiary_account' => '112233',
            'amount' => 50000.00,
            'currency' => 'IDR',
            'reference' => 'INV-'.$this->invoiceB->id,
            'invoice_id' => $this->invoiceB->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->financeUnitA)
            ->get(route('finance.bank-reconciliation.show', ['batch' => $this->batchA->id]));

        $response->assertOk();
        $this->assertStringNotContainsString('Client Beta Confidential Corp', $response->getContent());
    }

    // 38 integrity audit detects corrupt relationship
    public function test_38_integrity_audit_detects_corrupt_relationship(): void
    {
        BankTransferItem::create([
            'batch_id' => $this->batchA->id,
            'beneficiary_name' => 'Mismatched Item',
            'beneficiary_account' => '998877',
            'amount' => 75000.00,
            'currency' => 'IDR',
            'reference' => 'INV-'.$this->invoiceB->id,
            'invoice_id' => $this->invoiceB->id,
            'status' => 'PENDING',
        ]);

        $exitCode = Artisan::call('bank-batches:audit-invoice-integrity');
        $output = Artisan::output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Found 1 cross-tenant BankTransferItem invoice mismatches', $output);
    }

    // 39 repair behavior safe and idempotent
    public function test_39_repair_behavior_safe_and_idempotent(): void
    {
        $corrupt = BankTransferItem::create([
            'batch_id' => $this->batchA->id,
            'beneficiary_name' => 'Repair Item',
            'beneficiary_account' => '998877',
            'amount' => 75000.00,
            'currency' => 'IDR',
            'reference' => 'INV-'.$this->invoiceB->id,
            'invoice_id' => $this->invoiceB->id,
            'status' => 'PENDING',
        ]);

        // First run with --repair
        $exitCode1 = Artisan::call('bank-batches:audit-invoice-integrity', ['--repair' => true]);
        $output1 = Artisan::output();

        $this->assertEquals(0, $exitCode1);
        $this->assertStringContainsString('Repaired 1 BankTransferItem records by nullifying foreign invoice_id', $output1);

        $corrupt->refresh();
        $this->assertNull($corrupt->invoice_id);
        $this->assertEquals(75000.00, (float) $corrupt->amount); // payment instruction intact

        // Second run is idempotent and clean
        $exitCode2 = Artisan::call('bank-batches:audit-invoice-integrity', ['--repair' => true]);
        $output2 = Artisan::output();

        $this->assertEquals(0, $exitCode2);
        $this->assertStringContainsString('ALL CLEAN', $output2);
    }

    // 40 BankStatementReconciler itself enforces tenant scope
    public function test_40_bank_statement_reconciler_itself_enforces_tenant_scope(): void
    {
        $svc = new BankStatementReconciler;
        $statement = "REFERENCE,AMOUNT\nINV-{$this->invoiceB->id},2750000.00\n";

        $matched = $svc->reconcileCsv($statement, $this->financeUnitA);

        $this->assertEquals(0, $matched);
        $this->invoiceB->refresh();
        $this->assertEquals('APPROVED', $this->invoiceB->status);
    }

    // 41 controller and service use one reconciliation path
    public function test_41_controller_and_service_use_one_reconciliation_path(): void
    {
        $statement = "REFERENCE,AMOUNT\nINV-{$this->invoiceA->id},1500000.00\n";

        $response = $this->actingAs($this->financeUnitA)
            ->post(route('finance.bank-batches.reconcile'), ['statement_csv' => $statement]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Reconciled 1 payments.');

        $this->invoiceA->refresh();
        $this->assertEquals('PAID', $this->invoiceA->status);
    }

    // 42 direct service invocation cannot bypass tenant scope
    public function test_42_direct_service_invocation_cannot_bypass_tenant_scope(): void
    {
        $svc = new BankStatementReconciler;
        $statement = "REFERENCE,AMOUNT\nINV-{$this->invoiceA->id},1500000.00\n";

        // Without actor context, cannot bypass
        $matched = $svc->reconcileCsv($statement, null);

        $this->assertEquals(0, $matched);
        $this->invoiceA->refresh();
        $this->assertEquals('APPROVED', $this->invoiceA->status);
    }

    // 43 permission role matrix for view_bank_batch_all
    public function test_43_permission_role_matrix_for_view_bank_batch_all(): void
    {
        $financePusatRole = Role::where('name', 'Finance Pusat')->first();
        $financeUnitRole = Role::where('name', 'Finance Unit')->first();
        $systemAdminRole = Role::where('name', 'System Admin')->first();
        $adminPusatRole = Role::where('name', 'Admin Pusat')->first();

        $this->assertTrue($financePusatRole->hasPermissionTo('view_bank_batch_all'), 'Finance Pusat must have view_bank_batch_all');
        $this->assertFalse($financeUnitRole->hasPermissionTo('view_bank_batch_all'), 'Finance Unit must NOT have view_bank_batch_all');
        $this->assertTrue($systemAdminRole->hasPermissionTo('view_bank_batch_all'), 'System Admin must have view_bank_batch_all');
        $this->assertTrue($adminPusatRole->hasPermissionTo('view_bank_batch_all'), 'Admin Pusat must have view_bank_batch_all');
    }
}
