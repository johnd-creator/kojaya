<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EFakturExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_efaktur_csv_returns_valid_headers_and_values(): void
    {
        $user = User::factory()->create();
        $orgId = $user->organization_id ?? null;
        if (! $orgId) {
            $org = \App\Models\Organization::factory()->create();
            $orgId = $org->id;
            $user->organization_id = $orgId;
            $user->save();
        }
        $client = Client::factory()->create([
            'tax_id' => '01.234.567.8-901.000',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
        ]);

        $invoice = Invoice::create([
            'invoice_no' => '010-000-22',
            'client_id' => $client->id,
            'organization_id' => $orgId,
            'unit_id' => $orgId,
            'amount' => 10000000,
            'tax_amount' => 1100000,
            'total_amount' => 11100000,
            'status' => 'DRAFT',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('invoices.export-efaktur-csv', ['invoice' => $invoice->id]));
        $response->assertStatus(200);
        $csv = $response->getContent();
        $this->assertStringContainsString('KD_JENIS_TRANSAKSI', $csv);
        $this->assertStringContainsString('NOMOR_FAKTUR', $csv);
        $this->assertStringContainsString('JUMLAH_DPP', $csv);
        $this->assertStringContainsString('JUMLAH_PPN', $csv);
        $this->assertStringContainsString('010-000-22', $csv);
        $this->assertStringContainsString('10000000.00', $csv);
        $this->assertStringContainsString('1100000.00', $csv);
    }
}
