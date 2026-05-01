<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\BankStatementReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankStatementReconcilerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconcile_marks_invoice_as_paid(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $client = Client::factory()->create(['name' => 'Safe Name Ltd']);

        $invoice = Invoice::create([
            'invoice_no' => 'INV-UNIT-001',
            'client_id' => $client->id,
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'amount' => 500000,
            'tax_amount' => 55000,
            'total_amount' => 555000,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $statement = "ACCOUNT_NUMBER,BENEFICIARY_NAME,BENEFICIARY_ACCOUNT,AMOUNT,CURRENCY,REFERENCE\n";
        $statement .= "1234567890,{$client->name},9876543210,555000.00,IDR,INV-{$invoice->id}\n";

        $svc = new BankStatementReconciler;
        $matched = $svc->reconcileCsv($statement);
        $this->assertEquals(1, $matched);

        $invoice->refresh();
        $this->assertEquals('PAID', $invoice->status);
    }
}
