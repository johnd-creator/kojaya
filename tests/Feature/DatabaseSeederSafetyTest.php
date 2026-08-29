<?php

namespace Tests\Feature;

use App\Models\CooperativeContributionType;
use App\Models\CooperativeDuesInvoice;
use App\Models\CooperativeLedgerEntry;
use App\Models\CooperativeMember;
use App\Models\CooperativePayment;
use App\Models\Employee;
use App\Models\JobGrade;
use App\Models\LeaveType;
use App\Models\LoanType;
use App\Models\Organization;
use App\Models\PosCategory;
use App\Models\PosProduct;
use App\Models\PosTransaction;
use App\Models\SalaryComponentType;
use App\Models\User;
use App\Models\WorkShift;
use Database\Seeders\AnggotaSeeder;
use Database\Seeders\CooperativeManagerRoleSeeder;
use Database\Seeders\CooperativeReferenceSeeder;
use Database\Seeders\CooperativeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\InvoiceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UiAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<class-string>
     */
    private array $demoSeeders = [
        CooperativeSeeder::class,
        AnggotaSeeder::class,
        DemoDataSeeder::class,
        InvoiceSeeder::class,
        CooperativeManagerRoleSeeder::class,
    ];

    public function test_demo_seeders_refuse_to_run_in_production(): void
    {
        config(['app.env' => 'production']);

        foreach ($this->demoSeeders as $seederClass) {
            $thrown = false;

            try {
                (new $seederClass)->run();
            } catch (\LogicException $exception) {
                $thrown = true;
                $this->assertStringContainsString('is only available in local, testing, or playwright environments', $exception->getMessage());
            }

            $this->assertTrue($thrown, "Expected {$seederClass} to throw LogicException in production.");
        }
    }

    public function test_demo_seeders_refuse_to_run_in_staging_and_qa(): void
    {
        foreach (['staging', 'qa', 'development'] as $environment) {
            config(['app.env' => $environment]);

            foreach ($this->demoSeeders as $seederClass) {
                $thrown = false;

                try {
                    (new $seederClass)->run();
                } catch (\LogicException $exception) {
                    $thrown = true;
                    $this->assertStringContainsString('is only available in local, testing, or playwright environments', $exception->getMessage());
                }

                $this->assertTrue($thrown, "Expected {$seederClass} to throw LogicException in {$environment}.");
            }
        }
    }

    public function test_ui_audit_seeder_remains_restricted_to_testing_and_playwright(): void
    {
        foreach (['production', 'staging', 'qa', 'local', 'development'] as $environment) {
            config(['app.env' => $environment]);
            $thrown = false;

            try {
                (new UiAuditSeeder)->run();
            } catch (\LogicException $exception) {
                $thrown = true;
                $this->assertStringContainsString('UiAuditSeeder is only available in testing or playwright environments', $exception->getMessage());
            }

            $this->assertTrue($thrown, "UiAuditSeeder unexpectedly ran in {$environment}.");
        }

        config(['app.env' => 'testing']);
        $this->seed(UiAuditSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'ui.system@kojaya.test']);

        config(['app.env' => 'playwright']);
        $this->seed(UiAuditSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'ui.system@kojaya.test']);
    }

    public function test_database_seeder_under_staging_creates_only_safe_reference_data(): void
    {
        config(['app.env' => 'staging']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'System Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Pengurus Koperasi']);
        $this->assertDatabaseHas('roles', ['name' => 'Anggota']);
        $this->assertDatabaseHas('organizations', ['code' => 'KOP-001']);
        $this->assertDatabaseHas('cooperative_contribution_types', ['code' => 'POKOK']);
        $this->assertDatabaseHas('cooperative_contribution_types', ['code' => 'WAJIB']);
        $this->assertDatabaseHas('pos_categories', ['slug' => 'sembako']);
        $this->assertDatabaseHas('loan_types', ['code' => 'emergency']);

        $this->assertSame(0, CooperativeMember::query()->count(), 'No members should be created in staging.');
        $this->assertSame(0, PosTransaction::query()->count(), 'No POS transactions should be created in staging.');
        $this->assertSame(0, PosProduct::query()->count(), 'No POS products should be created in staging.');
        $this->assertSame(0, Employee::query()->count(), 'No employee fixtures should be created in staging.');
        $this->assertSame(0, User::query()->count(), 'No privileged users with default passwords should be created in staging.');
    }

    public function test_database_seeder_under_production_creates_only_safe_reference_data(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new DatabaseSeeder)->run();

        $this->assertDatabaseHas('roles', ['name' => 'System Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Pengurus Koperasi']);
        $this->assertDatabaseHas('organizations', ['code' => 'KOP-001']);
        $this->assertDatabaseHas('cooperative_contribution_types', ['code' => 'POKOK']);

        $this->assertSame(0, CooperativeMember::query()->count(), 'No members should be created in production.');
        $this->assertSame(0, PosTransaction::query()->count(), 'No POS transactions should be created in production.');
        $this->assertSame(0, PosProduct::query()->count(), 'No POS products should be created in production.');
        $this->assertSame(0, Employee::query()->count(), 'No employee fixtures should be created in production.');
        $this->assertSame(0, User::query()->count(), 'No default privileged users should be created in production.');
    }

    public function test_rerunning_reference_seeders_is_idempotent(): void
    {
        config(['app.env' => 'production']);
        $this->app['env'] = 'production';

        (new DatabaseSeeder)->run();

        $baseline = [
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'organizations' => Organization::query()->count(),
            'contribution_types' => CooperativeContributionType::query()->count(),
            'pos_categories' => PosCategory::query()->count(),
            'loan_types' => LoanType::query()->count(),
            'job_grades' => JobGrade::query()->count(),
            'leave_types' => LeaveType::query()->count(),
            'salary_components' => SalaryComponentType::query()->count(),
            'work_shifts' => WorkShift::query()->count(),
        ];

        (new DatabaseSeeder)->run();

        $afterSecondRun = [
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'organizations' => Organization::query()->count(),
            'contribution_types' => CooperativeContributionType::query()->count(),
            'pos_categories' => PosCategory::query()->count(),
            'loan_types' => LoanType::query()->count(),
            'job_grades' => JobGrade::query()->count(),
            'leave_types' => LeaveType::query()->count(),
            'salary_components' => SalaryComponentType::query()->count(),
            'work_shifts' => WorkShift::query()->count(),
        ];

        $this->assertSame($baseline, $afterSecondRun, 'Seeding reference data twice must yield identical record counts.');
    }

    public function test_running_reference_seeder_does_not_modify_existing_records_or_credentials(): void
    {
        $organization = Organization::factory()->create([
            'code' => 'KOP-001',
            'name' => 'Custom Local Koperasi',
        ]);

        $customAdmin = User::factory()->create([
            'email' => 'admin@erp.com',
            'password' => Hash::make('CustomSecretPassword123!'),
            'organization_id' => $organization->id,
        ]);

        $realMember = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'member_no' => 'REAL-001',
            'no_anggota' => 'REAL-001',
            'name' => 'Real Active Member',
        ]);

        $contributionType = CooperativeContributionType::factory()->create([
            'code' => 'POKOK',
            'name' => 'Simpanan Pokok Custom Production',
            'default_amount' => 350000,
            'is_active' => false,
        ]);

        $customLoanType = LoanType::query()->create([
            'code' => 'emergency',
            'name' => 'Pinjaman Darurat Kustom',
            'description' => 'Deskripsi kustom pengurus',
            'interest_rate' => 2.75,
            'admin_fee' => 50000,
            'late_fee_per_day' => 10000,
            'min_amount' => 750000,
            'max_amount' => 12000000,
            'min_term_months' => 2,
            'max_term_months' => 12,
            'is_active' => false,
        ]);

        $customPosCategory = PosCategory::query()->create([
            'slug' => 'sembako',
            'name' => 'Sembako & Kebutuhan Pokok',
            'is_active' => false,
        ]);

        $invoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $realMember->id,
            'cooperative_contribution_type_id' => $contributionType->id,
            'period' => '2026-01',
            'amount' => 350000,
            'paid_amount' => 350000,
            'due_date' => '2026-01-10',
            'status' => 'PAID',
        ]);

        $payment = CooperativePayment::query()->create([
            'cooperative_member_id' => $realMember->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'reference_no' => 'REAL-PAY-2026-001',
            'amount' => 350000,
            'payment_method' => 'TRANSFER',
            'paid_at' => '2026-01-05',
            'status' => 'APPROVED',
        ]);

        $ledgerEntry = CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $realMember->id,
            'cooperative_payment_id' => $payment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'credit' => 350000,
            'debit' => 0,
        ]);

        $posProduct = PosProduct::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Real POS Product',
            'sale_price' => 50000,
        ]);

        $posTransaction = PosTransaction::query()->create([
            'cooperative_member_id' => $realMember->id,
            'transaction_no' => 'TRX-REAL-001',
            'client_reference' => 'CLIENT-REF-REAL-001',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'gross_profit' => 10000,
            'cash_received' => 50000,
            'cash_change' => 0,
            'status' => 'COMPLETED',
            'sold_at' => '2026-01-15 10:00:00',
        ]);

        config(['app.env' => 'production']);
        $this->app['env'] = 'production';

        (new DatabaseSeeder)->run();

        $customAdmin->refresh();
        $this->assertTrue(
            Hash::check('CustomSecretPassword123!', $customAdmin->password),
            'Reference seeder must never overwrite an existing user password.',
        );

        $realMember->refresh();
        $this->assertSame('Real Active Member', $realMember->name);

        $contributionType->refresh();
        $this->assertSame('Simpanan Pokok Custom Production', $contributionType->name);
        $this->assertSame(350000.0, (float) $contributionType->default_amount);
        $this->assertFalse((bool) $contributionType->is_active, 'Existing contribution type active status must be preserved.');

        $customLoanType->refresh();
        $this->assertSame('Pinjaman Darurat Kustom', $customLoanType->name);
        $this->assertSame(2.75, (float) $customLoanType->interest_rate);
        $this->assertSame(750000.0, (float) $customLoanType->min_amount);
        $this->assertSame(12000000.0, (float) $customLoanType->max_amount);
        $this->assertSame(2, $customLoanType->min_term_months);
        $this->assertSame(12, $customLoanType->max_term_months);
        $this->assertFalse((bool) $customLoanType->is_active, 'Existing loan type active status must be preserved.');

        $customPosCategory->refresh();
        $this->assertSame('Sembako & Kebutuhan Pokok', $customPosCategory->name);
        $this->assertFalse((bool) $customPosCategory->is_active, 'Existing POS category active status must be preserved.');

        $invoice->refresh();
        $this->assertSame('PAID', $invoice->status);
        $this->assertSame(350000.0, (float) $invoice->paid_amount);

        $this->assertDatabaseHas('cooperative_payments', ['id' => $payment->id, 'reference_no' => 'REAL-PAY-2026-001']);
        $this->assertDatabaseHas('cooperative_ledger_entries', ['id' => $ledgerEntry->id]);
        $this->assertDatabaseHas('pos_products', ['id' => $posProduct->id, 'name' => 'Real POS Product']);
        $this->assertDatabaseHas('pos_transactions', ['id' => $posTransaction->id, 'transaction_no' => 'TRX-REAL-001']);
    }

    public function test_existing_member_number_collision_does_not_mutate_or_delete_real_member_financials(): void
    {
        config(['app.env' => 'testing']);

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CooperativeReferenceSeeder::class);

        $organization = Organization::query()->where('code', 'KOP-001')->firstOrFail();
        $pokok = CooperativeContributionType::query()->where('code', 'POKOK')->firstOrFail();

        $realMember = CooperativeMember::factory()->create([
            'organization_id' => $organization->id,
            'no_anggota' => '001',
            'member_no' => '001',
            'name' => 'Real Legacy Member 001',
            'status' => 'ACTIVE',
        ]);

        $realInvoice = CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $realMember->id,
            'cooperative_contribution_type_id' => $pokok->id,
            'period' => '2025-01',
            'amount' => 200000,
            'paid_amount' => 200000,
            'due_date' => '2025-01-10',
            'status' => 'PAID',
        ]);

        $realPayment = CooperativePayment::query()->create([
            'cooperative_member_id' => $realMember->id,
            'cooperative_dues_invoice_id' => $realInvoice->id,
            'reference_no' => 'REAL-ORIGINAL-PAY-001',
            'amount' => 200000,
            'payment_method' => 'CASH',
            'paid_at' => '2025-01-05',
            'status' => 'APPROVED',
        ]);

        $realLedger = CooperativeLedgerEntry::factory()->create([
            'cooperative_member_id' => $realMember->id,
            'cooperative_payment_id' => $realPayment->id,
            'entry_type' => 'SAVING_PAYMENT',
            'ledger_scope' => 'SAVINGS',
            'credit' => 200000,
            'debit' => 0,
        ]);

        $this->fakeCooperativeReceiptIssuance();

        $this->seed(AnggotaSeeder::class);
        $this->seed(CooperativeSeeder::class);

        $realMember->refresh();
        $this->assertSame('Real Legacy Member 001', $realMember->name, 'Real member profile must not be overwritten by demo seeder.');

        $this->assertDatabaseHas('cooperative_dues_invoices', [
            'id' => $realInvoice->id,
            'cooperative_member_id' => $realMember->id,
            'status' => 'PAID',
        ]);

        $this->assertDatabaseHas('cooperative_payments', [
            'id' => $realPayment->id,
            'reference_no' => 'REAL-ORIGINAL-PAY-001',
        ]);

        $this->assertDatabaseHas('cooperative_ledger_entries', [
            'id' => $realLedger->id,
            'cooperative_member_id' => $realMember->id,
        ]);

        $this->assertDatabaseHas('cooperative_members', [
            'no_anggota' => 'DEMO-ANG-001',
        ]);
        $this->assertDatabaseHas('cooperative_members', [
            'no_anggota' => 'DEMO-KOP-001',
        ]);
    }
}
