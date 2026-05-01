<?php

namespace Tests\Feature;

use Database\Seeders\DemoDataSeeder;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    public function test_demo_data_seeder_populates_core_demo_records(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'demo.pm@erp.com',
        ]);
        $this->assertDatabaseHas('organizations', [
            'code' => 'KOP-001',
        ]);
        $this->assertDatabaseHas('employees', [
            'employee_code' => 'DEMOEMP001',
            'email' => 'demo.pm@erp.com',
        ]);
        $this->assertDatabaseHas('clients', [
            'code' => 'DEMO-CLI-PLN',
        ]);
        $this->assertDatabaseHas('projects', [
            'project_code' => 'DEMO-PRJ-001',
            'status' => 'ONGOING',
        ]);
        $this->assertDatabaseHas('project_tasks', [
            'name' => 'Cable tray installation',
            'status' => 'IN_PROGRESS',
        ]);
        $this->assertDatabaseHas('invoices', [
            'invoice_no' => 'DEMO-INV-2026-001',
            'status' => 'PAID',
        ]);
        $this->assertDatabaseHas('assets', [
            'code' => 'DEMO-AST-001',
        ]);
        $this->assertDatabaseHas('petty_cash_accounts', [
            'name' => 'Petty Cash Operasional Demo',
        ]);
        $this->assertDatabaseHas('reimbursements', [
            'description' => 'Transport lokal dan kebutuhan koordinasi lapangan.',
        ]);
        $this->assertDatabaseHas('employee_certificates', [
            'certificate_number' => 'DEMO-SIO-001',
        ]);
    }
}
