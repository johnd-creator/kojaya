<?php

namespace Tests\Feature;

use App\Models\BankTransferBatch;
use App\Models\BankTransferItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_bank_batch_csv_and_reconcile_invoice(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Finance Pusat');
        $client = Client::factory()->create(['name' => 'Safe Name Ltd']);

        $invoice = Invoice::create([
            'invoice_no' => 'INV-TEST',
            'client_id' => $client->id,
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $batch = BankTransferBatch::create([
            'organization_id' => $org->id,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'format' => 'CSV',
            'batch_date' => now()->toDateString(),
            'reference' => 'BATCH-001',
            'status' => 'DRAFT',
        ]);

        BankTransferItem::create([
            'batch_id' => $batch->id,
            'beneficiary_name' => $client->name,
            'beneficiary_account' => '9876543210',
            'amount' => 1110000,
            'currency' => 'IDR',
            'reference' => 'INV-'.$invoice->id,
            'invoice_id' => $invoice->id,
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($user)->get(route('finance.bank-batches.export', ['batch' => $batch->id]));
        $response->assertStatus(200);
        $csv = $response->getContent();
        $this->assertStringContainsString('ACCOUNT_NUMBER', $csv);
        $this->assertStringContainsString('BENEFICIARY_NAME', $csv);
        $this->assertStringContainsString('INV-'.$invoice->id, $csv);

        $statement = "ACCOUNT_NUMBER,BENEFICIARY_NAME,BENEFICIARY_ACCOUNT,AMOUNT,CURRENCY,REFERENCE\n";
        $statement .= "1234567890,{$client->name},9876543210,1110000.00,IDR,INV-{$invoice->id}\n";

        $response = $this->actingAs($user)->post(route('finance.bank-batches.reconcile'), [
            'statement_csv' => $statement,
        ]);
        $response->assertStatus(302);

        $invoice->refresh();
        $this->assertEquals('PAID', $invoice->status);
    }
}
