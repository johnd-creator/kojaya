<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PettyCashTransaction;
use App\Models\Project;
use App\Models\Reimbursement;
use App\Models\User;
use App\Services\ProjectFinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_project_revenue_correctly(): void
    {
        $org = Organization::factory()->create();
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Finance Project',
            'project_code' => 'PROJ-FIN',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000000,
            'status' => 'ONGOING',
        ]);

        // Paid Invoice
        Invoice::create([
            'organization_id' => $org->id,
            'unit_id' => $org->id, // Add unit_id
            'project_id' => $project->id,
            'client_id' => \App\Models\Client::factory()->create(['organization_id' => $org->id])->id,
            'invoice_no' => 'INV-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'amount' => 500000, // Added amount
            'total_amount' => 500000,
            'status' => 'PAID',
        ]);

        // Unpaid Invoice (Should not count)
        Invoice::create([
            'organization_id' => $org->id,
            'unit_id' => $org->id, // Add unit_id
            'project_id' => $project->id,
            'client_id' => \App\Models\Client::factory()->create(['organization_id' => $org->id])->id,
            'invoice_no' => 'INV-002',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'amount' => 200000, // Added amount
            'total_amount' => 200000,
            'status' => 'SENT',
        ]);

        $service = new ProjectFinanceService;
        $revenue = $service->calculateRevenue($project);

        $this->assertEquals(500000, $revenue);
    }

    public function test_calculates_direct_costs_correctly(): void
    {
        $org = Organization::factory()->create();
        $project = Project::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $org->id,
            'name' => 'Cost Project',
            'project_code' => 'PROJ-COST',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'budget' => 1000000,
            'status' => 'ONGOING',
        ]);

        $employee = \App\Models\Employee::factory()->create(['organization_id' => $org->id]);

        // 1. Reimbursement (Paid)
        $user = \App\Models\User::factory()->create(['organization_id' => $org->id]);
        Reimbursement::create([
            'organization_id' => $org->id,
            'unit_id' => $org->id,
            'user_id' => $user->id,
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'submission_date' => now(),
            'amount' => 100000,
            'total_amount' => 100000,
            'status' => 'PAID',
            'description' => 'Travel',
        ]);

        // 2. Petty Cash (Credit/Expense)
        // Note: PettyCashAccount needed for foreign key
        $pcAccount = \App\Models\PettyCashAccount::create([
            'organization_id' => $org->id,
            'name' => 'Site Cash',
            'balance' => 1000000,
        ]);

        PettyCashTransaction::create([
            'petty_cash_account_id' => $pcAccount->id,
            'user_id' => \App\Models\User::factory()->create(['organization_id' => $org->id])->id, // Add user_id
            'project_id' => $project->id,
            'transaction_date' => now(),
            'type' => 'CREDIT',
            'amount' => 50000,
            'status' => 'APPROVED',
            'description' => 'Supplies',
            'reference_no' => 'PC-001',
        ]);

        // 3. Labor Cost (Mocking Payroll Allocation logic via relationship if possible,
        // but since PayrollAllocation model might not be fully set up in this test context,
        // we will check if the service handles empty labor correctly or if we can seed it)
        // Assuming no payroll allocation for simplicity, or we can mock the relation.

        $service = new ProjectFinanceService;
        $costs = $service->calculateDirectCosts($project);

        $this->assertEquals(150000, $costs['total']);
        $this->assertEquals(100000, $costs['breakdown']['reimbursements']);
        $this->assertEquals(50000, $costs['breakdown']['petty_cash']);
    }

    public function test_transactions_endpoint_bounds_request_derived_limit(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $project = Project::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $client = \App\Models\Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Invoice::factory()->paid()->count(51)->create([
            'organization_id' => $organization->id,
            'unit_id' => $organization->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('projects.transactions', [
                'project' => $project,
                'limit' => 999999,
            ]))
            ->assertOk()
            ->assertJsonCount(50, 'data');
    }
}
