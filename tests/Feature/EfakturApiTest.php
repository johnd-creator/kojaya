<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EfakturApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_and_check_status(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Finance Pusat');
        $client = Client::factory()->create([
            'tax_id' => '01.234.567.8-901.000',
        ]);

        $invoice = Invoice::create([
            'invoice_no' => 'INV-API-001',
            'client_id' => $client->id,
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'amount' => 1500000,
            'tax_amount' => 165000,
            'total_amount' => 1665000,
            'status' => 'APPROVED',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $submit = $this->actingAs($user)->post(route('invoices.efaktur.api.submit', ['invoice' => $invoice->id]));
        $submit->assertStatus(200);
        $submissionId = $submit->json('submission.id');
        $this->assertNotEmpty($submissionId);

        $status = $this->actingAs($user)->get(route('invoices.efaktur.api.status', ['submission' => $submissionId]));
        $status->assertStatus(200);
        $this->assertEquals('ACCEPTED', $status->json('submission.status'));
    }
}
