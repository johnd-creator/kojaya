<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EfakturBatchExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_batch_and_download_csv_for_multiple_invoices(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $client = Client::factory()->create([
            'tax_id' => '01.234.567.8-901.000',
            'address' => 'Jl. Gatot Subroto No. 1',
        ]);

        $inv1 = Invoice::create([
            'invoice_no' => 'INV-001',
            'client_id' => $client->id,
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
        $inv2 = Invoice::create([
            'invoice_no' => 'INV-002',
            'client_id' => $client->id,
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'amount' => 3000000,
            'tax_amount' => 330000,
            'total_amount' => 3330000,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response = $this->actingAs($user)->post(route('invoices.efaktur.batch-create'), [
            'invoice_ids' => [$inv1->id, $inv2->id],
            'reference' => 'BATCH-EFK-001',
        ]);
        $response->assertStatus(200);
        $batchId = $response->json('batch_id');
        $this->assertNotEmpty($batchId);

        $download = $this->actingAs($user)->get(route('invoices.efaktur.batch-csv', ['batch' => $batchId]));
        $download->assertStatus(200);
        $csv = $download->getContent();
        $this->assertStringContainsString('KD_JENIS_TRANSAKSI', $csv);
        $this->assertStringContainsString('INV-001', $csv);
        $this->assertStringContainsString('INV-002', $csv);
    }
}
